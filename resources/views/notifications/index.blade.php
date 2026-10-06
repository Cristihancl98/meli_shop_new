@extends('layouts.app')

@section('title', 'Notificaciones')
@section('page-title', 'Notificaciones')

@section('content')

@if(!$account)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-triangle-fill"></i> Vincula una cuenta de Mercado Libre.</div>
@endif

<div class="glass-card" style="overflow:hidden;">
    <div class="section-header"><span>{{ $notifications->count() }} sin leer</span></div>
    @forelse($notifications as $notification)
        <div class="alert-item d-flex justify-content-between align-items-center" style="padding:14px 20px;border-bottom:1px solid var(--border-glow);">
            <div>
                @switch($notification->type)
                    @case('sale')
                        <i class="bi bi-bag-check me-2" style="color:var(--neon-green);"></i> Nueva venta
                        <a href="{{ route('orders.index') }}" class="ms-2" style="font-size:12px;">Ver ventas</a>
                        @break
                    @case('pre_sale_question')
                        <i class="bi bi-question-circle me-2" style="color:var(--neon-yellow);"></i> Nueva pregunta preventa
                        <a href="{{ route('questions.index', ['answered' => '0']) }}" class="ms-2" style="font-size:12px;">Responder</a>
                        @break
                    @default
                        <i class="bi bi-chat-dots me-2" style="color:var(--neon-blue);"></i> Nuevo mensaje posventa
                        <a href="{{ route('post-sale.index') }}" class="ms-2" style="font-size:12px;">Ver conversación</a>
                @endswitch
                <small style="color:var(--text-muted);display:block;">{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                @csrf @method('PATCH')
                <button class="btn-ghost" style="padding:4px 10px;"><i class="bi bi-check2"></i> Leída</button>
            </form>
        </div>
    @empty
        <div class="empty-state"><i class="bi bi-bell" style="font-size:40px;"></i><p>Sin notificaciones pendientes.</p></div>
    @endforelse
</div>

@endsection
