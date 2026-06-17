@extends('layouts.app')

@section('title', $product->title)
@section('page-title', 'Detalle del Producto')

@push('styles')
<style>
    .detail-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }
    .detail-value {
        font-size: 15px;
        font-weight: 700;
        color: #fff;
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-glow);
        font-size: 13px;
    }
    .stat-row:last-child { border-bottom: none; }
    .stat-row .label { color: var(--text-muted); }
    .stat-row .value { font-weight: 600; color: #fff; }
    code.dark { background: rgba(0,212,255,.08); color: var(--neon-blue); padding: 2px 8px; border-radius: 5px; font-size: 11px; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('products.index') }}" class="btn-ghost" style="padding:8px 14px;">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
    <span class="badge badge-{{ $product->status }}" style="font-size:12px;padding:6px 14px;">
        {{ match($product->status) {
            'active' => 'Activo',
            'paused' => 'Pausado',
            'closed' => 'Finalizado',
            default  => $product->status,
        } }}
    </span>
</div>

<div class="row g-4">

    {{-- Imagen y acciones --}}
    <div class="col-lg-4">
        <div class="glass-card p-3 text-center mb-3"
             style="background:rgba(255,255,255,.02);">
            <img
                src="{{ $product->thumbnail ?: 'https://placehold.co/400x300/0d1733/475569?text=Sin+imagen' }}"
                alt="{{ $product->title }}"
                class="img-fluid rounded-2"
                style="max-height:280px;object-fit:contain;"
            >
        </div>

        <div class="d-flex gap-2 mb-2">
            <a href="{{ route('products.edit', $product) }}" class="btn-neon flex-fill text-center">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            @if($product->meli_item_id)
                <form method="POST" action="{{ route('products.sync', $product) }}" class="flex-fill">
                    @csrf
                    <button type="submit" class="btn-ghost w-100" style="color:#34d399;">
                        <i class="bi bi-arrow-repeat me-1"></i> Sincronizar
                    </button>
                </form>
            @endif
        </div>

        @if($product->permalink)
            <a href="{{ $product->permalink }}" target="_blank" class="btn-ghost w-100 text-center d-block">
                <i class="bi bi-box-arrow-up-right me-1"></i> Ver en Mercado Libre
            </a>
        @endif
    </div>

    {{-- Datos --}}
    <div class="col-lg-8">

        <div class="glass-card p-4 mb-4">
            <h4 style="color:#fff;font-weight:700;margin-bottom:20px;line-height:1.3;">{{ $product->title }}</h4>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="detail-label">Precio</div>
                    <div class="detail-value" style="font-size:20px;color:var(--neon-blue);">
                        ${{ number_format($product->price, 0, ',', '.') }}
                        <small style="font-size:11px;color:var(--text-muted);">COP</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="detail-label">Stock</div>
                    <div class="detail-value {{ $product->stock === 0 ? '' : '' }}"
                         style="{{ $product->stock === 0 ? 'color:#f87171;' : '' }}">
                        {{ $product->stock }}
                        @if($product->stock === 0)
                            <small style="font-size:11px;color:#f87171;font-weight:400;display:block;">Sin stock</small>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="detail-label">Condición</div>
                    <div class="detail-value">{{ $product->condition === 'new' ? 'Nuevo' : 'Usado' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="detail-label">Categoría</div>
                    <div class="detail-value" style="font-size:13px;">{{ $product->category?->name ?? 'Sin categoría' }}</div>
                </div>
            </div>

            @if($product->description)
                <div style="border-top:1px solid var(--border-glow);padding-top:16px;">
                    <div class="detail-label" style="margin-bottom:8px;">Descripción</div>
                    <p style="font-size:14px;line-height:1.7;color:var(--text-dim);margin:0;">{{ $product->description }}</p>
                </div>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h6 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--neon-green);margin-bottom:14px;">
                        <i class="bi bi-graph-up-arrow me-1"></i>Estadísticas
                    </h6>
                    <div class="stat-row">
                        <span class="label">Unidades vendidas</span>
                        <span class="value">{{ $product->statistics?->quantity_sold ?? 0 }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Total facturado</span>
                        <span class="value" style="color:var(--neon-green);">${{ number_format($product->statistics?->total_revenue ?? 0, 0, ',', '.') }} COP</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Última venta</span>
                        <span class="value">{{ $product->statistics?->last_sale_date?->format('d/m/Y') ?? 'Sin ventas' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h6 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--neon-blue);margin-bottom:14px;">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Info MeLi
                    </h6>
                    <div class="stat-row">
                        <span class="label">ID MeLi</span>
                        <code class="dark">{{ $product->meli_item_id ?? 'Sin publicar' }}</code>
                    </div>
                    <div class="stat-row">
                        <span class="label">Tipo publicación</span>
                        <span class="value">{{ $product->listing_type_id ?? '—' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Última sync</span>
                        <span class="value">{{ $product->last_sync?->diffForHumans() ?? 'Nunca' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
