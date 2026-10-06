@extends('layouts.app')

@section('title', 'Ventas')
@section('page-title', 'Ventas')

@section('content')

{{-- STATS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-blue"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <div class="metric-label">Total ventas</div>
                    <div class="metric-value">{{ number_format($orders->total()) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-purple"><i class="bi bi-layers"></i></div>
                <div>
                    <div class="metric-label">Esta página</div>
                    <div class="metric-value">{{ $orders->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-teal"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="metric-label">Página actual</div>
                    <div class="metric-value">{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 d-flex align-items-center">
        @can('sync', \App\Models\Order::class)
        <form method="POST" action="{{ route('orders.sync') }}" class="w-100">
            @csrf
            <button type="submit" class="btn-neon w-100">
                <i class="bi bi-arrow-repeat me-2"></i>Sincronizar MeLi
            </button>
        </form>
        @endcan
    </div>
</div>

{{-- FILTROS --}}
<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('orders.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-2">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="paid"      {{ ($filters['status'] ?? '') === 'paid'      ? 'selected' : '' }}>Pagado</option>
                    <option value="pending"   {{ ($filters['status'] ?? '') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Pago</label>
                <select name="payment_status" class="form-select">
                    <option value="">Todos</option>
                    <option value="approved" {{ ($filters['payment_status'] ?? '') === 'approved' ? 'selected' : '' }}>Aprobado</option>
                    <option value="pending"  {{ ($filters['payment_status'] ?? '') === 'pending'  ? 'selected' : '' }}>Pendiente</option>
                    <option value="rejected" {{ ($filters['payment_status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Cliente</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Nombre / nick" value="{{ $filters['customer_name'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn-neon flex-fill" style="padding:9px;">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a href="{{ route('orders.index') }}" class="btn-ghost" style="padding:9px;">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- TABLA --}}
<div class="glass-card" style="overflow:hidden;">
    @if($orders->isEmpty())
        <div class="empty-state">
            <i class="bi bi-receipt"></i>
            <p>No hay ventas registradas</p>
            @can('sync', \App\Models\Order::class)
            <form method="POST" action="{{ route('orders.sync') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn-ghost">
                    <i class="bi bi-arrow-repeat me-1"></i>Sincronizar desde MeLi
                </button>
            </form>
            @endcan
        </div>
    @else
        <div class="table-responsive">
            <table class="dark-table">
                <thead>
                    <tr>
                        <th style="padding-left:24px;">Orden</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Total COP</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td style="padding-left:24px;">
                            <span style="font-weight:700;color:var(--text-strong);">#{{ $order->id }}</span>
                            @if($order->meli_order_id)
                            <br><small style="font-size:10px;color:var(--text-muted);">{{ $order->meli_order_id }}</small>
                            @endif
                        </td>
                        <td>
                            @if($order->customer)
                                <span style="font-weight:600;color:var(--text-primary);">{{ $order->customer->nickname ?: $order->customer->name }}</span>
                            @else
                                <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="background:rgba(0,212,255,.08);color:var(--neon-blue);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">
                                {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'producto' : 'productos' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:700;color:var(--text-strong);">${{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            @php
                            $statusMap = [
                                'paid'      => ['class' => 'badge-paid',      'label' => 'Pagado'],
                                'pending'   => ['class' => 'badge-pending',   'label' => 'Pendiente'],
                                'cancelled' => ['class' => 'badge-cancelled', 'label' => 'Cancelado'],
                            ];
                            $s = $statusMap[$order->status] ?? ['class' => 'badge-pending', 'label' => $order->status];
                            @endphp
                            <span class="badge {{ $s['class'] }}" style="font-size:10px;padding:4px 10px;">{{ $s['label'] }}</span>
                        </td>
                        <td>
                            @php
                            $payMap = [
                                'approved' => ['class' => 'badge-paid',      'label' => 'Aprobado'],
                                'pending'  => ['class' => 'badge-pending',   'label' => 'Pendiente'],
                                'rejected' => ['class' => 'badge-cancelled', 'label' => 'Rechazado'],
                            ];
                            $p = $payMap[$order->payment_status] ?? ['class' => 'badge-pending', 'label' => $order->payment_status];
                            @endphp
                            <span class="badge {{ $p['class'] }}" style="font-size:10px;padding:4px 10px;">{{ $p['label'] }}</span>
                        </td>
                        <td>
                            <span style="color:var(--text-dim);font-size:12px;">{{ $order->order_date?->format('d/m/Y') }}</span>
                            <br><small style="color:var(--text-muted);font-size:10px;">{{ $order->order_date?->format('H:i') }}</small>
                        </td>
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

        @if($orders->hasPages())
        <div class="px-4 py-3" style="border-top:1px solid var(--border-glow);">
            {{ $orders->withQueryString()->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
