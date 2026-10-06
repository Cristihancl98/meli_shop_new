@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Gestión de Productos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="font-size:13px;color:var(--text-muted);margin:0;">
        {{ $products->total() }} producto(s) encontrado(s)
    </p>
    <a href="{{ route('products.create') }}" class="btn-neon">
        <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
    </a>
</div>

{{-- FILTERS --}}
<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('products.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control"
                    placeholder="Nombre del producto..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Activo</option>
                    <option value="paused"  {{ request('status') === 'paused'  ? 'selected' : '' }}>Pausado</option>
                    <option value="closed"  {{ request('status') === 'closed'  ? 'selected' : '' }}>Finalizado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Categoría</label>
                <select name="category_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Precio mín. (COP)</label>
                <input type="number" name="min_price" class="form-control"
                    placeholder="0" value="{{ request('min_price') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Precio máx. (COP)</label>
                <input type="number" name="max_price" class="form-control"
                    placeholder="Sin límite" value="{{ request('max_price') }}">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn-neon flex-fill" style="padding:9px;">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('products.index') }}" class="btn-ghost flex-fill" style="padding:9px;text-align:center;">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- NO ACCOUNT WARNING --}}
@if(!$account)
    <div class="flash-alert flash-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No tienes una cuenta de Mercado Libre vinculada.
        <a href="{{ route('meli.connect') }}" style="color:var(--neon-blue);margin-left:6px;">Conectar ahora →</a>
    </div>
@endif

{{-- PRODUCTS GRID --}}
@if($products->isEmpty())
    <div class="glass-card empty-state">
        <i class="bi bi-box-seam"></i>
        <p>No se encontraron productos con los filtros aplicados.</p>
        @if(request()->hasAny(['search','status','category_id','min_price','max_price']))
            <a href="{{ route('products.index') }}" class="btn-ghost mt-2" style="display:inline-block;padding:8px 16px;">Limpiar filtros</a>
        @endif
    </div>
@else
    <div class="row g-3">
        @foreach($products as $product)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <img
                        src="{{ $product->thumbnail ?: 'https://placehold.co/300x180/0d1733/475569?text=Sin+imagen' }}"
                        alt="{{ $product->title }}"
                        class="product-img"
                        onerror="this.src='https://placehold.co/300x180/0d1733/475569?text=Sin+imagen'"
                    >
                    <div class="product-body">
                        <p class="product-title" title="{{ $product->title }}">{{ $product->title }}</p>

                        <div class="product-price">
                            ${{ number_format($product->price, 0, ',', '.') }}
                            <small style="font-size:10px;color:var(--text-muted);font-weight:400;">COP</small>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="product-meta">
                                <i class="bi bi-box me-1"></i>{{ $product->stock }} en stock
                            </span>
                            <span class="badge badge-{{ $product->status }}" style="font-size:9px;">
                                {{ match($product->status) {
                                    'active' => 'Activo',
                                    'paused' => 'Pausado',
                                    'closed' => 'Finalizado',
                                    default  => ucfirst($product->status),
                                } }}
                            </span>
                        </div>

                        <p class="product-meta mb-3" style="font-size:11px;">
                            <i class="bi bi-clock me-1"></i>
                            {{ $product->updated_at->diffForHumans() }}
                        </p>

                        <div class="d-flex gap-1">
                            <a href="{{ route('products.show', $product) }}"
                               class="btn-ghost flex-fill text-center" style="padding:7px;font-size:13px;" title="Ver">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $product) }}"
                               class="btn-ghost flex-fill text-center" style="padding:7px;font-size:13px;color:var(--neon-blue);" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($product->meli_item_id)
                                <form method="POST" action="{{ route('products.sync', $product) }}" class="flex-fill">
                                    @csrf
                                    <button type="submit" class="btn-ghost w-100" style="padding:7px;font-size:13px;color:var(--success-fg);" title="Sincronizar">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4">
        <p style="font-size:12px;color:var(--text-muted);margin:0;">
            Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }}
            de {{ $products->total() }} productos
        </p>
        {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
@endif

@endsection
