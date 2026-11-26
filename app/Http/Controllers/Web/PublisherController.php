<?php

namespace App\Http\Controllers\Web;

use App\DTOs\PublishProductDTO;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ScrapingRequiredException;
use App\DTOs\CatalogSearchDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\FilterCatalogRequest;
use App\Http\Requests\BulkPublish\ListBulkCodesRequest;
use App\Http\Requests\Meli\CategoryLevelRequest;
use App\Http\Requests\Pricing\CalculatePriceRequest;
use App\Http\Requests\BulkPublish\StoreBulkCodesRequest;
use App\Http\Requests\Product\PublishProductRequest;
use App\Models\Product;
use App\Services\BulkPublishService;
use App\Services\CatalogService;
use App\Services\MeliCategoryService;
use App\Services\CatalogSearchService;
use App\Services\PricingService;
use App\Services\PublishBatchService;
use App\Traits\ApiResponses;
use App\Services\ProductPublishingService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublisherController extends Controller
{
    use ApiResponses, ResolvesMeliAccount;

    public function __construct(
        private readonly ProductPublishingService $publishingService,
        private readonly CatalogService $catalogService,
        private readonly BulkPublishService $bulkPublishService,
        private readonly PricingService $pricingService,
        private readonly MeliCategoryService $categoryService,
        private readonly PublishBatchService $batchService,
        private readonly CatalogSearchService $catalogSearchService
    ) {}

    public function price(CalculatePriceRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        $breakdown = $this->pricingService->calculate(
            (float) $request->validated('base_price'),
            (float) $request->validated('weight')
        );

        return $this->ok($breakdown->toArray(), 'Precio recalculado.');
    }

    public function create(Request $request): View
    {
        $this->authorize('publish', Product::class);

        $account           = $this->currentAccount();
        $sku               = trim((string) $request->query('sku', ''));
        $lookup            = null;
        $lookupError       = null;
        $suggestedCategory = null;
        $scraperUrl        = null;
        $batchItem         = $account && $request->filled('batch_item')
            ? $this->batchService->itemForCorrection($account, (int) $request->query('batch_item'))
            : null;

        if ($batchItem) {
            $sku               = $batchItem->sku;
            $lookup            = $batchItem->data;
            $suggestedCategory = $batchItem->data['suggested_category'] ?? null;
        } elseif ($sku !== '') {
            try {
                $lookup            = $this->publishingService->lookupSku($account, $sku, true);
                $suggestedCategory = $lookup['categoria_meli']
                    ?? ($account ? $this->publishingService->suggestCategory($account, (string) $lookup['titulo']) : null);
            } catch (ScrapingRequiredException $e) {
                if ($request->boolean('scraped')) {
                    $lookupError = 'El scraper respondió, pero el producto no aparece en el catálogo. Verifica que el scraper y el panel usen la misma base MongoDB.';
                } else {
                    $scraperUrl = $e->scraperUrl;
                }
            } catch (BusinessRuleException $e) {
                $lookupError = $e->getMessage();
            }
        }

        return view('publisher.create', compact('account', 'sku', 'lookup', 'lookupError', 'suggestedCategory', 'scraperUrl', 'batchItem'));
    }

    public function categories(CategoryLevelRequest $request): JsonResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return $this->fail('Vincula una cuenta de Mercado Libre para consultar categorías.', 422);
        }

        $level = $this->categoryService->level($account, $request->validated('category_id'));

        return $this->ok($level->toArray(), 'Categorías obtenidas.');
    }

    public function store(PublishProductRequest $request): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return back()->withErrors(['account' => 'Debes vincular una cuenta de Mercado Libre primero.']);
        }

        $product   = $this->publishingService->publish($account, PublishProductDTO::fromArray($request->validated()), $request->user());
        $batchItem = $request->filled('batch_item_id')
            ? $this->batchService->markPublishedFromEditor($account, (int) $request->validated('batch_item_id'), $product)
            : null;

        return $batchItem
            ? redirect()->route('publisher.batches.show', $batchItem->publish_batch_id)->with('success', "{$batchItem->sku} publicado en Mercado Libre: {$product->meli_item_id}")
            : redirect()->route('products.show', $product->id)->with('success', "Publicado en Mercado Libre: {$product->meli_item_id}");
    }

    public function catalog(FilterCatalogRequest $request): View
    {
        $this->authorize('publish', Product::class);

        $criteria = $request->searched() ? $request->criteria() : CatalogSearchDTO::fromArray(['only_publishable' => true]);
        $result   = null;
        $error    = null;

        if ($request->searched()) {
            try {
                $result = $this->catalogSearchService->search($criteria, $this->currentAccount());
            } catch (\Throwable $e) {
                report($e);
                $error = 'No fue posible consultar el catálogo. Verifica la conexión MongoDB.';
            }
        }

        $titleLimit = CatalogSearchService::MELI_TITLE_LIMIT;
        $maxResults = CatalogSearchService::MAX_RESULTS;

        return view('publisher.catalog', compact('criteria', 'result', 'error', 'titleLimit', 'maxResults'));
    }

    public function codes(ListBulkCodesRequest $request): View
    {
        $this->authorize('publish', Product::class);

        $account = $this->currentAccount();
        $date    = $request->validated('date');
        $codes   = $account ? $this->bulkPublishService->list($account, $date) : collect();

        return view('publisher.codes', compact('account', 'codes', 'date'));
    }

    public function storeCodes(StoreBulkCodesRequest $request): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return back()->withErrors(['account' => 'Debes vincular una cuenta de Mercado Libre primero.']);
        }

        $count = $this->bulkPublishService->addCodes($account, $request->validated('skus'));

        return redirect()->route('publisher.codes')->with('success', "{$count} código(s) registrados.");
    }
}
