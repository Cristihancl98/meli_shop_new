@extends('layouts.app')

@section('title', 'Clientes Más Activos')
@section('page-title', 'Clientes Más Activos')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="font-size:13px;color:var(--text-muted);margin:0;">Top 20 clientes ordenados por total gastado en COP</p>
    @can('export-report')
    <a href="{{ route('reports.export.customers') }}" class="btn-ghost" style="color:#34d399;border-color:rgba(52,211,153,.3);">
        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
    </a>
    @endcan
</div>

<div class="glass-card" style="overflow:hidden;">
    @if($customers->isEmpty())
    <div class="empty-state">
        <i class="bi bi-person-check"></i>
        <p>Sin clientes registrados aún. Sincroniza tus ventas primero.</p>
    </div>
    @else
    <div class="table-responsive">
        <table class="dark-table">
            <thead>
                <tr>
                    <th style="padding-left:24px;width:60px;">#</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th class="text-center">Órdenes</th>
                    <th class="text-end">Total Gastado COP</th>
                    <th class="text-end">Ticket Promedio COP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $index => $customer)
                <tr>
                    <td style="padding-left:24px;">
                        <span class="rank-badge {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                            {{ $index + 1 }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--neon-purple),var(--neon-blue));display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <span style="color:#fff;font-size:13px;font-weight:700;">
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
                    <td style="font-size:12px;color:var(--text-dim);">
                        @if($customer->email)
                        <div><i class="bi bi-envelope me-1" style="color:var(--text-muted);"></i>{{ $customer->email }}</div>
                        @endif
                        @if($customer->phone)
                        <div><i class="bi bi-telephone me-1" style="color:var(--text-muted);"></i>{{ $customer->phone }}</div>
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
                    <td class="text-end" style="font-weight:800;font-size:15px;color:var(--neon-green);">
                        ${{ number_format($customer->orders_sum_total_amount ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-end" style="font-weight:600;color:var(--text-dim);">
                        @if($customer->orders_count > 0)
                        ${{ number_format(($customer->orders_sum_total_amount ?? 0) / $customer->orders_count, 0, ',', '.') }}
                        @else
                        <span style="color:var(--text-muted);">—</span>
                        @endif
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
    @endif
</div>

@endsection
