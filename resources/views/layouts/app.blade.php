<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Marketplace Manager') — MeLi Colombia</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-w:    240px;
            --neon-blue:    #00d4ff;
            --neon-purple:  #7c3aed;
            --neon-green:   #00ff88;
            --neon-yellow:  #ffe600;
            --dark-base:    #101d34;
            --dark-sidebar: #0b1628;
            --dark-card:    #182844;
            --border-glow:  rgba(0,212,255,.22);
            --border-card:  rgba(70,100,160,.45);
            --text-primary: #e2e8f0;
            --text-muted:   #8097b8;
            --text-dim:     #a8bcd4;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-base);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* ═══ SIDEBAR ═══ */
        #sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--dark-sidebar);
            border-right: 1px solid var(--border-glow);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .25s ease;
            display: flex;
            flex-direction: column;
        }

        /* Brand */
        .sidebar-brand {
            padding: 22px 18px 20px;
            border-bottom: 1px solid var(--border-glow);
            background: rgba(0,212,255,.03);
        }
        .sidebar-brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            box-shadow: 0 0 16px rgba(0,212,255,.3);
            flex-shrink: 0;
        }
        .sidebar-brand h5 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(90deg, #fff, var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sidebar-brand small {
            font-size: 10px;
            color: var(--text-muted);
            letter-spacing: .5px;
        }

        /* Nav sections */
        .nav-section {
            padding: 18px 18px 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #2d3e5e;
        }

        /* Nav links */
        .nav-link {
            color: #475569;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 2px solid transparent;
            transition: all .15s;
            position: relative;
        }
        .nav-link i { font-size: 15px; width: 18px; flex-shrink: 0; }
        .nav-link:hover {
            color: var(--text-dim);
            background: rgba(0,212,255,.05);
            border-left-color: rgba(0,212,255,.3);
        }
        .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(0,212,255,.12), transparent);
            border-left-color: var(--neon-blue);
        }
        .nav-link.active i { color: var(--neon-blue); }

        /* Account switcher */
        .store-switcher {
            margin: 4px 12px 4px;
        }
        .store-switcher .store-btn {
            background: rgba(0,212,255,.06);
            border: 1px solid rgba(0,212,255,.15);
            border-radius: 10px;
            color: var(--text-dim);
            font-size: 12px;
            font-weight: 500;
            padding: 8px 12px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all .2s;
        }
        .store-switcher .store-btn:hover {
            background: rgba(0,212,255,.1);
            border-color: rgba(0,212,255,.3);
            color: #fff;
        }
        .store-btn .store-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--neon-green);
            box-shadow: 0 0 6px var(--neon-green);
            flex-shrink: 0;
        }
        .store-btn .store-name {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: left;
        }
        .dropdown-menu-dark-custom {
            background: #182844;
            border: 1px solid var(--border-glow);
            border-radius: 12px;
            padding: 6px;
            min-width: 220px;
            box-shadow: 0 16px 48px rgba(0,0,0,.5);
        }
        .dropdown-menu-dark-custom .dropdown-item {
            color: var(--text-dim);
            font-size: 12px;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dropdown-menu-dark-custom .dropdown-item:hover {
            background: rgba(0,212,255,.1);
            color: #fff;
        }
        .dropdown-menu-dark-custom .dropdown-divider {
            border-color: var(--border-glow);
            margin: 4px 0;
        }
        .dropdown-menu-dark-custom .active-store {
            color: var(--neon-blue);
            font-weight: 600;
        }

        /* Sidebar footer */
        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid var(--border-glow);
            padding: 8px 0;
        }

        /* ═══ TOPBAR ═══ */
        #main-content { margin-left: var(--sidebar-w); }

        .topbar {
            background: rgba(12,22,40,.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glow);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .page-title {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            letter-spacing: -.2px;
        }
        .topbar-right { display: flex; align-items: center; gap: 12px; }

        /* User dropdown */
        .user-btn {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border-glow);
            border-radius: 10px;
            color: var(--text-dim);
            font-size: 13px;
            font-weight: 500;
            padding: 7px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all .2s;
        }
        .user-btn:hover {
            background: rgba(0,212,255,.08);
            border-color: rgba(0,212,255,.3);
            color: #fff;
        }
        .user-avatar {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }

        /* ═══ CONTENT ═══ */
        .content-wrap { padding: 36px; }

        /* ═══ CARDS ═══ */
        .glass-card {
            background: var(--dark-card);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            transition: border-color .2s, box-shadow .2s;
        }
        .glass-card:hover {
            border-color: rgba(0,212,255,.35);
            box-shadow: 0 8px 32px rgba(0,0,0,.25), 0 0 0 1px rgba(0,212,255,.08);
        }

        /* KPI metric cards */
        .metric-card {
            background: var(--dark-card);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 20px;
            transition: all .2s;
        }
        .metric-card:hover {
            border-color: rgba(0,212,255,.4);
            box-shadow: 0 4px 20px rgba(0,0,0,.2), 0 0 20px rgba(0,212,255,.06);
            transform: translateY(-2px);
        }
        .metric-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        /* KPI icons */
        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .kpi-blue   { background: linear-gradient(135deg,#1e40af,#3b82f6); color:#fff; box-shadow:0 4px 16px rgba(59,130,246,.3); }
        .kpi-green  { background: linear-gradient(135deg,#065f46,#10b981); color:#fff; box-shadow:0 4px 16px rgba(16,185,129,.3); }
        .kpi-purple { background: linear-gradient(135deg,#4c1d95,#8b5cf6); color:#fff; box-shadow:0 4px 16px rgba(139,92,246,.3); }
        .kpi-teal   { background: linear-gradient(135deg,#134e4a,#14b8a6); color:#fff; box-shadow:0 4px 16px rgba(20,184,166,.3); }
        .kpi-yellow { background: linear-gradient(135deg,#78350f,#f59e0b); color:#fff; box-shadow:0 4px 16px rgba(245,158,11,.3); }
        .kpi-red    { background: linear-gradient(135deg,#7f1d1d,#ef4444); color:#fff; box-shadow:0 4px 16px rgba(239,68,68,.3); }
        .kpi-cyan   { background: linear-gradient(135deg,#164e63,#06b6d4); color:#fff; box-shadow:0 4px 16px rgba(6,182,212,.3); }

        /* Chart cards */
        .chart-card {
            background: var(--dark-card);
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 24px;
        }
        .chart-title { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 2px; }
        .chart-subtitle { font-size: 11px; color: var(--text-muted); margin-bottom: 18px; }

        /* Product cards */
        .product-card {
            background: var(--dark-card);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            overflow: hidden;
            transition: all .2s;
            height: 100%;
        }
        .product-card:hover {
            border-color: rgba(0,212,255,.3);
            box-shadow: 0 8px 24px rgba(0,0,0,.3);
            transform: translateY(-3px);
        }
        .product-card .product-img {
            width: 100%; height: 180px;
            object-fit: contain;
            background: rgba(255,255,255,.03);
            padding: 12px;
        }
        .product-card .product-body { padding: 14px; }
        .product-card .product-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 38px;
        }
        .product-card .product-price { font-size: 17px; font-weight: 800; color: #fff; margin: 6px 0; }
        .product-card .product-meta { font-size: 11px; color: var(--text-muted); }

        /* Status badges */
        .badge-active  { background:rgba(16,185,129,.15); color:#34d399; border:1px solid rgba(16,185,129,.2); }
        .badge-paused  { background:rgba(245,158,11,.12); color:#fbbf24; border:1px solid rgba(245,158,11,.2); }
        .badge-closed  { background:rgba(239,68,68,.12);  color:#f87171; border:1px solid rgba(239,68,68,.2); }
        .badge-pending { background:rgba(245,158,11,.12); color:#fbbf24; border:1px solid rgba(245,158,11,.2); }
        .badge-paid    { background:rgba(16,185,129,.15); color:#34d399; border:1px solid rgba(16,185,129,.2); }
        .badge-cancelled { background:rgba(239,68,68,.12); color:#f87171; border:1px solid rgba(239,68,68,.2); }

        /* Alert flashes */
        .flash-alert {
            border-radius: 10px;
            font-size: 13px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .flash-success {
            background: rgba(16,185,129,.1);
            border: 1px solid rgba(16,185,129,.2);
            color: #34d399;
        }
        .flash-danger {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.2);
            color: #f87171;
        }

        /* Filter panel */
        .filter-panel {
            background: var(--dark-card);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        /* Tables */
        .dark-table {
            color: var(--text-primary);
            width: 100%;
        }
        .dark-table thead th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-glow);
            padding: 10px 16px;
            background: transparent;
            white-space: nowrap;
        }
        .dark-table tbody td {
            font-size: 13px;
            color: var(--text-dim);
            border-bottom: 1px solid rgba(0,212,255,.05);
            padding: 12px 16px;
            vertical-align: middle;
        }
        .dark-table tbody tr:hover td {
            background: rgba(0,212,255,.03);
            color: var(--text-primary);
        }

        /* Form controls */
        .form-control, .form-select {
            background: rgba(255,255,255,.05) !important;
            border: 1px solid var(--border-glow) !important;
            color: var(--text-primary) !important;
            border-radius: 10px !important;
            font-size: 13px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--neon-blue) !important;
            box-shadow: 0 0 0 3px rgba(0,212,255,.1) !important;
            background: rgba(0,212,255,.05) !important;
        }
        .form-control::placeholder { color: var(--text-muted) !important; }
        .form-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .form-select option { background: #182844; color: var(--text-primary); }

        /* Buttons */
        .btn-neon {
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            border-radius: 10px;
            padding: 9px 18px;
            transition: all .2s;
            box-shadow: 0 4px 16px rgba(124,58,237,.3);
        }
        .btn-neon:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0,212,255,.3);
            color: #fff;
        }
        .btn-ghost {
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border-glow);
            color: var(--text-dim);
            font-size: 13px;
            border-radius: 10px;
            padding: 9px 18px;
            transition: all .2s;
        }
        .btn-ghost:hover {
            background: rgba(0,212,255,.08);
            border-color: rgba(0,212,255,.3);
            color: #fff;
        }

        /* Pagination */
        .page-link {
            background: rgba(255,255,255,.04);
            border-color: var(--border-glow);
            color: var(--text-dim);
            border-radius: 8px !important;
            margin: 0 2px;
            font-size: 13px;
            padding: 6px 12px;
        }
        .page-link:hover { background: rgba(0,212,255,.1); color: #fff; border-color: var(--neon-blue); }
        .page-item.active .page-link { background: var(--neon-blue); border-color: var(--neon-blue); color: #000; font-weight: 700; }
        .page-item.disabled .page-link { opacity: .3; }

        /* Rank badges */
        .rank-badge {
            width: 26px; height: 26px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .rank-1 { background:linear-gradient(135deg,#78350f,#f59e0b); color:#fff; }
        .rank-2 { background:rgba(100,116,139,.2); color:#94a3b8; }
        .rank-3 { background:linear-gradient(135deg,#831843,#f472b6); color:#fff; }
        .rank-other { background:rgba(255,255,255,.05); color:#475569; }

        /* Alert items (dashboard) */
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .alert-warning { background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.2); color:#fbbf24; }
        .alert-info    { background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.2); color:#93c5fd; }

        /* Section headers inside glass-cards */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-glow);
        }
        .section-header h6 {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
        }

        /* Empty states */
        .empty-state {
            padding: 80px 32px;
            text-align: center;
        }
        .empty-state i {
            font-size: 52px;
            display: block;
            margin-bottom: 16px;
            opacity: .45;
            color: var(--text-dim);
        }
        .empty-state p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.6;
        }

        /* List rows (order history, top product lists, etc.) */
        .list-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid rgba(0,212,255,.06);
            transition: background .15s;
        }
        .list-row:last-child { border-bottom: none; }
        .list-row:hover { background: rgba(0,212,255,.03); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,212,255,.15); border-radius: 4px; }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- ═══════════ SIDEBAR ═══════════ --}}
<nav id="sidebar">

    {{-- Brand --}}
    <div class="sidebar-brand d-flex align-items-center gap-3">
        <div class="sidebar-brand-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
        <div>
            <h5>Marketplace</h5>
            <small>Mercado Libre Colombia</small>
        </div>
    </div>

    {{-- Nav --}}
    <div class="flex-fill overflow-auto py-2">

        <div class="nav-section">Principal</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-section">Gestión</div>
        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i> Productos
        </a>
        <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Ventas
        </a>
        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Clientes
        </a>

        <div class="nav-section">Reportes</div>
        <a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-line"></i> Ventas por Fecha
        </a>
        <a href="{{ route('reports.products') }}" class="nav-link {{ request()->routeIs('reports.products') ? 'active' : '' }}">
            <i class="bi bi-trophy"></i> Más Vendidos
        </a>
        <a href="{{ route('reports.customers') }}" class="nav-link {{ request()->routeIs('reports.customers') ? 'active' : '' }}">
            <i class="bi bi-person-check"></i> Clientes Activos
        </a>

        <div class="nav-section">Tiendas MeLi</div>

        @php
            $meliAccounts    = auth()->check()
                ? \App\Models\MercadolibreAccount::where('user_id', auth()->id())->where('is_active', true)->orderBy('nickname')->get()
                : collect();
            $activeAccountId = session('active_meli_account_id');
            $activeAccount   = $meliAccounts->firstWhere('id', $activeAccountId) ?? $meliAccounts->first();
        @endphp

        @if($meliAccounts->count() > 0)
        <div class="store-switcher my-1">
            <div class="dropdown">
                <button class="store-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="store-dot"></span>
                    <span class="store-name">{{ $activeAccount?->nickname ?? 'Seleccionar tienda' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark-custom">
                    @foreach($meliAccounts as $acc)
                    <li>
                        <form method="POST" action="{{ route('accounts.switch') }}">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $acc->id }}">
                            <button type="submit" class="dropdown-item {{ $activeAccountId === $acc->id ? 'active-store' : '' }}">
                                <i class="bi bi-{{ $activeAccountId === $acc->id ? 'check-circle-fill' : 'circle' }}" style="font-size:12px;"></i>
                                {{ $acc->nickname }}
                            </button>
                        </form>
                    </li>
                    @endforeach
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('meli.connect') }}">
                            <i class="bi bi-plus-circle" style="font-size:12px;color:var(--neon-blue);"></i>
                            Conectar nueva tienda
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        @else
        <a href="{{ route('meli.connect') }}" class="nav-link">
            <i class="bi bi-link-45deg"></i> Conectar MeLi
        </a>
        @endif

        <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
            <i class="bi bi-shop"></i> Mis Tiendas
        </a>

    </div>

    {{-- Footer --}}
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent" style="color:#475569;">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </button>
        </form>
    </div>

</nav>

{{-- ═══════════ MAIN ═══════════ --}}
<div id="main-content">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-md-none" id="sidebar-toggle"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--border-glow);color:var(--text-dim);">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        </div>

        <div class="topbar-right">
            @if(session('success'))
                <span style="font-size:12px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#34d399;padding:4px 12px;border-radius:20px;">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </span>
            @endif

            <div class="dropdown">
                <button class="user-btn dropdown-toggle border-0" data-bs-toggle="dropdown">
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}</div>
                    {{ auth()->user()?->name ?? 'Usuario' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark-custom" style="min-width:180px;">
                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person" style="font-size:13px;color:var(--neon-blue);"></i>
                            Perfil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item" style="color:#f87171;">
                                <i class="bi bi-box-arrow-right" style="font-size:13px;"></i>
                                Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- FLASH ALERTS --}}
    @if(session('error'))
        <div class="mx-4 mt-4">
            <div class="flash-alert flash-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif
    @if($errors->any())
        <div class="mx-4 mt-4">
            <div class="flash-alert flash-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
        </div>
    @endif

    {{-- CONTENT --}}
    <div class="content-wrap">
        @yield('content')
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>
@stack('scripts')
</body>
</html>
