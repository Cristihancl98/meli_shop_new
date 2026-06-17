@extends('layouts.app')

@section('title', 'Venta #' . $order->id)
@section('page-title', 'Detalle de Venta')

@push('styles')
<style>
    .info-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-glow);
        font-size: 12px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .lbl { color: var(--text-muted); }
    .info-row .val { font-weight: 600; color: var(--text-primary); }
    code.dark { background: rgba(0,212,255,.08); color: var(--neon-blue); padding: 2px 8px; border-radius: 5px; font-size: 11px; }
    .side-section-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 14px;
    }
</style>
@endpush

@section('content')

@php
$statusMap = [
    'paid'      => ['class' => 'badge-paid',      'label' => 'Pagado'],
    'pending'   => ['class' => 'badge-pending',   'label' => 'Pendiente'],
    'cancelled' => ['class' => 'badge-cancelled', 'label' => 'Cancelado'],
];
$s = $statusMap[$order->status] ?? ['class' => 'badge-pending', 'label' => $order->status];
$payMap = [
    'approved' => ['class' => 'badge-paid',      'label' => 'Aprobado'],
    'pending'  => ['class' => 'badge-pending',   'label' => 'Pendiente'],
    'rejected' => ['class' => 'badge-cancelled', 'label' => 'Rechazado'],
];
$p = $payMap[$order->payment_status] ?? ['class' => 'badge-pending', 'label' => $order->payment_status];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('orders.index') }}" class="btn-ghost" style="padding:8px 14px;">
        <i class="bi bi-arrow-left me-1"></i>Volver a ventas
    </a>
    <span class="badge {{ $s['class'] }}" style="font-size:13px;padding:8px 18px;">{{ $s['label'] }}</span>
</div>

<div class="row g-4">

    {{-- Columna principal --}}
    <div class="col-lg-8">

        {{-- Encabezado orden --}}
        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:4px;">Orden #{{ $order->id }}</h5>
                    @if($order->meli_order_id)
                    <p style="font-size:12px;color:var(--text-muted);margin:0;">
                        MeLi ID: <code class="dark">{{ $order->meli_order_id }}</code>
                    </p>
                    @endif
                </div>
                <div class="text-end">
                    <div class="info-label">Fecha</div>
                    <div style="font-weight:600;color:var(--text-primary);font-size:13px;">{{ $order->order_date?->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="info-label">Estado orden</div>
                    <span class="badge {{ $s['class'] }}" style="margin-top:4px;padding:5px 12px;">{{ $s['label'] }}</span>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-label">Pago</div>
                    <span class="badge {{ $p['class'] }}" style="margin-top:4px;padding:5px 12px;">{{ $p['label'] }}</span>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-label">Envío</div>
                    <div style="font-weight:600;color:var(--text-primary);font-size:13px;margin-top:4px;">{{ $order->shipping_status ?? '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-label">Creado</div>
                    <div style="font-weight:600;color:var(--text-primary);font-size:13px;margin-top:4px;">{{ $order->created_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>

        {{-- Productos de la orden --}}
        <div class="glass-card mb-4" style="overflow:hidden;">
            <div class="section-header">
                <h6>Productos ({{ $order->items->count() }})</h6>
            </div>

            @forelse($order->items as $item)
            <div class="list-row">
                @if($item->product?->thumbnail)
                <img src="{{ $item->product->thumbnail }}"
                     alt="{{ $item->title }}"
                     style="width:52px;height:52px;object-fit:contain;border-radius:8px;border:1px solid var(--border-glow);background:rgba(255,255,255,.02);"
                     onerror="this.src='https://placehold.co/52x52/0d1733/475569?text=?'">
                @else
                <div style="width:52px;height:52px;border-radius:8px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-box-seam" style="color:var(--text-muted);font-size:20px;"></i>
                </div>
                @endif

                <div class="flex-fill">
                    <div style="font-size:13px;font-weight:600;color:var(--text-primary);">{{ $item->title }}</div>
                    @if($item->product)
                    <a href="{{ route('products.show', $item->product) }}" style="font-size:11px;color:var(--neon-blue);text-decoration:none;">
                        <i class="bi bi-box-seam me-1"></i>Ver producto
                    </a>
                    @endif
                </div>

                <div class="text-center" style="min-width:60px;">
                    <div style="font-size:10px;color:var(--text-muted);">Cantidad</div>
                    <div style="font-weight:700;color:#fff;">{{ $item->quantity }}</div>
                </div>
                <div class="text-center" style="min-width:100px;">
                    <div style="font-size:10px;color:var(--text-muted);">Precio unit.</div>
                    <div style="font-weight:600;color:var(--text-primary);">${{ number_format($item->unit_price, 0, ',', '.') }}</div>
                </div>
                <div class="text-end" style="min-width:110px;">
                    <div style="font-size:10px;color:var(--text-muted);">Subtotal</div>
                    <div style="font-weight:700;font-size:15px;color:#fff;">${{ number_format($item->total_price, 0, ',', '.') }}</div>
                </div>
            </div>
            @empty
            <div class="empty-state" style="padding:32px 20px;">
                <i class="bi bi-box"></i>
                <p>Sin productos registrados</p>
            </div>
            @endforelse

            <div class="d-flex justify-content-end align-items-center gap-3 px-5 py-3"
                 style="border-top:1px solid var(--border-glow);background:rgba(0,212,255,.03);">
                <span style="color:var(--text-muted);font-size:13px;font-weight:600;">Total:</span>
                <span style="font-weight:800;font-size:20px;color:var(--neon-blue);">
                    ${{ number_format($order->total_amount, 0, ',', '.') }}
                    <small style="font-size:12px;color:var(--text-muted);font-weight:400;">COP</small>
                </span>
            </div>
        </div>

    </div>

    {{-- Columna lateral --}}
    <div class="col-lg-4">

        {{-- Cliente --}}
        <div class="glass-card p-4 mb-4">
            <div class="side-section-title"><i class="bi bi-person me-1"></i>Cliente</div>

            @if($order->customer)
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--neon-purple),var(--neon-blue));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person" style="color:#fff;font-size:22px;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;color:#fff;">
                        {{ $order->customer->name ?: $order->customer->nickname }}
                    </div>
                    @if($order->customer->nickname && $order->customer->name)
                    <small style="color:var(--text-muted);">@{{ $order->customer->nickname }}</small>
                    @endif
                </div>
            </div>

            @if($order->customer->email)
            <div style="font-size:12px;color:var(--text-dim);margin-bottom:8px;">
                <i class="bi bi-envelope me-2" style="color:var(--text-muted);"></i>{{ $order->customer->email }}
            </div>
            @endif

            <div class="info-row" style="margin-top:12px;">
                <span class="lbl">Total órdenes</span>
                <span class="val">{{ $order->customer->orders->count() }}</span>
            </div>
            <div class="info-row">
                <span class="lbl">MeLi ID</span>
                <code class="dark">{{ $order->customer->meli_customer_id }}</code>
            </div>

            <a href="{{ route('customers.show', $order->customer) }}" class="btn-ghost w-100 text-center d-block mt-3">
                <i class="bi bi-person-lines-fill me-1"></i>Ver perfil completo
            </a>
            @else
            <p style="color:var(--text-muted);font-size:13px;margin:0;">Sin información de cliente</p>
            @endif
        </div>

        {{-- Info MeLi --}}
        <div class="glass-card p-4">
            <div class="side-section-title"><i class="bi bi-grid-3x3-gap me-1"></i>Info Mercado Libre</div>
            <div class="info-row">
                <span class="lbl">ID Orden MeLi</span>
                <code class="dark">{{ $order->meli_order_id ?? '—' }}</code>
            </div>
            <div class="info-row">
                <span class="lbl">Cuenta</span>
                <span class="val">{{ $order->account?->meli_user_id ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="lbl">Registrado</span>
                <span class="val">{{ $order->created_at->format('d/m/Y') }}</span>
            </div>
        </div>

    </div>
</div>

@endsection
