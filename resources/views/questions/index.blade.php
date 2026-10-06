@extends('layouts.app')

@section('title', 'Preguntas')
@section('page-title', 'Preguntas preventa')

@section('content')

<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('questions.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="answered" class="form-select">
                    <option value="">Todas</option>
                    <option value="0" @selected(request('answered') === '0')>Sin responder</option>
                    <option value="1" @selected(request('answered') === '1')>Respondidas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vista</label>
                <select name="seen" class="form-select">
                    <option value="">Todas</option>
                    <option value="0" @selected(request('seen') === '0')>No vistas</option>
                    <option value="1" @selected(request('seen') === '1')>Vistas</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn-neon w-100"><i class="bi bi-funnel"></i> Filtrar</button></div>
        </div>
    </form>
</div>

@if(!$account)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-triangle-fill"></i> Vincula una cuenta de Mercado Libre.</div>
@endif

@forelse($questions as $question)
    <div class="glass-card p-4 mb-3" style="{{ $question->seen ? '' : 'border-color:var(--neon-blue);' }}">
        <div class="d-flex gap-3">
            <img src="{{ $question->product?->thumbnail ?: 'https://placehold.co/72x72/0d1733/475569?text=-' }}" width="72" height="72" style="object-fit:contain;border-radius:8px;" alt="">
            <div class="flex-fill">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong style="color:var(--text-strong);">{{ $question->product?->title ?? $question->meli_item_id }}</strong>
                        @if($question->product)
                            <span style="color:var(--text-muted);font-size:12px;"> · ${{ number_format($question->product->price, 0, ',', '.') }} · {{ $question->product->sku }}</span>
                        @endif
                    </div>
                    <small style="color:var(--text-muted);">{{ $question->buyer_nickname ?? 'Comprador' }} · {{ $question->asked_at?->format('d/m/Y H:i') }}</small>
                </div>
                <p class="mt-2 mb-2" style="color:var(--text-dim);"><i class="bi bi-chat-left-quote me-1"></i>{{ $question->question }}</p>

                @if($question->isAnswered())
                    <div class="list-row" style="background:rgba(0,255,136,.05);border-radius:8px;padding:10px;">
                        <i class="bi bi-reply me-1" style="color:var(--neon-green);"></i>{{ $question->answer }}
                        <small style="color:var(--text-muted);display:block;">{{ $question->answered_at?->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <form method="POST" action="{{ route('questions.answer', $question->id) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="answer" maxlength="2000" class="form-control" placeholder="Escribe la respuesta..." required>
                        <button class="btn-neon"><i class="bi bi-send"></i></button>
                    </form>
                @endif

                @unless($question->seen)
                    <form method="POST" action="{{ route('questions.seen', $question->id) }}" class="mt-2">
                        @csrf @method('PATCH')
                        <button class="btn-ghost" style="padding:4px 10px;font-size:12px;"><i class="bi bi-eye"></i> Marcar como vista</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
@empty
    <div class="empty-state"><i class="bi bi-chat-square-dots" style="font-size:40px;"></i><p>No hay preguntas.</p></div>
@endforelse

<div class="mt-3">{{ $questions->links() }}</div>

@endsection
