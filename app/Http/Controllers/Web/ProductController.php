<?php

namespace App\Http\Controllers\Web;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Interfaces\MercadolibreAccountRepositoryInterface;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MercadolibreAccountRepositoryInterface $accountRepository
    ) {}

    public function index(Request $request): View
    {
        $user       = auth()->user();
        $account    = $this->accountRepository->findSelectedByUser($user->id, session('active_meli_account_id'));
        $products   = $account
            ? $this->productService->list($account, $request->only(['search', 'status', 'category_id', 'min_price', 'max_price']))
            : new LengthAwarePaginator([], 0, 20);
        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'account'));
    }

    public function show(int $id): View
    {
        $product = $this->productService->find($id);
        abort_if(!$product, 404);

        return view('products.show', compact('product'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $user    = auth()->user();
        $account = $this->accountRepository->findSelectedByUser($user->id, session('active_meli_account_id'));

        if (!$account) {
            return back()->withErrors(['account' => 'Debes vincular una cuenta de Mercado Libre primero.']);
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->getPathname()
            : null;

        $this->productService->create($account, ProductDTO::fromArray($request->validated()), $imagePath);

        return redirect()->route('products.index')->with('success', 'Producto creado y publicado en Mercado Libre.');
    }

    public function edit(int $id): View
    {
        $product    = $this->productService->find($id);
        abort_if(!$product, 404);
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, int $id): RedirectResponse
    {
        $user    = auth()->user();
        $account = $this->accountRepository->findSelectedByUser($user->id, session('active_meli_account_id'));
        $product = $this->productService->find($id);
        abort_if(!$product, 404);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->getPathname()
            : null;

        $this->productService->update($account, $product, $request->validated(), $imagePath);

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user    = auth()->user();
        $account = $this->accountRepository->findSelectedByUser($user->id, session('active_meli_account_id'));
        $product = $this->productService->find($id);
        abort_if(!$product, 404);

        $this->productService->delete($account, $product);

        return redirect()->route('products.index')->with('success', 'Producto eliminado.');
    }

    public function sync(int $id): RedirectResponse
    {
        $user    = auth()->user();
        $account = $this->accountRepository->findSelectedByUser($user->id, session('active_meli_account_id'));
        $product = $this->productService->find($id);
        abort_if(!$product || !$product->meli_item_id, 404);

        $this->productService->syncFromMeli($account, $product);

        return back()->with('success', 'Producto sincronizado desde Mercado Libre.');
    }
}
