<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAmazonPricesJob;
use App\Models\Product;
use App\Services\AmazonPriceSyncService;
use App\Services\ProductService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PriceReviewController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(
        private readonly AmazonPriceSyncService $priceSyncService,
        private readonly ProductService $productService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        $account   = $this->currentAccount();
        $reviews   = $account ? $this->priceSyncService->pendingReviews($account) : null;
        $suggested = $reviews
            ? collect($reviews->items())->mapWithKeys(fn (Product $product) => [$product->id => $this->priceSyncService->suggestedPrice($product)])->all()
            : [];

        return view('products.price-reviews', compact('account', 'reviews', 'suggested'));
    }

    public function run(): RedirectResponse
    {
        $this->authorize('syncAll', Product::class);

        if (!$account = $this->currentAccount()) {
            return back()->with('error', 'Conecta una cuenta de Mercado Libre primero.');
        }

        SyncAmazonPricesJob::dispatch($account);

        return redirect()->route('products.price-reviews')->with('success', 'Revisión de precios iniciada. Los resultados aparecerán aquí en unos minutos.');
    }

    public function apply(int $id): RedirectResponse
    {
        [$account, $product] = $this->resolve($id);
        $this->authorize('update', $product);

        $product = $this->priceSyncService->applySuggestedPrice($account, $product);

        return redirect()->route('products.price-reviews')
            ->with('success', "{$product->meli_item_id}: precio actualizado a $" . number_format((float) $product->price, 0, ',', '.') . ' COP en Mercado Libre.');
    }

    public function dismiss(int $id): RedirectResponse
    {
        [, $product] = $this->resolve($id);
        $this->authorize('update', $product);

        $this->priceSyncService->dismiss($product);

        return redirect()->route('products.price-reviews')->with('success', "{$product->meli_item_id}: se mantiene el precio actual.");
    }

    private function resolve(int $id): array
    {
        $account = $this->currentAccount();
        $product = $account ? $this->productService->findForAccount($account, $id) : null;
        abort_if(!$product || $product->review_status !== 'pending', 404);

        return [$account, $product];
    }
}
