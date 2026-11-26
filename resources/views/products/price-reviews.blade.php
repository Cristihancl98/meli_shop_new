@extends('layouts.app')

@section('title', 'Revisión de precios')
@section('page-title', 'Revisión de precios (Amazon)')

@push('styles')
<style>
    .review-table .item-thumb { width: 44px; height: 44px; object-fit: contain; border-radius: 8px; background: var(--overlay-soft); }
    .review-table .item-title { min-width: 220px; max-width: 320px; }
    .review-table .item-title span { display: block; color: var(--text-primary); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .review-table .item-detail { min-width: 260px; max-width: 420px; font-size: 12px; color: var(--text-dim); white-space: normal; }
    .review-table .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rules { font-size: 13px; color: var(--text-muted); margin: 0; padding-left: 1.1em; display: grid; gap: 2px; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <p style="font-size:13px;color:var(--text-dim);" class="mb-2">Cada 30 minutos el sistema compara tus publicaciones activas con el precio y el estado de Amazon (datos del scraper):</p>
        <ul class="rules">
            <li><strong>Amazon sube:</strong> se recalcula el precio y se actualiza solo en Mercado Libre.</li>
            <li><strong>Amazon baja:</strong> no se toca; queda aquí para que decidas.</li>
            <li><strong>Ya no existe en Amazon:</strong> se pausa en Mercado Libre y queda aquí. No se reactiva sola.</li>
        </ul>
    </div>
    @if($account)
        @can('syncAll', \App\Models\Product::class)
            <form method="POST" action="{{ route('products.price-reviews.run') }}" class="m-0">
                @csrf
                <button type="submit" class="btn-ghost"><i class="bi bi-arrow-repeat me-1"></i> Revisar precios ahora</button>
            </form>
        @endcan
    @endif
</div>

@if(!$account)
    <div class="flash-alert flash-danger"><i class="bi bi-exclamation-triangle-fill"></i> Vincula una cuenta de Mercado Libre para revisar precios.</div>
@elseif($reviews->isEmpty())
    <div class="glass-card empty-state"><i class="bi bi-check2-circle"></i><p>No hay publicaciones pendientes de revisión.</p></div>
@else
    <div class="glass-card" style="overflow:hidden;">
        <div class="section-header"><h6 class="mb-0"><i class="bi bi-exclamation-diamond me-2"></i>{{ $reviews->total() }} publicación(es) para revisar</h6></div>
        <div class="table-responsive">
            <table class="table dark-table mb-0 review-table">
                <thead>
                    <tr>
                        <th style="width:56px;"></th>
                        <th>Producto</th>
                        <th>Motivo</th>
                        <th class="text-end">Precio actual</th>
                        <th class="text-end">Amazon (USD)</th>
                        <th class="text-end">Sugerido</th>
                        <th>Marcado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $product)
                        <tr>
                            <td>@if($product->thumbnail)<img src="{{ $product->thumbnail }}" alt="" class="item-thumb" loading="lazy">@endif</td>
                            <td class="item-title">
                                <span title="{{ $product->title }}">{{ $product->title }}</span>
                                <small>@include('products._meli-link') · <code class="dark">{{ $product->sku }}</code></small>
                            </td>
                            <td class="item-detail">
                                <span class="badge badge-{{ $product->review_reason?->badge() }}" style="font-size:10px;">{{ $product->review_reason?->label() }}</span>
                                <div class="mt-1">{{ $product->review_detail }}</div>
                            </td>
                            <td class="text-end num"><strong style="color:var(--text-strong);">${{ number_format((float) $product->price, 0, ',', '.') }}</strong><br><span class="badge badge-{{ $product->status }}" style="font-size:9px;">{{ $product->status_label }}</span></td>
                            <td class="text-end num">
                                {{ number_format((float) $product->base_price, 2, ',', '.') }}
                                @if($product->review_amazon_price)<br><span style="color:var(--warning-fg);">→ {{ number_format((float) $product->review_amazon_price, 2, ',', '.') }}</span>@endif
                            </td>
                            <td class="text-end num">@if($suggested[$product->id] ?? null)${{ number_format($suggested[$product->id], 0, ',', '.') }}@else — @endif</td>
                            <td style="white-space:nowrap;" title="{{ $product->review_flagged_at?->format('d/m/Y H:i') }}">{{ $product->review_flagged_at?->diffForHumans() }}</td>
                            <td class="text-end" style="white-space:nowrap;">
                                @can('update', $product)
                                    @if(($suggested[$product->id] ?? null) && $product->review_reason === \App\Enums\PriceReviewReason::PriceDrop)
                                        <form method="POST" action="{{ route('products.price-review.apply', $product->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-neon" style="padding:5px 10px;font-size:12px;" title="Actualiza el precio en Mercado Libre al sugerido">Aplicar sugerido</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('products.price-review.dismiss', $product->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-ghost" style="padding:5px 10px;font-size:12px;" title="Mantener el precio actual y quitar de la lista">
                                            {{ $product->review_reason === \App\Enums\PriceReviewReason::PriceDrop ? 'Mantener precio' : 'Marcar revisado' }}
                                        </button>
                                    </form>
                                @endcan
                                <a href="{{ route('products.show', $product->id) }}" class="btn-ghost text-decoration-none" style="padding:5px 9px;" title="Ver producto"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($reviews->hasPages())
        <div class="mt-3">{{ $reviews->links('pagination::bootstrap-5') }}</div>
    @endif
@endif

@endsection
