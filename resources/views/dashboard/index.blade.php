@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .kpi-trend { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
    .kpi-trend .up   { color:var(--success-fg); }
    .kpi-trend .down { color:var(--danger-fg); }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-glow);
    }
    .section-header h6 {
        font-size: 13px;
        font-weight: 700;
        color:var(--text-strong);
        margin: 0;
    }

    .list-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(0,212,255,.05);
        transition: background .15s;
    }
    .list-row:last-child { border-bottom: none; }
    .list-row:hover { background: rgba(0,212,255,.04); }

    .order-status {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .empty-state {
        padding: 48px 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .empty-state i { font-size: 36px; display: block; margin-bottom: 12px; opacity: .3; }
    .empty-state p { font-size: 13px; margin: 0; }
</style>
@endpush

@section('content')

{{-- ── ALERTAS ── --}}
@if(count($alerts) > 0)
<div class="mb-4">
    @foreach($alerts as $alert)
    <div class="alert-item alert-{{ $alert['type'] }}">
        <i class="bi {{ $alert['icon'] }} fs-5 flex-shrink-0"></i>
        <div class="flex-fill">{{ $alert['message'] }}</div>
        <a href="{{ $alert['link'] }}" class="btn-ghost" style="font-size:11px;padding:4px 12px;">Ver</a>
    </div>
    @endforeach
</div>
@endif

{{-- ── FILA 1: KPIs principales ── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-blue"><i class="bi bi-box-seam"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Productos activos</div>
                    <div class="metric-value">{{ number_format($metrics['products_active']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-green"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Ventas este mes</div>
                    <div class="metric-value">{{ number_format($metrics['month_orders']) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-teal"><i class="bi bi-cash-coin"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Ingresos mes COP</div>
                    <div class="metric-value" style="font-size:20px;">${{ number_format($metrics['month_revenue'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-purple"><i class="bi bi-people"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Total clientes</div>
                    <div class="metric-value">{{ number_format($metrics['total_customers']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── FILA 2: KPIs secundarios ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-yellow"><i class="bi bi-clock-history"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Órdenes pendientes</div>
                    <div class="metric-value {{ $metrics['orders_pending'] > 0 ? '' : '' }}"
                         style="{{ $metrics['orders_pending'] > 0 ? 'color:var(--warning-fg);' : '' }}">
                        {{ number_format($metrics['orders_pending']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon {{ $metrics['low_stock_count'] > 0 ? 'kpi-red' : 'kpi-green' }}">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="flex-fill">
                    <div class="metric-label">Stock bajo (≤5)</div>
                    <div class="metric-value" style="{{ $metrics['low_stock_count'] > 0 ? 'color:var(--danger-fg);' : '' }}">
                        {{ number_format($metrics['low_stock_count']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-cyan"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Ticket promedio</div>
                    <div class="metric-value" style="font-size:20px;">${{ number_format($metrics['avg_ticket'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="metric-card">
            <div class="d-flex align-items-start gap-3">
                <div class="kpi-icon kpi-yellow"><i class="bi bi-pause-circle"></i></div>
                <div class="flex-fill">
                    <div class="metric-label">Productos pausados</div>
                    <div class="metric-value">{{ number_format($metrics['products_paused']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── GRÁFICOS ── --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="chart-card" style="height:100%;">
            <div class="chart-title">Ingresos diarios</div>
            <div class="chart-subtitle">Últimos 30 días · solo órdenes pagadas (COP)</div>
            <canvas id="chartDailySales" height="95"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card" style="height:100%;">
            <div class="chart-title">Estado de órdenes</div>
            <div class="chart-subtitle">Distribución total acumulada</div>
            <canvas id="chartOrderStatus" height="180"></canvas>
            <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:11px;color:var(--text-muted);">
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:5px;"></span>Pagadas</span>
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:5px;"></span>Pendientes</span>
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:5px;"></span>Canceladas</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-title">Ingresos mensuales</div>
            <div class="chart-subtitle">Últimos 6 meses · órdenes pagadas (COP)</div>
            <canvas id="chartMonthlyRevenue" height="65"></canvas>
        </div>
    </div>
</div>

{{-- ── TOP PRODUCTOS + ÚLTIMAS VENTAS ── --}}
<div class="row g-4">

    <div class="col-lg-5">
        <div class="glass-card" style="height:100%;">
            <div class="section-header">
                <h6><i class="bi bi-trophy me-2" style="color:var(--neon-yellow);"></i>Top productos más vendidos</h6>
                <a href="{{ route('products.index') }}" class="btn-ghost" style="font-size:11px;padding:4px 12px;border-radius:8px;">Ver todos</a>
            </div>

            @forelse($topProducts as $index => $product)
            <div class="list-row">
                <span class="rank-badge {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                    {{ $index + 1 }}
                </span>
                <img
                    src="{{ $product->thumbnail ?: 'https://placehold.co/36x36/0d1733/475569?text=?' }}"
                    alt="{{ $product->title }}"
                    style="width:36px;height:36px;object-fit:contain;border-radius:8px;border:1px solid var(--border-glow);background:var(--overlay-soft);"
                    onerror="this.src='https://placehold.co/36x36/0d1733/475569?text=?'">
                <div class="flex-fill overflow-hidden">
                    <div class="fw-semibold text-truncate" style="font-size:12px;color:var(--text-primary);">{{ $product->title }}</div>
                    <div style="font-size:11px;color:var(--text-muted);">${{ number_format($product->price, 0, ',', '.') }} COP</div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold" style="font-size:13px;color:var(--text-strong);">{{ $product->statistics?->quantity_sold ?? 0 }}</div>
                    <div style="font-size:10px;color:var(--text-muted);">uds.</div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-trophy"></i>
                <p>Sin datos de ventas aún</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-7">
        <div class="glass-card" style="height:100%;">
            <div class="section-header">
                <h6><i class="bi bi-receipt me-2" style="color:var(--neon-blue);"></i>Últimas ventas</h6>
                <a href="{{ route('orders.index') }}" class="btn-ghost" style="font-size:11px;padding:4px 12px;border-radius:8px;">Ver todas</a>
            </div>

            @forelse($recentOrders as $order)
            @php
                $sMap = [
                    'paid'      => ['class'=>'badge-paid',      'label'=>'Pagado'],
                    'pending'   => ['class'=>'badge-pending',   'label'=>'Pendiente'],
                    'cancelled' => ['class'=>'badge-cancelled', 'label'=>'Cancelado'],
                ];
                $s = $sMap[$order->status] ?? ['class'=>'badge-paused','label'=>$order->status];
            @endphp
            <div class="list-row">
                <div class="flex-fill overflow-hidden">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="fw-semibold" style="font-size:13px;color:var(--text-primary);">
                            {{ $order->customer?->nickname ?: ($order->customer?->name ?: 'Cliente') }}
                        </span>
                        <span class="order-status {{ $s['class'] }}">{{ $s['label'] }}</span>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);">
                        {{ $order->order_date?->format('d/m/Y H:i') }}
                        · {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'producto' : 'productos' }}
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="fw-bold" style="font-size:14px;color:var(--text-strong);">${{ number_format($order->total_amount, 0, ',', '.') }}</div>
                    <a href="{{ route('orders.show', $order) }}" style="font-size:11px;color:var(--neon-blue);">Ver detalle →</a>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-receipt"></i>
                <p>Sin ventas registradas aún</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

const chartTheme = () => ({
    accent:  themeColor('--neon-blue'),
    grid:    themeColor('--chart-grid'),
    tick:    themeColor('--chart-tick'),
    surface: themeColor('--surface-raised'),
    border:  themeColor('--border-glow'),
    title:   themeColor('--text-muted'),
    body:    themeColor('--text-strong'),
});

const tooltipTheme = (t) => ({
    backgroundColor: t.surface,
    borderColor:     t.border,
    borderWidth:     1,
    titleColor:      t.title,
    bodyColor:       t.body,
});

const themedCharts = [];

const applyChartTheme = (chart) => {
    const t = chartTheme();
    Chart.defaults.color = t.tick;
    Object.assign(chart.options.plugins.tooltip, tooltipTheme(t));

    Object.values(chart.options.scales || {}).forEach((scale) => {
        scale.ticks.color = t.tick;
        if (scale.grid && scale.grid.display !== false) {
            scale.grid.color = t.grid;
        }
    });

    if (chart.config.type === 'line') {
        chart.data.datasets[0].borderColor = t.accent;
        chart.data.datasets[0].pointBackgroundColor = t.accent;
    }

    chart.update('none');
};

window.addEventListener('themechange', () => themedCharts.forEach(applyChartTheme));

// ── Ventas diarias ──
const dailyLabels  = @json($dailySales['labels']);
const dailyRevenue = @json($dailySales['revenue']);

themedCharts.push(new Chart(document.getElementById('chartDailySales'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Ingresos COP',
            data: dailyRevenue,
            borderColor: themeColor('--neon-blue'),
            backgroundColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 260);
                g.addColorStop(0,   'rgba(14,165,233,0.2)');
                g.addColorStop(1,   'rgba(14,165,233,0)');
                return g;
            },
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: themeColor('--neon-blue'),
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => '$' + ctx.parsed.y.toLocaleString('es-CO') + ' COP' }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 10 }, color: themeColor('--chart-tick') } },
            y: {
                grid: { color: themeColor('--chart-grid') },
                ticks: {
                    font: { size: 10 }, color: themeColor('--chart-tick'),
                    callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v/1000).toFixed(0) + 'k')
                }
            }
        }
    }
}));

// ── Estado de órdenes (dona) ──
themedCharts.push(new Chart(document.getElementById('chartOrderStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Pagadas', 'Pendientes', 'Canceladas'],
        datasets: [{
            data: [{{ $metrics['orders_paid'] }}, {{ $metrics['orders_pending'] }}, {{ $metrics['orders_cancelled'] }}],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` }
            }
        }
    }
}));

// ── Ingresos mensuales (barras) ──
const monthlyLabels  = @json($monthlyRevenue['labels']);
const monthlyRevData = @json($monthlyRevenue['revenue']);

themedCharts.push(new Chart(document.getElementById('chartMonthlyRevenue'), {
    type: 'bar',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Ingresos COP',
            data: monthlyRevData,
            backgroundColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                g.addColorStop(0, 'rgba(124,58,237,0.9)');
                g.addColorStop(1, 'rgba(0,212,255,0.7)');
                return g;
            },
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => '$' + ctx.parsed.y.toLocaleString('es-CO') + ' COP' }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, color: themeColor('--chart-tick') } },
            y: {
                grid: { color: themeColor('--chart-grid') },
                ticks: {
                    font: { size: 11 }, color: themeColor('--chart-tick'),
                    callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v/1000).toFixed(0) + 'k')
                }
            }
        }
    }
}));

themedCharts.forEach(applyChartTheme);
</script>
@endpush
