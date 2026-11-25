@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Gestión de Productos')

@push('styles')
<style>
    .view-toggle { display: inline-flex; border: 1px solid var(--border-glow); border-radius: 10px; overflow: hidden; }
    .view-toggle a {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 14px; font-size: 13px; font-weight: 500;
        color: var(--text-muted); background: var(--control-bg); text-decoration: none;
    }
    .view-toggle a + a { border-left: 1px solid var(--border-glow); }
    .view-toggle a.active { color: var(--text-strong); background: var(--active-bg); }
    .view-toggle a:hover { color: var(--text-strong); }

    .sort-link { color: inherit; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px; }
    .sort-link i { font-size: 11px; opacity: .45; }
    .sort-link:hover { color: var(--text-strong); }
    .sort-link.active { color: var(--neon-blue); }
    .sort-link.active i { opacity: 1; }

    .products-table .product-thumb { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; background: var(--overlay-soft); }
    .products-table .product-cell { min-width: 220px; max-width: 360px; }
    .products-table .product-cell-title {
        display: block; color: var(--text-primary); font-weight: 600; text-decoration: none;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .products-table .product-cell-title:hover { color: var(--neon-blue); }
    .products-table .product-cell small { font-size: 11px; color: var(--text-muted); }
    .products-table .num { font-variant-numeric: tabular-nums; }
    .products-table .stock-low { color: var(--warning-fg); font-weight: 700; }

    .import-status {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 14px; border-radius: 10px; font-size: 13px;
        border: 1px solid var(--border-glow); background: var(--hover-bg); color: var(--text-dim);
    }
    .import-status i { margin-top: 1px; }
    .import-pending, .import-running { color: var(--info-fg); background: rgba(59,130,246,.08); border-color: rgba(59,130,246,.2); }
    .import-success { color: var(--success-fg); background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.2); }
    .import-error   { color: var(--danger-fg); background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.2); }
    .btn-ghost:disabled { opacity: .6; cursor: progress; }
    .spin { display: inline-block; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
</style>
@endpush

@if($importStatus?->inProgress())
    @push('scripts')
    <script>setTimeout(() => location.reload(), 15000);</script>
    @endpush
@endif

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="font-size:13px;color:var(--text-muted);margin:0;">
        {{ $products->total() }} producto(s) encontrado(s)
    </p>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <nav class="view-toggle" aria-label="Tipo de vista">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'cards']) }}" class="{{ $viewMode === 'cards' ? 'active' : '' }}" @if($viewMode === 'cards') aria-current="page" @endif>
                <i class="bi bi-grid-3x3-gap"></i> Tarjetas
            </a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="{{ $viewMode === 'table' ? 'active' : '' }}" @if($viewMode === 'table') aria-current="page" @endif>
                <i class="bi bi-table"></i> Tabla
            </a>
        </nav>
        @if($account)
            @can('syncAll', \App\Models\Product::class)
                <form method="POST" action="{{ route('products.sync-all') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn-ghost" @disabled($importStatus?->inProgress())
                            title="Descarga a este panel las publicaciones de tu cuenta de Mercado Libre">
                        <i class="bi bi-cloud-download me-1 {{ $importStatus?->inProgress() ? 'spin' : '' }}"></i>
                        {{ $importStatus?->inProgress() ? 'Sincronizando…' : 'Sincronizar con Mercado Libre' }}
                    </button>
                </form>
            @endcan
        @endif
        <a href="{{ route('products.create') }}" class="btn-neon">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
        </a>
    </div>
</div>

@if($importStatus)
    <div class="import-status import-{{ $importStatus->state }} mb-4" role="status">
        @switch($importStatus->state)
            @case('pending')
                <i class="bi bi-hourglass-split"></i>
                <span>Sincronización en cola desde {{ $importStatus->at->diffForHumans() }}.
                    @if($importStatus->waitingTooLong)
                        Está tardando en empezar: revisa que el procesador de tareas (<code>queue:work</code>) esté activo.
                    @endif
                </span>
                @break
            @case('running')
                <i class="bi bi-arrow-repeat spin"></i>
                <span>Descargando publicaciones desde Mercado Libre (inició {{ $importStatus->at->diffForHumans() }}). Esta página se actualiza sola.</span>
                @break
            @case('success')
                <i class="bi bi-check-circle"></i>
                <span>Última sincronización {{ $importStatus->at->diffForHumans() }}: {{ $importStatus->message }}.</span>
                @break
            @default
                <i class="bi bi-exclamation-triangle"></i>
                <span>La última sincronización falló ({{ $importStatus->at->diffForHumans() }}): {{ $importStatus->message }}</span>
        @endswitch
    </div>
@endif

{{-- FILTERS --}}
<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('products.index') }}">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label" for="f-search">Buscar</label>
                <input type="text" id="f-search" name="search" class="form-control"
                    placeholder="Nombre del producto..." value="{{ request('search') }}">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="f-status">Estado</label>
                <select id="f-status" name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Activo</option>
                    <option value="paused"  {{ request('status') === 'paused'  ? 'selected' : '' }}>Pausado</option>
                    <option value="closed"  {{ request('status') === 'closed'  ? 'selected' : '' }}>Finalizado</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="f-category">Categoría</label>
                <select id="f-category" name="category_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <label class="form-label" for="f-min">Precio mín. (COP)</label>
                <input type="number" id="f-min" name="min_price" class="form-control"
                    placeholder="0" value="{{ request('min_price') }}">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label" for="f-max">Precio máx. (COP)</label>
                <input type="number" id="f-max" name="max_price" class="form-control"
                    placeholder="Sin límite" value="{{ request('max_price') }}">
            </div>
            <div class="col-7 col-md-4 col-lg-3">
                <label class="form-label" for="f-sort">Ordenar por</label>
                <select id="f-sort" name="sort" class="form-select">
                    @foreach($sorts as $option)
                        <option value="{{ $option->value }}" {{ $sort === $option ? 'selected' : '' }}>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-5 col-md-4 col-lg-2">
                <label class="form-label" for="f-direction">Orden</label>
                <select id="f-direction" name="direction" class="form-select">
                    <option value="desc" {{ $direction === 'desc' ? 'selected' : '' }}>Descendente</option>
                    <option value="asc"  {{ $direction === 'asc'  ? 'selected' : '' }}>Ascendente</option>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn-neon flex-fill" style="padding:9px;" title="Aplicar filtros">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('products.index', ['view' => $viewMode]) }}" class="btn-ghost flex-fill" style="padding:9px;text-align:center;" title="Limpiar filtros">
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

@if($products->isEmpty())
    <div class="glass-card empty-state">
        <i class="bi bi-box-seam"></i>
        <p>No se encontraron productos con los filtros aplicados.</p>
        @if(request()->hasAny(['search','status','category_id','min_price','max_price']))
            <a href="{{ route('products.index', ['view' => $viewMode]) }}" class="btn-ghost mt-2" style="display:inline-block;padding:8px 16px;">Limpiar filtros</a>
        @elseif($account && !$importStatus?->inProgress())
            @can('syncAll', \App\Models\Product::class)
                <p class="mt-2">¿Ya tienes publicaciones en Mercado Libre? Descárgalas a este panel.</p>
                <form method="POST" action="{{ route('products.sync-all') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn-neon"><i class="bi bi-cloud-download me-1"></i> Sincronizar con Mercado Libre</button>
                </form>
            @endcan
        @endif
    </div>
@else
    @include($viewMode === 'table' ? 'products._table' : 'products._cards')

    <div class="d-flex justify-content-between align-items-center mt-4">
        <p style="font-size:12px;color:var(--text-muted);margin:0;">
            Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }}
            de {{ $products->total() }} productos
        </p>
        {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
@endif

@endsection
