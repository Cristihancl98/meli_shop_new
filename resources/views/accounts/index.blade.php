@extends('layouts.app')

@section('title', 'Mis Tiendas MeLi')
@section('page-title', 'Mis Tiendas MeLi')

@push('styles')
<style>
    .account-card {
        background: rgba(255,255,255,.03);
        border: 1px solid var(--border-card);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all .2s;
    }
    .account-card:hover { border-color: var(--border-glow); }
    .account-card.active {
        border-color: rgba(0,212,255,.4);
        background: rgba(0,212,255,.05);
        box-shadow: 0 0 20px rgba(0,212,255,.08);
    }
    .account-avatar {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .account-avatar.active-av {
        background: linear-gradient(135deg, var(--neon-purple), var(--neon-blue));
        box-shadow: 0 4px 16px rgba(0,212,255,.3);
        color: #fff;
    }
    .account-avatar.inactive-av {
        background: rgba(255,255,255,.06);
        color: var(--text-muted);
    }
    .active-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(0,212,255,.1);
        border: 1px solid rgba(0,212,255,.25);
        color: var(--neon-blue);
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    .active-badge .dot {
        width: 5px; height: 5px;
        border-radius: 50%;
        background: var(--neon-blue);
        box-shadow: 0 0 5px var(--neon-blue);
    }
    .token-expired {
        font-size: 10px;
        color: #f87171;
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.2);
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 6px;
    }
    .how-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 12px;
        font-size: 13px;
        color: var(--text-dim);
    }
    .how-item i { color: var(--neon-green); font-size: 15px; margin-top: 1px; flex-shrink: 0; }
    .btn-danger-ghost {
        background: rgba(239,68,68,.08);
        border: 1px solid rgba(239,68,68,.2);
        color: #f87171;
        font-size: 12px;
        border-radius: 8px;
        padding: 7px 14px;
        transition: all .2s;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-danger-ghost:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.4); }
</style>
@endpush

@section('content')

<div class="row g-4">

    {{-- TIENDAS CONECTADAS --}}
    <div class="col-12 col-lg-8">
        <div class="glass-card" style="overflow:hidden;">
            <div class="section-header">
                <h6><i class="bi bi-shop me-2" style="color:var(--neon-blue);"></i>Tiendas conectadas</h6>
                <span style="font-size:12px;color:var(--text-muted);">{{ $accounts->count() }} {{ $accounts->count() === 1 ? 'tienda' : 'tiendas' }}</span>
            </div>

            <div class="p-3">
                @if($accounts->isEmpty())
                    <div class="empty-state" style="padding:48px 20px;">
                        <i class="bi bi-shop-window"></i>
                        <p>No tienes tiendas de Mercado Libre conectadas.</p>
                        <a href="{{ route('meli.connect') }}" class="btn-neon mt-3" style="display:inline-block;">
                            <i class="bi bi-plus-lg me-2"></i>Conectar primera tienda
                        </a>
                    </div>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($accounts as $account)
                        @php $isSelected = $selectedId === $account->id; @endphp
                        <div class="account-card {{ $isSelected ? 'active' : '' }}">
                            <div class="account-avatar {{ $isSelected ? 'active-av' : 'inactive-av' }}">
                                <i class="bi bi-shop{{ $isSelected ? '-window' : '' }}"></i>
                            </div>

                            <div class="flex-fill">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span style="font-weight:700;font-size:15px;color:#fff;">{{ $account->nickname }}</span>
                                    @if($isSelected)
                                    <span class="active-badge"><span class="dot"></span>Activa</span>
                                    @endif
                                    @if($account->isTokenExpired())
                                    <span class="token-expired"><i class="bi bi-exclamation-triangle me-1"></i>Token expirado</span>
                                    @endif
                                </div>
                                <div style="font-size:12px;color:var(--text-muted);">
                                    @if($account->email){{ $account->email }} · @endif
                                    ID: <code style="color:var(--neon-blue);font-size:11px;background:rgba(0,212,255,.08);padding:1px 6px;border-radius:4px;">{{ $account->meli_user_id }}</code>
                                </div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                                    Token expira: {{ $account->expires_at?->format('d/m/Y H:i') ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-shrink-0">
                                @if(!$isSelected)
                                <form method="POST" action="{{ route('accounts.switch') }}">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                                    <button type="submit" class="btn-ghost" style="padding:7px 14px;font-size:12px;">
                                        <i class="bi bi-arrow-left-right me-1"></i>Activar
                                    </button>
                                </form>
                                @endif

                                <form method="POST" action="{{ route('accounts.disconnect', $account->id) }}"
                                      onsubmit="return confirm('¿Desconectar la tienda {{ $account->nickname }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger-ghost">
                                        <i class="bi bi-plug me-1"></i>Desconectar
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- PANEL DERECHO --}}
    <div class="col-12 col-lg-4">

        {{-- Conectar nueva --}}
        <div class="glass-card p-4 mb-4">
            <h6 style="font-size:13px;font-weight:700;color:#fff;margin-bottom:8px;">
                <i class="bi bi-plus-circle me-2" style="color:var(--neon-green);"></i>Conectar nueva tienda
            </h6>
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
                Vincula otra cuenta de Mercado Libre Colombia para gestionarla desde este panel.
            </p>
            <a href="{{ route('meli.connect') }}" class="btn-neon w-100 text-center d-block">
                <i class="bi bi-link-45deg me-2"></i>Conectar con MeLi
            </a>
        </div>

        {{-- Cómo funciona --}}
        <div class="glass-card p-4">
            <h6 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:16px;">
                <i class="bi bi-info-circle me-1"></i>¿Cómo funciona?
            </h6>
            <div class="how-item"><i class="bi bi-check2-circle"></i>Conecta N tiendas MeLi distintas</div>
            <div class="how-item"><i class="bi bi-check2-circle"></i>Cambia entre tiendas en un clic desde el sidebar</div>
            <div class="how-item"><i class="bi bi-check2-circle"></i>Cada tienda tiene sus propios productos, ventas y clientes</div>
            <div class="how-item" style="margin-bottom:0;"><i class="bi bi-check2-circle"></i>Los reportes y el dashboard se filtran por tienda activa</div>
        </div>

    </div>

</div>

@endsection
