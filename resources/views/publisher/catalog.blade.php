@extends('layouts.app')

@section('title', 'Catálogo')
@section('page-title', 'Publicador masivo — catálogo')

@push('styles')
<style>
    .catalog-table .item-thumb { width: 48px; height: 48px; object-fit: contain; border-radius: 6px; background: var(--overlay-soft); }
    .catalog-table .item-title { min-width: 240px; max-width: 420px; }
    .catalog-table .item-title span { display: block; color: var(--text-primary); font-weight: 600; }
    .catalog-table .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .title-length { font-size: 10px; font-variant-numeric: tabular-nums; color: var(--text-muted); }
    .title-length.over { color: var(--danger-fg); font-weight: 700; }
    .missing-list { font-size: 11px; color: var(--danger-fg); }
    .catalog-table input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
    .catalog-table input[type=checkbox]:disabled { cursor: not-allowed; opacity: .3; }
    #q-wrap[hidden] { display: none !important; }
</style>
@endpush

@section('content')

@include('publisher._tabs')

<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('publisher.catalog') }}" id="catalog-search">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label" for="c-mode">Buscar</label>
                <select name="mode" id="c-mode" class="form-select">
                    <option value="recent" @selected($criteria->mode === 'recent')>Recientes insertados</option>
                    <option value="title" @selected($criteria->mode === 'title')>Por título</option>
                    <option value="category" @selected($criteria->mode === 'category')>Por categoría MeLi</option>
                </select>
            </div>
            <div class="col-12 col-md-8 col-lg-5" id="q-wrap">
                <label class="form-label" for="c-q">Término</label>
                <input type="text" name="q" id="c-q" class="form-control" value="{{ $criteria->term }}" placeholder="Ej: audífonos o MCO3697" minlength="2">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="c-min-price">Precio mín. (USD)</label>
                <input type="number" step="0.01" min="0" name="min_price" id="c-min-price" class="form-control" value="{{ $criteria->minPrice }}" placeholder="0">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="c-max-price">Precio máx. (USD)</label>
                <input type="number" step="0.01" min="0" name="max_price" id="c-max-price" class="form-control" value="{{ $criteria->maxPrice }}" placeholder="Sin límite">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="c-min-weight">Peso mín. (lb)</label>
                <input type="number" step="0.01" min="0" name="min_weight" id="c-min-weight" class="form-control" value="{{ $criteria->minWeight }}" placeholder="0">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label" for="c-max-weight">Peso máx. (lb)</label>
                <input type="number" step="0.01" min="0" name="max_weight" id="c-max-weight" class="form-control" value="{{ $criteria->maxWeight }}" placeholder="Sin límite">
            </div>
            <div class="col-12 col-md-6 col-lg-5 d-flex align-items-center">
                <input type="hidden" name="only_publishable" value="0">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="only_publishable" value="1" id="c-publishable" @checked($criteria->onlyPublishable)>
                    <label class="form-check-label" for="c-publishable" style="font-size:13px;color:var(--text-dim);">
                        Solo publicables <span style="color:var(--text-muted);">(precio, peso, título ≤ {{ $titleLimit }} caracteres e imágenes)</span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <button type="submit" class="btn-neon w-100"><i class="bi bi-search me-1"></i> Buscar</button>
            </div>
        </div>
    </form>
</div>

@if($error)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i> {{ $error }}</div>
@endif

@if($result)
<form method="POST" action="{{ route('publisher.batches.store') }}" id="catalog-batch">
    @csrf
    <div class="glass-card" style="overflow:hidden;">
        <div class="section-header">
            <div>
                <h6 class="mb-1">{{ count($result->items) }} producto(s){{ $criteria->onlyPublishable ? ' publicables' : '' }}</h6>
                <span style="font-size:12px;color:var(--text-muted);">
                    Revisados {{ number_format($result->scanned, 0, ',', '.') }} del catálogo, del más reciente al más antiguo.
                    @if($result->limitReached) Se muestran los primeros {{ $maxResults }}: ajusta los filtros para afinar. @endif
                </span>
            </div>
            @if(count($result->items) > 0)
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn-ghost" id="select-publishable" style="padding:6px 12px;"><i class="bi bi-check2-all me-1"></i> Marcar publicables</button>
                    <button type="submit" class="btn-neon" id="create-batch" disabled>
                        <i class="bi bi-stack me-1"></i> Crear lote con marcados (<span id="selected-count">0</span>)
                    </button>
                </div>
            @endif
        </div>

        @if(count($result->items) === 0)
            <div class="empty-state"><i class="bi bi-search"></i><p>Ningún producto cumple los filtros.</p></div>
        @else
        <div class="table-responsive">
            <table class="table dark-table mb-0 catalog-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:60px;"></th>
                        <th>Título</th>
                        <th>SKU</th>
                        <th class="text-end">Precio USD</th>
                        <th class="text-end">Precio COP</th>
                        <th class="text-end">Peso (lb)</th>
                        <th class="text-center">Imágenes</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($result->items as $item)
                        @php($selectable = $item['publishable'] && !$item['already_published'] && $item['sku'] !== '')
                        <tr>
                            <td>
                                <input type="checkbox" name="skus[]" value="{{ $item['sku'] }}" class="item-check" aria-label="Marcar {{ $item['sku'] }}" @disabled(!$selectable)>
                            </td>
                            <td><img src="{{ $item['images'][0] ?? 'https://placehold.co/60x60/0d1733/475569?text=-' }}" class="item-thumb" alt="" loading="lazy"></td>
                            <td class="item-title">
                                <span>{{ $item['title'] !== '' ? $item['title'] : '—' }}</span>
                                <span class="title-length {{ $item['title_length'] > $titleLimit ? 'over' : '' }}">{{ $item['title_length'] }}/{{ $titleLimit }} caracteres</span>
                            </td>
                            <td><code class="dark">{{ $item['sku'] !== '' ? $item['sku'] : '—' }}</code></td>
                            <td class="text-end num">{{ $item['price_usd'] !== null ? number_format($item['price_usd'], 2, ',', '.') : '—' }}</td>
                            <td class="text-end num">@if($item['final_price_cop'])<strong style="color:var(--text-strong);">${{ number_format($item['final_price_cop'], 0, ',', '.') }}</strong>@else — @endif</td>
                            <td class="text-end num">{{ $item['weight_lb'] !== null ? rtrim(rtrim(number_format($item['weight_lb'], 2, ',', '.'), '0'), ',') : '—' }}</td>
                            <td class="text-center num">{{ count($item['images']) }}</td>
                            <td style="min-width:150px;">
                                @if($item['already_published'])
                                    <span class="badge badge-paused" style="font-size:10px;">Ya publicado</span>
                                @elseif($item['publishable'])
                                    <span class="badge badge-active" style="font-size:10px;">Publicable</span>
                                @else
                                    <span class="badge badge-cancelled" style="font-size:10px;">Incompleto</span>
                                    <div class="missing-list mt-1">Falta: {{ implode(', ', $item['missing']) }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($item['sku'] !== '' && !$item['already_published'])
                                    <a href="{{ route('publisher.create', ['sku' => $item['sku']]) }}" class="btn-ghost text-nowrap text-decoration-none d-inline-flex align-items-center gap-1" style="padding:6px 12px;"><i class="bi bi-cloud-upload"></i> Publicar</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</form>
@endif

@endsection

@push('scripts')
<script>
    (function () {
        const mode  = document.getElementById('c-mode');
        const qWrap = document.getElementById('q-wrap');
        const q     = document.getElementById('c-q');
        const syncMode = () => {
            const recent = mode.value === 'recent';
            qWrap.hidden = recent;
            q.required = !recent;
            q.disabled = recent;
        };
        mode.addEventListener('change', syncMode);
        syncMode();

        const checks  = () => Array.from(document.querySelectorAll('.item-check:not(:disabled)'));
        const counter = document.getElementById('selected-count');
        const submit  = document.getElementById('create-batch');
        if (!submit) return;

        const refresh = () => {
            const selected = checks().filter((c) => c.checked).length;
            counter.textContent = selected;
            submit.disabled = selected === 0;
        };
        document.querySelectorAll('.item-check').forEach((c) => c.addEventListener('change', refresh));
        document.getElementById('select-publishable').addEventListener('click', () => {
            const limit = {{ \App\Services\PublishBatchService::MAX_SKUS }};
            checks().forEach((c, i) => { c.checked = i < limit; });
            refresh();
        });
        refresh();
    })();
</script>
@endpush
