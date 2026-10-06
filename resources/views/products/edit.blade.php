@extends('layouts.app')

@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')

@push('styles')
<style>
    .form-section-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--neon-blue);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border-glow);
    }
    .input-group-dark {
        display: flex;
        border: 1px solid var(--border-glow);
        border-radius: 10px;
        overflow: hidden;
        background:var(--overlay-soft);
    }
    .input-group-dark .prefix {
        padding: 11px 14px;
        color: var(--text-muted);
        border-right: 1px solid var(--border-glow);
        background: rgba(0,212,255,.04);
        font-size: 13px;
        display: flex;
        align-items: center;
    }
    .input-group-dark .form-control {
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
    }
    .input-group-dark .form-control:focus { box-shadow: none !important; }
    .invalid-text { font-size: 12px; color:var(--danger-fg); margin-top: 5px; }
    .meli-info-box {
        background: rgba(0,212,255,.06);
        border: 1px solid rgba(0,212,255,.2);
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 13px;
        color:var(--info-fg);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .btn-danger-ghost {
        background: rgba(239,68,68,.08);
        border: 1px solid rgba(239,68,68,.2);
        color:var(--danger-fg);
        font-size: 13px;
        border-radius: 10px;
        padding: 9px 18px;
        transition: all .2s;
        cursor: pointer;
    }
    .btn-danger-ghost:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.4); }
</style>
@endpush

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('products.index') }}" class="btn-ghost" style="padding:8px 14px;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 style="color:var(--text-strong);font-weight:700;margin:0;">Editar producto</h5>
                @if($product->meli_item_id)
                    <p style="font-size:12px;color:var(--text-muted);margin:0;">
                        MeLi ID: <code style="color:var(--neon-blue);font-size:11px;">{{ $product->meli_item_id }}</code>
                    </p>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="glass-card p-4 mb-4">
                <div class="form-section-title"><i class="bi bi-info-circle"></i>Información básica</div>

                <div class="mb-4">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $product->title) }}">
                    @error('title')<div class="invalid-text">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Precio (COP)</label>
                        <div class="input-group-dark">
                            <span class="prefix">$</span>
                            <input type="number" name="price" step="0.01" min="0"
                                class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price', $product->price) }}">
                        </div>
                        @error('price')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" min="0"
                            class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', $product->stock) }}">
                        @error('stock')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active"  {{ old('status', $product->status) === 'active'  ? 'selected' : '' }}>Activo</option>
                            <option value="paused"  {{ old('status', $product->status) === 'paused'  ? 'selected' : '' }}>Pausado</option>
                            <option value="closed"  {{ old('status', $product->status) === 'closed'  ? 'selected' : '' }}>Finalizado</option>
                        </select>
                        @error('status')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Imagen --}}
            <div class="glass-card p-4 mb-4">
                <div class="form-section-title"><i class="bi bi-image"></i>Imagen</div>

                @if($product->thumbnail)
                <div class="d-flex align-items-center gap-3 mb-3 p-3"
                     style="background:var(--overlay-soft);border:1px solid var(--border-glow);border-radius:10px;">
                    <img src="{{ $product->thumbnail }}" alt="Actual"
                        style="height:72px;border-radius:8px;object-fit:contain;background:var(--overlay-soft);padding:4px;">
                    <div>
                        <p style="font-size:12px;color:var(--text-dim);margin:0;">Imagen actual</p>
                        <p style="font-size:11px;color:var(--text-muted);margin:2px 0 0;">Sube una nueva para reemplazarla</p>
                    </div>
                </div>
                @endif

                <label class="form-label">Nueva imagen (opcional)</label>
                <input type="file" name="image" id="image-input"
                    class="form-control @error('image') is-invalid @enderror" accept="image/*">
                <p style="font-size:11px;color:var(--text-muted);margin-top:6px;">Deja vacío para mantener la imagen actual. Máximo 5MB.</p>
                @error('image')<div class="invalid-text">{{ $message }}</div>@enderror
                <div id="image-preview" style="display:none;margin-top:10px;">
                    <img id="preview-img" src="" alt="Nueva imagen"
                        style="max-height:100px;border-radius:8px;border:1px solid var(--border-glow);">
                </div>
            </div>

            @if($product->meli_item_id)
                <div class="meli-info-box mb-4">
                    <i class="bi bi-info-circle" style="color:var(--neon-blue);font-size:16px;flex-shrink:0;"></i>
                    Los cambios se sincronizarán automáticamente con Mercado Libre.
                </div>
            @endif

            <div class="d-flex gap-3 justify-content-between">
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                    onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-ghost">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
                <div class="d-flex gap-2">
                    <a href="{{ route('products.index') }}" class="btn-ghost">Cancelar</a>
                    <button type="submit" class="btn-neon">
                        <i class="bi bi-check-lg me-2"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('image-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
        document.getElementById('preview-img').src = ev.target.result;
        document.getElementById('image-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>
@endpush
