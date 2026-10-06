@extends('layouts.app')

@section('title', 'Catálogo')
@section('page-title', 'Publicador masivo — catálogo')

@section('content')

@include('publisher._tabs')

<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('publisher.catalog') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Buscar por</label>
                <select name="mode" class="form-select">
                    <option value="title" @selected($mode === 'title')>Título</option>
                    <option value="category" @selected($mode === 'category')>Categoría MeLi</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Término</label>
                <input type="text" name="q" class="form-control" value="{{ $term }}" placeholder="Ej: audífonos o MCO3697" minlength="2" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn-neon w-100"><i class="bi bi-search me-1"></i> Buscar</button>
            </div>
        </div>
    </form>
</div>

@if($error)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i> {{ $error }}</div>
@endif

@if($term !== '' && !$error)
<div class="glass-card" style="overflow:hidden;">
    <div class="section-header">
        <span>{{ count($results) }} producto(s) en el catálogo</span>
    </div>
    @if(count($results) === 0)
        <div class="empty-state"><p>No se encontraron productos.</p></div>
    @else
    <div class="table-responsive">
        <table class="table dark-table mb-0">
            <thead>
                <tr><th></th><th>Título</th><th>SKU</th><th class="text-end">Precio (USD)</th><th class="text-end">Peso (lb)</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($results as $item)
                    <tr>
                        <td style="width:60px;"><img src="{{ ($item['imagenes'] ?? [])[0] ?? 'https://placehold.co/60x60/0d1733/475569?text=-' }}" width="48" height="48" style="object-fit:contain;border-radius:6px;" alt=""></td>
                        <td>{{ $item['titulo'] ?? '—' }}</td>
                        <td><code class="dark">{{ $item['sku'] ?? '—' }}</code></td>
                        <td class="text-end">{{ isset($item['precio']) ? number_format((float) $item['precio'], 2, ',', '.') : '—' }}</td>
                        <td class="text-end">{{ $item['peso'] ?? '—' }}</td>
                        <td class="text-end">
                            @if(!empty($item['sku']))
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
@endif

@endsection
