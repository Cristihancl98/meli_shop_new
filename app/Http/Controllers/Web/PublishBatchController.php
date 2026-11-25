<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPublish\BatchItemsActionRequest;
use App\Http\Requests\BulkPublish\StorePublishBatchRequest;
use App\Models\Product;
use App\Models\PublishBatch;
use App\Services\PublishBatchService;
use App\Traits\ResolvesMeliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublishBatchController extends Controller
{
    use ResolvesMeliAccount;

    public function __construct(private readonly PublishBatchService $batchService) {}

    public function index(): View
    {
        $this->authorize('publish', Product::class);

        $account = $this->currentAccount();
        $batches = $account ? $this->batchService->list($account) : null;
        $maxSkus = PublishBatchService::MAX_SKUS;

        return view('publisher.batches.index', compact('account', 'batches', 'maxSkus'));
    }

    public function store(StorePublishBatchRequest $request): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        if (!$account = $this->currentAccount()) {
            return back()->withErrors(['account' => 'Debes vincular una cuenta de Mercado Libre primero.']);
        }

        $batch = $this->batchService->create($account, $request->user(), $request->validated('skus'));

        return redirect()->route('publisher.batches.show', $batch->id)
            ->with('success', "Lote creado con {$batch->items->count()} SKU. Se están buscando en el catálogo.");
    }

    public function show(int $id): View
    {
        $this->authorize('publish', Product::class);

        $account = $this->currentAccount();
        $batch   = $account ? $this->batchService->find($account, $id) : null;
        abort_if(!$batch, 404);

        $counts     = $this->batchService->statusCounts($batch);
        $inProgress = $batch->items->contains(fn ($item) => $item->status->inProgress());

        return view('publisher.batches.show', compact('batch', 'counts', 'inProgress'));
    }

    public function publish(BatchItemsActionRequest $request, int $id): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        $batch = $this->findBatchOrFail($id);
        $count = $this->batchService->publish($batch, $request->user(), $request->validated('items'));

        return redirect()->route('publisher.batches.show', $batch->id)->with(
            $count > 0 ? 'success' : 'error',
            $count > 0 ? "Publicando {$count} artículo(s). El estado de cada uno se actualiza en esta página." : 'Ninguno de los artículos marcados se puede publicar.'
        );
    }

    public function rescrape(BatchItemsActionRequest $request, int $id): RedirectResponse
    {
        $this->authorize('publish', Product::class);

        $batch = $this->findBatchOrFail($id);
        $count = $this->batchService->rescrape($batch, $request->validated('items'));

        return redirect()->route('publisher.batches.show', $batch->id)->with(
            $count > 0 ? 'success' : 'error',
            $count > 0 ? "Buscando de nuevo {$count} SKU." : 'Ninguno de los artículos marcados se puede volver a buscar.'
        );
    }

    private function findBatchOrFail(int $id): PublishBatch
    {
        $account = $this->currentAccount();
        $batch   = $account ? $this->batchService->find($account, $id) : null;
        abort_if(!$batch, 404);

        return $batch;
    }
}
