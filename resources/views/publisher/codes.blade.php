@extends('layouts.app')

@section('title', 'Códigos')
@section('page-title', 'Publicador masivo — por códigos')

@section('content')

@include('publisher._tabs')

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <div class="chart-title mb-3">Cargar códigos</div>
            <form method="POST" action="{{ route('publisher.codes.store') }}">
                @csrf
                <textarea name="codes" rows="12" class="form-control mb-3" placeholder="Un SKU por línea, o separados por coma" required>{{ old('codes') }}</textarea>
                <button type="submit" class="btn-neon w-100" @disabled(!$account)><i class="bi bi-plus-lg me-1"></i> Registrar códigos</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-card" style="overflow:hidden;">
            <div class="section-header d-flex justify-content-between align-items-center">
                <span>{{ $date ? "Códigos cargados el {$date}" : 'Códigos pendientes' }} ({{ $codes->count() }})</span>
                <form method="GET" action="{{ route('publisher.codes') }}" class="d-flex gap-2">
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
                    <button class="btn-ghost" style="padding:4px 10px;"><i class="bi bi-funnel"></i></button>
                </form>
            </div>
            @if($codes->isEmpty())
                <div class="empty-state"><p>Sin códigos.</p></div>
            @else
                <table class="table dark-table mb-0">
                    <thead><tr><th>SKU</th><th>Estado</th><th>Cargado</th><th></th></tr></thead>
                    <tbody>
                        @foreach($codes as $code)
                            <tr>
                                <td><code class="dark">{{ $code->sku }}</code></td>
                                <td><span class="badge badge-{{ $code->status === 'pending' ? 'pending' : ($code->status === 'published' ? 'active' : 'cancelled') }}">{{ $code->status }}</span></td>
                                <td>{{ $code->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end"><a href="{{ route('publisher.create', ['sku' => $code->sku]) }}" class="btn-ghost text-decoration-none" style="padding:4px 10px;" title="Publicar"><i class="bi bi-cloud-upload"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

@endsection
