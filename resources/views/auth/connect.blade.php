@extends('layouts.auth')

@section('title', 'Conexión')

@section('card-class', 'login-card-light')

@section('header')
@endsection

@section('form')

            @include('auth._logo')

            @if ($errors->any())
                <div class="cyber-alert">
                    <i class="bi bi-shield-exclamation"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('connect.store') }}">
                @csrf

                <div class="mb-1">
                    <label class="field-label" for="connection_code">Código de conexión de la tienda</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="connection_code"
                            name="connection_code"
                            class="cyber-input @error('connection_code') is-invalid @enderror"
                            value="{{ old('connection_code') }}"
                            placeholder="Código entregado por el administrador"
                            maxlength="40"
                            autocomplete="off"
                            autofocus
                            required
                        >
                        <i class="bi bi-key input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-cyber">
                    <i class="bi bi-plug me-2"></i>
                    Conectar tienda
                </button>
            </form>

@endsection
