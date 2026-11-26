<?php

namespace App\Services;

use App\DTOs\AmazonSyncSummaryDTO;
use App\Enums\PriceReviewReason;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\MeliApiException;
use App\Interfaces\ExternalCatalogRepositoryInterface;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\MercadolibreAccount;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AmazonPriceSyncService
{
    public const AMAZON_NOT_FOUND = -1;

    private const CHUNK_SIZE      = 100;
    private const USD_TOLERANCE   = 0.009;
    private const COP_TOLERANCE   = 1;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ExternalCatalogRepositoryInterface $catalogRepository,
        private readonly MercadoLibreService $meliService,
        private readonly ProductPublishingService $publishingService,
        private readonly PricingService $pricingService
    ) {}

    public function syncAccount(MercadolibreAccount $account): AmazonSyncSummaryDTO
    {
        $counts   = [];
        $settings = $this->pricingService->settings();

        $this->productRepository->activeListedWithSkuChunked($account->id, self::CHUNK_SIZE, function (Collection $products) use ($account, $settings, &$counts) {
            $documents = $this->catalogRepository->findManyBySkus($products->pluck('sku')->all());

            foreach ($products as $product) {
                $document = $documents[mb_strtoupper(trim((string) $product->sku))] ?? null;
                $result   = $document ? $this->evaluate($account, $product, $document, $settings) : 'missing';
                $counts[$result] = ($counts[$result] ?? 0) + 1;
            }
        });

        return AmazonSyncSummaryDTO::fromCounts($counts);
    }

    public function pendingReviews(MercadolibreAccount $account): LengthAwarePaginator
    {
        return $this->productRepository->pendingReviews($account->id);
    }

    public function countPendingReviews(MercadolibreAccount $account): int
    {
        return $this->productRepository->countPendingReviews($account->id);
    }

    public function suggestedPrice(Product $product): ?float
    {
        if (!$product->review_amazon_price || !($product->weight > 0)) {
            return null;
        }

        return $this->pricingService->calculate((float) $product->review_amazon_price, (float) $product->weight)->finalPrice;
    }

    public function applySuggestedPrice(MercadolibreAccount $account, Product $product): Product
    {
        $suggested = $this->suggestedPrice($product)
            ?? throw new BusinessRuleException('Esta revisión no tiene un precio sugerido para aplicar.');

        return $this->applyPrice($account, $product, $suggested, (float) $product->review_amazon_price, (float) $product->weight);
    }

    public function dismiss(Product $product): Product
    {
        return $this->productRepository->update($product, ['review_status' => 'dismissed']);
    }

    private function evaluate(MercadolibreAccount $account, Product $product, array $document, array $settings): string
    {
        if ((int) ($document['estado'] ?? 1) === self::AMAZON_NOT_FOUND) {
            return $this->pauseMissingOnAmazon($account, $product);
        }

        $amazonPrice = is_numeric($document['precio'] ?? null) ? (float) $document['precio'] : 0.0;
        $basePrice   = (float) $product->base_price;
        $weight      = is_numeric($document['peso'] ?? null) && $document['peso'] > 0 ? (float) $document['peso'] : (float) $product->weight;

        if ($amazonPrice <= 0) {
            return $this->touch($product, 'unchanged');
        }

        if ($basePrice <= 0) {
            $this->productRepository->update($product, ['base_price' => $amazonPrice, 'weight' => $weight ?: null, 'amazon_checked_at' => now()]);

            return 'baseline';
        }

        if (abs($amazonPrice - $basePrice) < self::USD_TOLERANCE) {
            return $this->touch($product, 'unchanged');
        }

        return $amazonPrice > $basePrice
            ? $this->raisePrice($account, $product, $amazonPrice, $weight, $settings)
            : $this->flagPriceDrop($product, $amazonPrice, $weight, $settings);
    }

    private function raisePrice(MercadolibreAccount $account, Product $product, float $amazonPrice, float $weight, array $settings): string
    {
        $finalPrice = $this->pricingService->calculateWith($settings, $amazonPrice, $weight)->finalPrice;

        try {
            $this->applyPrice($account, $product, $finalPrice, $amazonPrice, $weight);

            return 'price_updated';
        } catch (\Throwable $e) {
            $this->flag($product, PriceReviewReason::UpdateFailed, sprintf(
                'Amazon subió de %s a %s USD, pero no se pudo actualizar el precio en Mercado Libre: %s',
                $this->usd((float) $product->base_price), $this->usd($amazonPrice), $e->getMessage()
            ), $amazonPrice);

            return 'failed';
        }
    }

    private function flagPriceDrop(Product $product, float $amazonPrice, float $weight, array $settings): string
    {
        $alreadyReviewed = in_array($product->review_status, ['pending', 'dismissed'], true)
            && $product->review_reason === PriceReviewReason::PriceDrop
            && abs((float) $product->review_amazon_price - $amazonPrice) < self::USD_TOLERANCE;

        if ($alreadyReviewed) {
            return $this->touch($product, 'unchanged');
        }

        $suggested = $this->pricingService->calculateWith($settings, $amazonPrice, $weight)->finalPrice;

        $this->flag($product, PriceReviewReason::PriceDrop, sprintf(
            'Amazon bajó de %s a %s USD. Precio sugerido %s COP (actual %s COP).',
            $this->usd((float) $product->base_price), $this->usd($amazonPrice), $this->cop($suggested), $this->cop((float) $product->price)
        ), $amazonPrice, ['weight' => $weight ?: $product->weight]);

        return 'flagged';
    }

    private function pauseMissingOnAmazon(MercadolibreAccount $account, Product $product): string
    {
        try {
            $this->publishingService->changeStatus($account, $product, 'paused');
            $this->flag($product, PriceReviewReason::NotOnAmazon, 'Ya no existe en Amazon: se pausó en Mercado Libre. Reactívala a mano cuando corresponda.', null);

            return 'paused';
        } catch (\Throwable $e) {
            $this->flag($product, PriceReviewReason::UpdateFailed, "Ya no existe en Amazon y no se pudo pausar en Mercado Libre: {$e->getMessage()}", null);

            return 'failed';
        }
    }

    private function applyPrice(MercadolibreAccount $account, Product $product, float $finalPrice, float $amazonPrice, float $weight): Product
    {
        if (abs($finalPrice - (float) $product->price) >= self::COP_TOLERANCE) {
            $response = $this->meliService->updateProduct($account, (string) $product->meli_item_id, ['price' => $finalPrice]);

            if (!isset($response['last_updated'])) {
                throw MeliApiException::fromResponse('actualizar precio', $response);
            }
        }

        return $this->productRepository->update($product, [
            'price'               => $finalPrice,
            'base_price'          => $amazonPrice,
            'weight'              => $weight ?: $product->weight,
            'review_status'       => null,
            'review_reason'       => null,
            'review_detail'       => null,
            'review_amazon_price' => null,
            'review_flagged_at'   => null,
            'amazon_checked_at'   => now(),
            'last_sync'           => now(),
        ]);
    }

    private function flag(Product $product, PriceReviewReason $reason, string $detail, ?float $amazonPrice, array $extra = []): void
    {
        $this->productRepository->update($product, $extra + [
            'review_status'       => 'pending',
            'review_reason'       => $reason,
            'review_detail'       => $detail,
            'review_amazon_price' => $amazonPrice,
            'review_flagged_at'   => now(),
            'amazon_checked_at'   => now(),
        ]);
    }

    private function touch(Product $product, string $result): string
    {
        $this->productRepository->update($product, ['amazon_checked_at' => now()]);

        return $result;
    }

    private function usd(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function cop(float $value): string
    {
        return '$' . number_format($value, 0, ',', '.');
    }
}
