@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')

{{-- STATS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-purple"><i class="bi bi-people"></i></div>
                <div>
                    <div class="metric-label">Total clientes</div>
                    <div class="metric-value">{{ number_format($customers->total()) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-blue"><i class="bi bi-layers"></i></div>
                <div>
                    <div class="metric-label">Esta página</div>
                    <div class="metric-value">{{ $customers->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-teal"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <div class="metric-label">Página</div>
                    <div class="metric-value">{{ $customers->currentPage() }} / {{ $customers->lastPage() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- BÚSQUEDA --}}
<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('customers.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label">Buscar cliente</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Nombre, nickname o email..."
                       value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn-neon flex-fill" style="padding:9px;">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
                <a href="{{ route('customers.index') }}" class="btn-ghost" style="padding:9px;">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- TABLA --}}
<div class="glass-card" style="overflow:hidden;">
    @if($customers->isEmpty())
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>No hay clientes registrados</p>
            <small style="color:var(--text-muted);font-size:12px;">Los clientes se crean automáticamente al sincronizar ventas</small>
            <a href="{{ route('orders.index') }}" class="btn-ghost mt-3" style="display:inline-block;padding:8px 16px;">
                <i class="bi bi-receipt me-1"></i>Ver Ventas
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="dark-table">
                <thead>
                    <tr>
                        <th style="padding-left:24px;">Cliente</th>
                        <th>Contacto</th>
                        <th class="text-center">Órdenes</th>
                        <th class="text-end">Total comprado COP</th>
                        <th>Registrado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td style="padding-left:24px;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--neon-purple),var(--neon-blue));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <span style="color:var(--text-strong);font-size:13px;font-weight:700;">
                                        {{ strtoupper(substr($customer->name ?: $customer->nickname, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--text-primary);">{{ $customer->name ?: $customer->nickname }}</div>
                                    @if($customer->nickname && $customer->name)
                                    <small style="color:var(--text-muted);font-size:11px;">@{{ $customer->nickname }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($customer->email)
                            <div style="font-size:12px;color:var(--text-dim);"><i class="bi bi-envelope me-1" style="color:var(--text-muted);"></i>{{ $customer->email }}</div>
                            @endif
                            @if($customer->phone)
                            <div style="font-size:12px;color:var(--text-dim);"><i class="bi bi-telephone me-1" style="color:var(--text-muted);"></i>{{ $customer->phone }}</div>
                            @endif
                            @if(!$customer->email && !$customer->phone)
                            <span style="color:var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span style="background:rgba(0,212,255,.1);color:var(--neon-blue);padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">
                                {{ $customer->orders_count }}
                            </span>
                        </td>
                        <td class="text-end">
                            <span style="font-weight:700;color:var(--text-strong);">${{ number_format($customer->orders_sum_total_amount ?? 0, 0, ',', '.') }}</span>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);">
                            {{ $customer->created_at->format('d/m/Y') }}
                        </td>
                        <td>
                            <a href="{{ route('customers.show', $customer) }}" class="btn-ghost" style="padding:6px 12px;font-size:12px;">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-4 py-3" style="border-top:1px solid var(--border-card);">
            {{ $customers->withQueryString()->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
