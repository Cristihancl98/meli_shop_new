@extends('layouts.app')

@section('title', 'Posventa')
@section('page-title', 'Mensajería posventa')

@section('content')

@if(!$account)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-triangle-fill"></i> Vincula una cuenta de Mercado Libre.</div>
@endif

@forelse($conversations as $order)
    <div class="glass-card p-4 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <strong style="color:var(--text-strong);">{{ $order->customer?->nickname ?? $order->customer?->name }}</strong>
                <span style="color:var(--text-muted);font-size:12px;"> · Venta <a href="{{ route('orders.show', $order->id) }}">#{{ $order->meli_order_id }}</a> · {{ $order->order_date?->format('d/m/Y') }}</span>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($order->items as $item)
                    <span class="badge badge-paid" title="{{ $item->title }}">{{ \Illuminate\Support\Str::limit($item->title, 30) }} × {{ $item->quantity }}</span>
                @endforeach
            </div>
        </div>

        <div class="d-flex flex-column gap-2 mb-3" style="max-height:320px;overflow:auto;">
            @foreach($order->postSaleMessages as $message)
                <div class="{{ $message->from_seller ? 'align-self-end' : 'align-self-start' }}"
                     style="max-width:75%;padding:8px 12px;border-radius:10px;{{ $message->from_seller ? 'background:rgba(0,212,255,.12);' : 'background:var(--overlay-soft);' }}">
                    <div style="color:var(--text-primary);font-size:13px;white-space:pre-line;">{{ $message->text }}</div>
                    <small style="color:var(--text-muted);font-size:10px;">{{ $message->from_seller ? 'Tienda' : 'Comprador' }} · {{ $message->sent_at?->format('d/m/Y H:i') }}</small>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('post-sale.reply', $order->id) }}" class="d-flex gap-2">
            @csrf
            <input type="text" name="text" maxlength="350" class="form-control" placeholder="Responder al comprador (máx. 350)" required>
            <button class="btn-neon"><i class="bi bi-send"></i></button>
        </form>
    </div>
@empty
    <div class="empty-state"><i class="bi bi-chat-dots" style="font-size:40px;"></i><p>No hay conversaciones posventa.</p></div>
@endforelse

<div class="mt-3">{{ $conversations->links() }}</div>

@endsection
