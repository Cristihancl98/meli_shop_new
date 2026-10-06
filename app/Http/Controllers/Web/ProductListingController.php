<?php

namespace App\Http\Controllers\Web;

use App\DTOs\UpdateListingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\UpdateListingRequest;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\ProductPublishingService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;

class ProductListingController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(
        private readonly ProductPublishingService $publishingService,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function pause(int $id): RedirectResponse
    {
        [$account, $product] = $this->resolve($id, 'changeStatus');
        $this->publishingService->changeStatus($account, $product, 'paused');

        return back()->with('success', 'Producto pausado.');
    }

    public function activate(int $id): RedirectResponse
    {
        [$account, $product] = $this->resolve($id, 'changeStatus');
        $this->publishingService->changeStatus($account, $product, 'active');

        return back()->with('success', 'Producto activado.');
    }

    public function update(UpdateListingRequest $request, int $id): RedirectResponse
    {
        [$account, $product] = $this->resolve($id, 'update');
        $this->publishingService->updateListing($account, $product, UpdateListingDTO::fromArray($request->validated()));

        return redirect()->route('products.show', $id)->with('success', 'Publicación actualizada en Mercado Libre.');
    }

    public function archive(int $id): RedirectResponse
    {
        [, $product] = $this->resolve($id, 'archive');
        $this->publishingService->archive($product);

        return redirect()->route('products.index')->with('success', 'Producto archivado.');
    }

    private function resolve(int $id, string $ability): array
    {
        $account = $this->currentAccount();
        abort_if(!$account, 404);

        $product = $this->productRepository->findForAccount($account->id, $id);
        abort_if(!$product, 404);

        $this->authorize($ability, $product);

        return [$account, $product];
    }
}
