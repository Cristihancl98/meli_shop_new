@extends('layouts.auth')

@section('title', 'Acceso')

@section('card-class', 'login-card-light')

@section('header')
@endsection

@section('form')

            <div class="store-heading text-center mb-4">
                <div class="store-heading-label"><i class="bi bi-shop me-1"></i>Tienda</div>
                <h1 class="store-heading-name">{{ $store?->name }}</h1>
                <form method="POST" action="{{ route('connect.change') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 store-heading-change">Cambiar tienda</button>
                </form>
            </div>

            @if ($errors->any())
                <div class="cyber-alert">
                    <i class="bi bi-shield-exclamation"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="cyber-alert cyber-alert-success">
                    <i class="bi bi-check-circle"></i>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-1">
                    <label class="field-label" for="email">Identificador de acceso</label>
                    <div class="input-wrap">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="cyber-input @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="correo@empresa.com"
                            autofocus
                            autocomplete="email"
                            required
                        >
                        <i class="bi bi-at input-icon"></i>
                    </div>
                </div>

                <div class="mb-1">
                    <label class="field-label" for="password">Clave de seguridad</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="cyber-input"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <i class="bi bi-shield-lock input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-cyber">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Acceder al sistema
                </button>
            </form>

@endsection
