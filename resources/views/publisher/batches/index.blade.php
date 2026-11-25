@extends('layouts.app')

@section('title', 'Publicación masiva')
@section('page-title', 'Publicador masivo — por SKU')

@section('content')

@include('publisher._tabs')

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <div class="chart-title mb-1">Nuevo lote</div>
            <p style="font-size:12px;color:var(--text-muted);" class="mb-3">
                Pega hasta {{ $maxSkus }} SKU. El sistema los busca en el catálogo y con el scraper; después los revisas y eliges cuáles publicar.
            </p>
            <form method="POST" action="{{ route('publisher.batches.store') }}">
                @csrf
                <label class="form-label" for="batch-codes">SKU</label>
                <textarea id="batch-codes" name="codes" rows="12" class="form-control mb-3" placeholder="Un SKU por línea, o separados por coma o espacio" required>{{ old('codes') }}</textarea>
                <button type="submit" class="btn-neon w-100" @disabled(!$account)><i class="bi bi-search me-1"></i> Crear lote y buscar</button>
                @if(!$account)
                    <p class="mt-2 mb-0" style="font-size:12px;color:var(--danger-fg);">Vincula una cuenta de Mercado Libre para crear lotes.</p>
                @endif
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="glass-card" style="overflow:hidden;">
            <div class="section-header"><h6 class="mb-0"><i class="bi bi-stack me-2"></i>Lotes recientes</h6></div>
            @if(!$batches || $batches->isEmpty())
                <div class="empty-state"><i class="bi bi-stack"></i><p>Todavía no hay lotes. Pega tus SKU para crear el primero.</p></div>
            @else
                <div class="table-responsive">
                    <table class="table dark-table mb-0">
                        <thead>
                            <tr><th>Lote</th><th>Creado</th><th class="text-end">SKU</th><th class="text-end">Publicados</th><th class="text-end">Listos</th><th class="text-end">Con problemas</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                                <tr>
                                    <td><strong style="color:var(--text-strong);">#{{ $batch->id }}</strong>
                                        @if($batch->pending_count > 0)<span class="badge badge-pending ms-1" style="font-size:9px;">En proceso</span>@endif
                                    </td>
                                    <td style="white-space:nowrap;" title="{{ $batch->created_at->format('d/m/Y H:i') }}">{{ $batch->created_at->diffForHumans() }}<br><small style="color:var(--text-muted);">{{ $batch->user?->name }}</small></td>
                                    <td class="text-end">{{ $batch->items_count }}</td>
                                    <td class="text-end" style="color:var(--success-fg);">{{ $batch->published_count }}</td>
                                    <td class="text-end">{{ $batch->ready_count }}</td>
                                    <td class="text-end" style="color:{{ $batch->failed_count ? 'var(--danger-fg)' : 'inherit' }};">{{ $batch->failed_count }}</td>
                                    <td class="text-end"><a href="{{ route('publisher.batches.show', $batch->id) }}" class="btn-ghost text-decoration-none" style="padding:4px 10px;">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($batches && $batches->hasPages())
            <div class="mt-3">{{ $batches->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>

@endsection
