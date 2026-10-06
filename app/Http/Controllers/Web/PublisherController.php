<?php

namespace App\Http\Controllers\Web;

use App\DTOs\PublishProductDTO;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPublish\ListBulkCodesRequest;
use App\Http\Requests\Pricing\CalculatePriceRequest;
use App\Http\Requests\BulkPublish\StoreBulkCodesRequest;
use App\Http\Requests\Product\PublishProductRequest;
use App\Models\Product;
use App\Services\BulkPublishService;
use App\Services\CatalogService;
use App\Services\PricingService;
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
        private readonly PricingService $pricingService
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

        if ($sku !== '') {
            try {
                $lookup            = $this->publishingService->lookupSku($account, $sku);
                $suggestedCategory = $lookup['categoria_meli']
                    ?? ($account ? $this->publishingService->suggestCategory($account, (string) $lookup['titulo']) : null);
            } catch (BusinessRuleException $e) {
                $lookupError = $e->getMessage();
            }
        }

        return view('publisher.create', compact('account', 'sku', 'lookup', 'lookupError', 'suggestedCategory'));
    }

    public function store(PublishProductRequest $request): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return back()->withErrors(['account' => 'Debes vincular una cuenta de Mercado Libre primero.']);
        }

        $product = $this->publishingService->publish($account, PublishProductDTO::fromArray($request->validated()), $request->user());

        return redirect()->route('products.show', $product->id)->with('success', "Publicado en Mercado Libre: {$product->meli_item_id}");
    }

    public function catalog(Request $request): View
    {
        $this->authorize('publish', Product::class);

        $mode    = $request->query('mode', 'title');
        $term    = trim((string) $request->query('q', ''));
        $results = [];
        $error   = null;

        if (mb_strlen($term) >= 2) {
            try {
                $results = $mode === 'category'
                    ? $this->catalogService->findByCategory($term)
                    : $this->catalogService->searchByTitle($term);
            } catch (\Throwable $e) {
                report($e);
                $error = 'No fue posible consultar el catálogo. Verifica la conexión MongoDB.';
            }
        }

        return view('publisher.catalog', compact('mode', 'term', 'results', 'error'));
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
