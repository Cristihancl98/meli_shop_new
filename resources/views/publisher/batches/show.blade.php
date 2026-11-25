@extends('layouts.app')

@section('title', "Lote #{$batch->id}")
@section('page-title', "Publicador masivo — lote #{$batch->id}")

@push('styles')
<style>
    .batch-chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .batch-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 999px; font-size: 12px;
        border: 1px solid var(--border-glow); background: var(--control-bg); color: var(--text-dim);
    }
    .batch-chip strong { color: var(--text-strong); font-variant-numeric: tabular-nums; }
    .batch-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .batch-table .item-thumb { width: 44px; height: 44px; object-fit: contain; border-radius: 8px; background: var(--overlay-soft); }
    .batch-table .item-title { min-width: 220px; max-width: 340px; }
    .batch-table .item-title span { display: block; color: var(--text-primary); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .batch-table .item-title small { color: var(--text-muted); font-size: 11px; }
    .batch-table .item-error { min-width: 220px; max-width: 380px; font-size: 12px; color: var(--danger-fg); white-space: normal; }
    .batch-table .num { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .batch-table tr.is-published td { opacity: .75; }
    .batch-table input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
    .batch-table input[type=checkbox]:disabled { cursor: not-allowed; opacity: .3; }
    .spin { display: inline-block; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .spin { animation: none; } }
</style>
@endpush

@section('content')

@include('publisher._tabs')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('publisher.batches.index') }}" style="font-size:13px;color:var(--text-muted);text-decoration:none;"><i class="bi bi-arrow-left me-1"></i> Lotes</a>
        <div style="font-size:12px;color:var(--text-muted);" class="mt-1">
            Creado {{ $batch->created_at->format('d/m/Y H:i') }}@if($batch->user) por {{ $batch->user->name }}@endif · {{ $batch->items->count() }} SKU
        </div>
    </div>
    @if($inProgress)
        <span style="font-size:12px;color:var(--info-fg);"><i class="bi bi-arrow-repeat spin me-1"></i> Procesando… la página se actualiza sola</span>
    @endif
</div>

<div class="batch-chips mb-4">
    @foreach(\App\Enums\BatchItemStatus::cases() as $status)
        @if(($counts[$status->value] ?? 0) > 0)
            <span class="batch-chip"><span class="badge badge-{{ $status->badge() }}" style="font-size:9px;">&nbsp;</span>{{ $status->label() }} <strong>{{ $counts[$status->value] }}</strong></span>
        @endif
    @endforeach
</div>

<form method="POST" action="{{ route('publisher.batches.publish', $batch->id) }}" id="batch-form">
    @csrf
    <div class="glass-card" style="overflow:hidden;">
        <div class="section-header">
            <div class="batch-toolbar">
                <button type="button" class="btn-ghost" id="select-ready" style="padding:6px 12px;"><i class="bi bi-check2-all me-1"></i> Marcar listos</button>
                <button type="button" class="btn-ghost" id="select-none" style="padding:6px 12px;">Desmarcar</button>
            </div>
            <div class="batch-toolbar">
                <button type="submit" formaction="{{ route('publisher.batches.rescrape', $batch->id) }}" class="btn-ghost" data-needs-selection style="padding:6px 12px;"
                        title="Vuelve a buscar en el catálogo/scraper los marcados que no se encontraron o fallaron">
                    <i class="bi bi-arrow-clockwise me-1"></i> Buscar de nuevo
                </button>
                <button type="submit" class="btn-neon" data-needs-selection>
                    <i class="bi bi-cloud-upload me-1"></i> Publicar marcados (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table dark-table mb-0 batch-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="select-all" aria-label="Marcar todos"></th>
                        <th style="width:56px;"></th>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th class="text-end">Precio COP</th>
                        <th class="text-end">Cant.</th>
                        <th>Marca</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batch->items as $item)
                        @php($selectable = $item->status->canPublish() || $item->status->canRescrape())
                        <tr class="{{ $item->status === \App\Enums\BatchItemStatus::Published ? 'is-published' : '' }}">
                            <td>
                                <input type="checkbox" name="items[]" value="{{ $item->id }}" class="item-check" aria-label="Marcar {{ $item->sku }}"
                                       data-status="{{ $item->status->value }}" @disabled(!$selectable)>
                            </td>
                            <td>
                                @if($item->thumbnail())
                                    <img src="{{ $item->thumbnail() }}" alt="" class="item-thumb" loading="lazy">
                                @endif
                            </td>
                            <td><code class="dark">{{ $item->sku }}</code></td>
                            <td class="item-title">
                                <span title="{{ $item->title() }}">{{ $item->title() ?? '—' }}</span>
                                @if($item->data['suggested_category'] ?? null)
                                    <small>Categoría {{ $item->data['suggested_category'] }}</small>
                                @endif
                            </td>
                            <td class="text-end num">
                                @if(isset($item->data['final_price']))<strong style="color:var(--text-strong);">${{ number_format($item->data['final_price'], 0, ',', '.') }}</strong>@else — @endif
                            </td>
                            <td class="text-end num">{{ $item->data['default_quantity'] ?? '—' }}</td>
                            <td>{{ $item->data['marca'] ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                <span class="badge badge-{{ $item->status->badge() }}" style="font-size:10px;">
                                    @if($item->status->inProgress())<i class="bi bi-arrow-repeat spin me-1"></i>@endif{{ $item->status->label() }}
                                </span>
                                @if($item->attempts > 1)<br><small style="color:var(--text-muted);font-size:10px;">{{ $item->attempts }} intentos</small>@endif
                            </td>
                            <td class="item-error">
                                @if($item->status === \App\Enums\BatchItemStatus::Published)
                                    <a href="{{ $item->meliUrl() }}" target="_blank" rel="noopener noreferrer" class="meli-link">{{ $item->meli_item_id }}<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                                @elseif($item->error)
                                    {{ $item->error }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if(in_array($item->status, [\App\Enums\BatchItemStatus::Ready, \App\Enums\BatchItemStatus::Failed, \App\Enums\BatchItemStatus::NotFound], true))
                                    <a href="{{ route('publisher.create', ['sku' => $item->sku, 'batch_item' => $item->id]) }}" class="btn-ghost text-decoration-none" style="padding:4px 10px;white-space:nowrap;" title="Abrir en el publicador individual para corregir y publicar">
                                        <i class="bi bi-pencil-square me-1"></i>Corregir
                                    </a>
                                @elseif($item->product_id)
                                    <a href="{{ route('products.show', $item->product_id) }}" class="btn-ghost text-decoration-none" style="padding:4px 10px;" title="Ver producto"><i class="bi bi-eye"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    (function () {
        const checks  = () => Array.from(document.querySelectorAll('.item-check:not(:disabled)'));
        const counter = document.getElementById('selected-count');
        const all     = document.getElementById('select-all');

        const refresh = () => {
            const selected = checks().filter((c) => c.checked).length;
            counter.textContent = selected;
            document.querySelectorAll('[data-needs-selection]').forEach((b) => { b.disabled = selected === 0; });
            all.checked = selected > 0 && selected === checks().length;
            all.indeterminate = selected > 0 && selected < checks().length;
        };

        document.querySelectorAll('.item-check').forEach((c) => c.addEventListener('change', refresh));
        all.addEventListener('change', () => { checks().forEach((c) => { c.checked = all.checked; }); refresh(); });
        document.getElementById('select-ready').addEventListener('click', () => {
            checks().forEach((c) => { c.checked = c.dataset.status === 'ready'; });
            refresh();
        });
        document.getElementById('select-none').addEventListener('click', () => { checks().forEach((c) => { c.checked = false; }); refresh(); });
        refresh();

        @if($inProgress)
        setInterval(() => {
            if (!checks().some((c) => c.checked)) location.reload();
        }, 10000);
        @endif
    })();
</script>
@endpush
