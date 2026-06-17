@extends('layouts.app')

@section('title', $customer->name ?: $customer->nickname)
@section('page-title', 'Perfil del Cliente')

@push('styles')
<style>
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 0;
        border-bottom: 1px solid var(--border-card);
        font-size: 13px;
    }
    .stat-row:last-child { border-bottom: none; }
    .stat-row .lbl { color: var(--text-muted); }
    .stat-row .val { font-weight: 700; color: #fff; }
    code.dark { background: rgba(0,212,255,.1); color: var(--neon-blue); padding: 2px 8px; border-radius: 5px; font-size: 11px; }
    .side-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('customers.index') }}" class="btn-ghost" style="padding:8px 14px;">
        <i class="bi bi-arrow-left me-1"></i>Volver a clientes
    </a>
</div>

<div class="row g-4">

    {{-- Sidebar perfil --}}
    <div class="col-lg-4">

        {{-- Tarjeta perfil --}}
        <div class="glass-card p-4 mb-4 text-center">
            <div style="width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,var(--neon-purple),var(--neon-blue));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 24px rgba(124,58,237,.3);">
                <span style="color:#fff;font-size:30px;font-weight:700;">
                    {{ strtoupper(substr($customer->name ?: $customer->nickname, 0, 1)) }}
                </span>
            </div>
            <h5 style="color:#fff;font-weight:700;margin-bottom:4px;">{{ $customer->name ?: $customer->nickname }}</h5>
            @if($customer->nickname && $customer->name)
            <p style="color:var(--text-muted);font-size:13px;margin-bottom:8px;">@{{ $customer->nickname }}</p>
            @endif
            @if($customer->email)
            <p style="font-size:12px;color:var(--text-dim);margin-bottom:4px;"><i class="bi bi-envelope me-1" style="color:var(--text-muted);"></i>{{ $customer->email }}</p>
            @endif
            @if($customer->phone)
            <p style="font-size:12px;color:var(--text-dim);margin-bottom:0;"><i class="bi bi-telephone me-1" style="color:var(--text-muted);"></i>{{ $customer->phone }}</p>
            @endif
        </div>

        {{-- Resumen compras --}}
        <div class="glass-card p-4 mb-4">
            <div class="side-title"><i class="bi bi-graph-up me-1" style="color:var(--neon-green);"></i>Resumen de compras</div>

            <div class="stat-row">
                <span class="lbl">Total órdenes</span>
                <span style="background:linear-gradient(135deg,var(--neon-purple),var(--neon-blue));color:#fff;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                    {{ $totalOrders }}
                </span>
            </div>
            <div class="stat-row">
                <span class="lbl">Total gastado</span>
                <div class="text-end">
                    <div style="font-weight:800;font-size:15px;color:var(--neon-green);">${{ number_format($totalSpent, 0, ',', '.') }}</div>
                    <div style="font-size:10px;color:var(--text-muted);">COP</div>
                </div>
            </div>
            @if($totalOrders > 0)
            <div class="stat-row">
                <span class="lbl">Ticket promedio</span>
                <div class="text-end">
                    <div class="val">${{ number_format($totalSpent / $totalOrders, 0, ',', '.') }}</div>
                    <div style="font-size:10px;color:var(--text-muted);">COP</div>
                </div>
            </div>
            @endif
            @php $lastOrder = $customer->orders->first(); @endphp
            @if($lastOrder)
            <div class="stat-row">
                <span class="lbl">Última compra</span>
                <span class="val">{{ $lastOrder->order_date?->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>

        {{-- Info MeLi --}}
        <div class="glass-card p-4">
            <div class="side-title"><i class="bi bi-grid-3x3-gap me-1" style="color:var(--neon-blue);"></i>Info Mercado Libre</div>
            <div class="stat-row">
                <span class="lbl">MeLi ID</span>
                <code class="dark">{{ $customer->meli_customer_id ?? '—' }}</code>
            </div>
            <div class="stat-row">
                <span class="lbl">Registrado</span>
                <span class="val">{{ $customer->created_at->format('d/m/Y') }}</span>
            </div>
        </div>

    </div>

    {{-- Historial de órdenes --}}
    <div class="col-lg-8">
        <div class="glass-card" style="overflow:hidden;">
            <div class="section-header">
                <h6><i class="bi bi-receipt me-2" style="color:var(--neon-blue);"></i>Historial de compras ({{ $totalOrders }})</h6>
            </div>

            @forelse($customer->orders as $order)
            @php
            $sMap = [
                'paid'      => ['class' => 'badge-paid',      'label' => 'Pagado'],
                'pending'   => ['class' => 'badge-pending',   'label' => 'Pendiente'],
                'cancelled' => ['class' => 'badge-cancelled', 'label' => 'Cancelado'],
            ];
            $s = $sMap[$order->status] ?? ['class' => 'badge-pending', 'label' => $order->status];
            @endphp
            <div class="list-row">
                <div class="flex-fill">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-weight:700;font-size:14px;color:#fff;">Orden #{{ $order->id }}</span>
                        <span class="badge {{ $s['class'] }}" style="font-size:10px;padding:3px 8px;">{{ $s['label'] }}</span>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);">
                        {{ $order->order_date?->format('d/m/Y H:i') }}
                        · {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'producto' : 'productos' }}
                    </div>
                    @if($order->items->count() > 0)
                    <div style="font-size:12px;color:var(--text-dim);margin-top:6px;">
                        @foreach($order->items->take(2) as $item)
                        <div style="display:flex;align-items:center;gap:4px;">
                            <i class="bi bi-dot" style="color:var(--text-muted);"></i>
                            {{ $item->title }}
                            <span style="color:var(--text-muted);">({{ $item->quantity }})</span>
                        </div>
                        @endforeach
                        @if($order->items->count() > 2)
                        <div style="font-size:11px;color:var(--text-muted);">+{{ $order->items->count() - 2 }} más</div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="text-end flex-shrink-0">
                    <div style="font-weight:800;font-size:16px;color:var(--neon-blue);">${{ number_format($order->total_amount, 0, ',', '.') }}</div>
                    <small style="color:var(--text-muted);font-size:10px;">COP</small>
                    <br>
                    <a href="{{ route('orders.show', $order) }}" class="btn-ghost mt-2 d-inline-block" style="padding:5px 12px;font-size:11px;">
                        Ver orden →
                    </a>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <p>Sin órdenes registradas</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection
