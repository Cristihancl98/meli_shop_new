<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Acceso') — Marketplace Manager</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --neon-blue:   #00d4ff;
            --neon-purple: #7c3aed;
            --neon-green:  #00ff88;
            --dark-bg:     #050814;
            --card-bg:     rgba(10, 15, 35, 0.85);
            --border:      rgba(0, 212, 255, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Fondo animado con partículas ── */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,212,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,212,255,.04) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove {
            0%   { transform: translateY(0); }
            100% { transform: translateY(60px); }
        }

        /* Orbes de luz de fondo */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            animation: orbFloat 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(124,58,237,.35) 0%, transparent 70%);
            top: -150px; left: -150px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,212,255,.25) 0%, transparent 70%);
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(0,255,136,.15) 0%, transparent 70%);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -2s;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        /* ── Partículas flotantes ── */
        .particles { position: fixed; inset: 0; pointer-events: none; }
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: var(--neon-blue);
            border-radius: 50%;
            animation: particleFloat linear infinite;
            opacity: 0;
        }
        @keyframes particleFloat {
            0%   { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: .8; }
            90%  { opacity: .4; }
            100% { transform: translateY(-10vh) translateX(60px); opacity: 0; }
        }

        /* ── Card principal ── */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 16px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow:
                0 0 0 1px rgba(0,212,255,.08),
                0 25px 60px rgba(0,0,0,.6),
                inset 0 1px 0 rgba(255,255,255,.06);
            overflow: hidden;
            animation: cardIn .6s cubic-bezier(.22,1,.36,1);
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(32px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Línea superior animada */
        .card-topline {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), var(--neon-purple), var(--neon-blue), transparent);
            background-size: 200% 100%;
            animation: lineSweep 3s linear infinite;
        }
        @keyframes lineSweep {
            0%   { background-position: -100% 0; }
            100% { background-position: 200% 0; }
        }

        /* ── Header ── */
        .login-header {
            padding: 36px 40px 28px;
            text-align: center;
            position: relative;
        }

        .brand-icon {
            width: 72px; height: 72px;
            margin: 0 auto 20px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .brand-icon-inner {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            position: relative;
            z-index: 1;
            box-shadow: 0 0 30px rgba(0,212,255,.4);
        }
        .brand-icon-ring {
            position: absolute;
            inset: 0;
            border-radius: 20px;
            border: 1px solid rgba(0,212,255,.4);
            animation: ringPulse 2s ease-in-out infinite;
        }
        @keyframes ringPulse {
            0%, 100% { transform: scale(1); opacity: .6; }
            50%       { transform: scale(1.15); opacity: 0; }
        }

        .brand-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
            margin-bottom: 6px;
        }
        .brand-title span {
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand-subtitle {
            font-size: 12px;
            color: rgba(148,163,184,.7);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,255,136,.08);
            border: 1px solid rgba(0,255,136,.2);
            color: var(--neon-green);
            font-size: 11px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 14px;
            letter-spacing: .5px;
        }
        .status-dot {
            width: 6px; height: 6px;
            background: var(--neon-green);
            border-radius: 50%;
            animation: statusBlink 2s ease-in-out infinite;
        }
        @keyframes statusBlink {
            0%, 100% { opacity: 1; box-shadow: 0 0 6px var(--neon-green); }
            50%       { opacity: .3; box-shadow: none; }
        }

        /* ── Body ── */
        .login-body {
            padding: 8px 40px 40px;
        }

        .field-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(148,163,184,.8);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: block;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 20px;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(0,212,255,.5);
            font-size: 15px;
            z-index: 2;
            transition: color .2s;
        }
        .cyber-input {
            width: 100%;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(0,212,255,.2);
            border-radius: 10px;
            color: #e2e8f0;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            padding: 13px 16px 13px 44px;
            outline: none;
            transition: all .2s;
        }
        .cyber-input::placeholder { color: rgba(148,163,184,.4); }
        .cyber-input:focus {
            border-color: var(--neon-blue);
            background: rgba(0,212,255,.05);
            box-shadow: 0 0 0 3px rgba(0,212,255,.1), 0 0 20px rgba(0,212,255,.08);
        }
        .cyber-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--neon-blue);
        }
        .cyber-input.is-invalid {
            border-color: #f87171;
        }

        /* ── Botón ── */
        .btn-cyber {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            letter-spacing: .5px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all .2s;
            box-shadow: 0 4px 24px rgba(124,58,237,.4);
            margin-top: 8px;
        }
        .btn-cyber::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.1), transparent);
        }
        .btn-cyber::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
            transition: left .5s;
        }
        .btn-cyber:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 32px rgba(0,212,255,.35), 0 0 0 1px rgba(0,212,255,.2);
        }
        .btn-cyber:hover::after { left: 150%; }
        .btn-cyber:active { transform: translateY(0); }

        /* ── Alert ── */
        .cyber-alert {
            background: rgba(248,113,113,.08);
            border: 1px solid rgba(248,113,113,.25);
            border-radius: 8px;
            color: #fca5a5;
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cyber-alert-success {
            background: rgba(0,255,136,.06);
            border-color: rgba(0,255,136,.2);
            color: #86efac;
        }

        /* ── Footer ── */
        .login-footer {
            border-top: 1px solid rgba(0,212,255,.08);
            padding: 16px 40px;
            text-align: center;
            font-size: 11px;
            color: rgba(148,163,184,.35);
            letter-spacing: .5px;
        }
        .login-footer span { color: rgba(0,212,255,.4); }
        /* ── Card clara (conexión y login) ── */
        .login-card-light {
        background: #fff;
        border-color: rgba(15,23,42,.08);
        box-shadow: 0 25px 60px rgba(0,0,0,.45);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        }
        .login-card-light .login-body { padding-top: 36px; }
        .login-card-light .field-label { color: #475569; }
        .login-card-light .cyber-input {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
        }
        .login-card-light .cyber-input::placeholder { color: #94a3b8; }
        .login-card-light .cyber-input:focus {
        background: #fff;
        border-color: #4a7fc1;
        box-shadow: 0 0 0 3px rgba(74,127,193,.15);
        }
        .login-card-light .input-icon,
        .login-card-light .input-wrap:focus-within .input-icon { color: #4a7fc1; }
        .login-card-light .cyber-alert { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .login-card-light .login-footer { border-top-color: #e2e8f0; color: #94a3b8; }
        .login-card-light .login-footer span { color: #4a7fc1; }
        .login-card-light .cyber-alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
        .login-card-light .store-heading-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #4a7fc1;
            margin-bottom: 6px;
        }
        .login-card-light .store-heading-name {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.15;
            color: #1f2d4f;
            margin: 0 0 8px;
            word-break: break-word;
        }
        .login-card-light .store-heading-change {
            font-size: 12px;
            color: #4a7fc1;
            text-decoration: none;
        }
        .login-card-light .store-heading-change:hover { text-decoration: underline; }

    </style>
    @stack('styles')
</head>
<body>

<!-- Fondo -->
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<!-- Partículas -->
<div class="particles" id="particles"></div>

<!-- Card -->
<div class="login-wrapper">
    <div class="login-card @yield('card-class')">

        <div class="card-topline"></div>

        @section('header')
        <div class="login-header">
            <div class="brand-icon">
                <div class="brand-icon-inner">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                </div>
                <div class="brand-icon-ring"></div>
            </div>

            <div class="brand-title">Marketplace <span>Manager</span></div>
            <div class="brand-subtitle">MeLi Colombia · Panel de control</div>

            <div class="status-badge">
                <div class="status-dot"></div>
                Sistema operativo
            </div>
        </div>
        @show

        <div class="login-body">
@yield('form')
        </div>

        <div class="login-footer">
            Acceso restringido · <span>Marketplace Manager v1.0</span>
        </div>

    </div>
</div>

<script>
    // Partículas flotantes
    const container = document.getElementById('particles');
    const colors = ['#00d4ff', '#7c3aed', '#00ff88'];
    for (let i = 0; i < 40; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            animation-duration: ${6 + Math.random() * 12}s;
            animation-delay: ${Math.random() * 10}s;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            width: ${1 + Math.random() * 2}px;
            height: ${1 + Math.random() * 2}px;
        `;
        container.appendChild(p);
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
