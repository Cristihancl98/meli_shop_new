@extends('layouts.app')

@section('title', 'Editar usuario')
@section('page-title', 'Editar usuario')

@section('content')

<a href="{{ route('users.index') }}" class="btn-ghost mb-4 d-inline-block" style="padding:8px 14px;"><i class="bi bi-arrow-left me-1"></i> Volver</a>

<div class="glass-card p-4" style="max-width:720px;">
    <form method="POST" action="{{ route('users.update', $user->id) }}">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user])
        <button type="submit" class="btn-neon mt-3"><i class="bi bi-save me-1"></i> Guardar</button>
    </form>
</div>

@endsection
