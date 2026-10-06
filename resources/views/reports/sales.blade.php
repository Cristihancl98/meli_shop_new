@extends('layouts.app')

@section('title', 'Reporte de Ventas')
@section('page-title', 'Reporte de Ventas')

@section('content')

{{-- FILTROS --}}
<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('reports.sales') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn-neon w-100" style="padding:10px;">
                    <i class="bi bi-search me-1"></i>Generar
                </button>
            </div>
            @can('export-report')
            <div class="col-12 col-md-4 d-flex gap-2 justify-content-end">
                <a href="{{ route('reports.export.sales', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                   class="btn-ghost" style="color:var(--success-fg);border-color:rgba(52,211,153,.3);">
                    <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                </a>
            </div>
            @endcan
        </div>
    </form>
</div>

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-blue"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <div class="metric-label">Total órdenes</div>
                    <div class="metric-value">{{ number_format($total_orders) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-green"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="metric-label">Órdenes pagadas</div>
                    <div class="metric-value" style="color:var(--success-fg);">{{ number_format($paid_orders) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-teal"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="metric-label">Ingresos COP</div>
                    <div class="metric-value" style="font-size:19px;">${{ number_format($total_revenue, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-purple"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="metric-label">Ticket promedio</div>
                    <div class="metric-value" style="font-size:19px;">
                        ${{ $paid_orders > 0 ? number_format($total_revenue / $paid_orders, 0, ',', '.') : '0' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TABLA --}}
<div class="glass-card" style="overflow:hidden;">
    <div class="section-header">
        <h6>
            <i class="bi bi-calendar-range me-2" style="color:var(--neon-blue);"></i>
            Ventas del {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        </h6>
        <span style="font-size:12px;color:var(--text-muted);">{{ $rows->count() }} {{ $rows->count() === 1 ? 'registro' : 'registros' }}</span>
    </div>

    @if($rows->isEmpty())
    <div class="empty-state">
        <i class="bi bi-bar-chart-line"></i>
        <p>Sin ventas en el rango de fechas seleccionado</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="dark-table">
            <thead>
                <tr>
                    <th style="padding-left:24px;">#</th>
                    <th>Cliente</th>
                    <th>Productos</th>
                    <th class="text-end">Total COP</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $order)
                @php
                $sMap = ['paid'=>['class'=>'badge-paid','l'=>'Pagado'],'pending'=>['class'=>'badge-pending','l'=>'Pendiente'],'cancelled'=>['class'=>'badge-cancelled','l'=>'Cancelado']];
                $s = $sMap[$order->status] ?? ['class'=>'badge-pending','l'=>$order->status];
                @endphp
                <tr>
                    <td style="padding-left:24px;font-weight:700;color:var(--text-strong);">#{{ $order->id }}</td>
                    <td>{{ $order->customer?->nickname ?: ($order->customer?->name ?: '—') }}</td>
                    <td>
                        <span style="background:rgba(0,212,255,.08);color:var(--neon-blue);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">
                            {{ $order->items->count() }}
                        </span>
                    </td>
                    <td class="text-end" style="font-weight:700;color:var(--text-strong);">${{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $s['class'] }}" style="font-size:10px;padding:4px 10px;">{{ $s['l'] }}</span></td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $order->order_date?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="btn-ghost" style="padding:6px 12px;font-size:12px;">
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
