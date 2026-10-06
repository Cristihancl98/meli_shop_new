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
        color:var(--text-strong);
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
    .stat-row .value { font-weight: 600; color:var(--text-strong); }
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
            'active'       => 'Activo',
            'paused'       => 'Pausado',
            'closed'       => 'Finalizado',
            'under_review' => 'En revisión',
            'inactive'     => 'Inactivo',
            default        => $product->status,
        } }}
    </span>
</div>

<div class="row g-4">

    {{-- Imagen y acciones --}}
    <div class="col-lg-4">
        <div class="glass-card p-3 text-center mb-3"
             style="background:var(--overlay-soft);">
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
                    <button type="submit" class="btn-ghost w-100" style="color:var(--success-fg);">
                        <i class="bi bi-arrow-repeat me-1"></i> Sincronizar
                    </button>
                </form>
            @endif
        </div>

        @if($product->permalink)
            <a href="{{ $product->permalink }}" target="_blank" class="btn-ghost w-100 text-center d-block mb-2">
                <i class="bi bi-box-arrow-up-right me-1"></i> Ver en Mercado Libre
            </a>
        @endif

        @can('changeStatus', $product)
            @if($product->meli_item_id)
                <div class="d-flex gap-2 mb-2">
                    @if($product->status === 'active')
                        <form method="POST" action="{{ route('products.pause', $product->id) }}" class="flex-fill">
                            @csrf
                            <button class="btn-ghost w-100" style="color:var(--warning-fg);"><i class="bi bi-pause-circle me-1"></i> Pausar</button>
                        </form>
                    @elseif($product->status === 'paused')
                        <form method="POST" action="{{ route('products.activate', $product->id) }}" class="flex-fill">
                            @csrf
                            <button class="btn-ghost w-100" style="color:var(--success-fg);"><i class="bi bi-play-circle me-1"></i> Activar</button>
                        </form>
                    @endif
                </div>
            @endif
        @endcan

        @can('archive', $product)
            <form method="POST" action="{{ route('products.archive', $product->id) }}" onsubmit="return confirm('¿Archivar este producto? Solo se oculta localmente, no se cierra en Mercado Libre.');">
                @csrf
                <button class="btn-ghost w-100" style="color:var(--danger-fg);"><i class="bi bi-archive me-1"></i> Archivar</button>
            </form>
        @endcan

        @if($product->pictures && count($product->pictures) > 1)
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach($product->pictures as $picture)
                    <img src="{{ $picture }}" width="56" height="56" style="object-fit:contain;border-radius:6px;background:var(--overlay-soft);" alt="">
                @endforeach
            </div>
        @endif
    </div>

    {{-- Datos --}}
    <div class="col-lg-8">

        <div class="glass-card p-4 mb-4">
            <h4 style="color:var(--text-strong);font-weight:700;margin-bottom:20px;line-height:1.3;">{{ $product->title }}</h4>

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
                         style="{{ $product->stock === 0 ? 'color:var(--danger-fg);' : '' }}">
                        {{ $product->stock }}
                        @if($product->stock === 0)
                            <small style="font-size:11px;color:var(--danger-fg);font-weight:400;display:block;">Sin stock</small>
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
                    <div class="stat-row">
                        <span class="label">Categoría MeLi</span>
                        <code class="dark">{{ $product->meli_category_id ?? '—' }}</code>
                    </div>
                    <div class="stat-row">
                        <span class="label">Publicado</span>
                        <span class="value">{{ $product->published_at?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Descripción en MeLi</span>
                        <span class="value" style="color:var({{ $product->description_synced ? '--success-fg' : '--warning-fg' }});">{{ $product->description_synced ? 'Sincronizada' : 'Pendiente' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">SKU</span>
                        <code class="dark">{{ $product->sku ?? '—' }}</code>
                    </div>
                    <div class="stat-row">
                        <span class="label">Precio base (USD)</span>
                        <span class="value">{{ $product->base_price !== null ? number_format($product->base_price, 2, ',', '.') : '—' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Peso (lb)</span>
                        <span class="value">{{ $product->weight ?? '—' }}</span>
                    </div>
                    <div class="stat-row">
                        <span class="label">Vendidos en MeLi</span>
                        <span class="value">{{ $product->sold_quantity }}</span>
                    </div>
                </div>
            </div>
        </div>

        @can('update', $product)
            @if($product->meli_item_id)
                <div class="glass-card p-4 mt-4">
                    <h6 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--neon-purple);margin-bottom:14px;">
                        <i class="bi bi-pencil-square me-1"></i>Editar publicación en Mercado Libre
                    </h6>
                    <form method="POST" action="{{ route('products.listing', $product->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Título</label>
                                <input type="text" name="title" maxlength="60" class="form-control" value="{{ old('title', $product->title) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio (COP)</label>
                                <input type="number" step="1" min="1" name="price" class="form-control" value="{{ old('price', (int) $product->price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio base (USD)</label>
                                <input type="number" step="0.01" min="0" name="base_price" class="form-control" value="{{ old('base_price', $product->base_price) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cantidad</label>
                                <input type="number" min="0" name="quantity" class="form-control" value="{{ old('quantity', $product->stock) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Imágenes (una URL por línea, vacío = sin cambios)</label>
                                <textarea name="pictures" rows="3" class="form-control">{{ old('pictures') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción (vacío = sin cambios; se agrega la plantilla)</label>
                                <textarea name="description" rows="5" class="form-control">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-neon mt-3"><i class="bi bi-cloud-arrow-up me-1"></i> Actualizar en Mercado Libre</button>
                    </form>
                </div>
            @endif
        @endcan

    </div>
</div>

@endsection
