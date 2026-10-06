@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios')

@section('content')

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <div class="chart-title mb-3">Registrar usuario</div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form', ['user' => null])
                <button type="submit" class="btn-neon w-100 mt-3"><i class="bi bi-person-plus me-1"></i> Registrar</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card" style="overflow:hidden;">
            <div class="section-header"><span>{{ $users->total() }} usuario(s)</span></div>
            <table class="table dark-table mb-0">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge badge-active">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</span></td>
                            <td class="text-end"><a href="{{ route('users.edit', $user->id) }}" class="btn-ghost" style="padding:4px 10px;"><i class="bi bi-pencil"></i></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>

@endsection
