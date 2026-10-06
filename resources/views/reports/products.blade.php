@extends('layouts.app')

@section('title', 'Productos Más Vendidos')
@section('page-title', 'Productos Más Vendidos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="font-size:13px;color:var(--text-muted);margin:0;">Top 20 productos ordenados por unidades vendidas</p>
    @can('export-report')
    <a href="{{ route('reports.export.products') }}" class="btn-ghost" style="color:var(--success-fg);border-color:rgba(52,211,153,.3);">
        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
    </a>
    @endcan
</div>

<div class="glass-card" style="overflow:hidden;">
    @if($products->isEmpty())
    <div class="empty-state">
        <i class="bi bi-trophy"></i>
        <p>Sin datos de ventas aún. Sincroniza tus ventas primero.</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="dark-table">
            <thead>
                <tr>
                    <th style="padding-left:24px;width:60px;">#</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-end">Precio COP</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Uds. Vendidas</th>
                    <th class="text-end">Ingresos COP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $index => $product)
                <tr>
                    <td style="padding-left:24px;">
                        <span class="rank-badge {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                            {{ $index + 1 }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $product->thumbnail ?: 'https://placehold.co/40x40/182844/6b82a0?text=?' }}"
                                 alt="{{ $product->title }}"
                                 style="width:40px;height:40px;object-fit:contain;border-radius:8px;border:1px solid var(--border-card);background:var(--overlay-soft);"
                                 onerror="this.src='https://placehold.co/40x40/182844/6b82a0?text=?'">
                            <div>
                                <div style="font-weight:600;color:var(--text-primary);max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    {{ $product->title }}
                                </div>
                                @if($product->meli_item_id)
                                <small style="color:var(--text-muted);font-size:10px;">{{ $product->meli_item_id }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $product->category?->name ?? '—' }}</td>
                    <td class="text-end" style="font-weight:600;color:var(--text-primary);">${{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span style="{{ $product->stock <= 5 ? 'color:var(--danger-fg);font-weight:700;' : 'color:var(--text-dim);' }}">{{ $product->stock }}</span>
                    </td>
                    <td class="text-center">
                        <span style="font-weight:800;font-size:15px;color:var(--neon-blue);">{{ number_format($product->statistics?->quantity_sold ?? 0) }}</span>
                    </td>
                    <td class="text-end" style="font-weight:700;color:var(--success-fg);">
                        ${{ number_format($product->statistics?->total_revenue ?? 0, 0, ',', '.') }}
                    </td>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="btn-ghost" style="padding:6px 12px;font-size:12px;">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
