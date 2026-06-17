@extends('layouts.app')

@section('title', 'Crear Producto')
@section('page-title', 'Nuevo Producto')

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
        background: rgba(255,255,255,.04);
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
    .input-group-dark .form-control:focus {
        box-shadow: none !important;
    }
    .drop-zone {
        border: 2px dashed var(--border-glow);
        border-radius: 12px;
        padding: 32px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: rgba(0,212,255,.03);
    }
    .drop-zone:hover, .drop-zone.drag-over {
        border-color: var(--neon-blue);
        background: rgba(0,212,255,.06);
    }
    .invalid-text { font-size: 12px; color: #f87171; margin-top: 5px; }
    .form-control.is-invalid { border-color: #f87171 !important; }
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
                <h5 style="color:#fff;font-weight:700;margin:0;">Crear y publicar producto</h5>
                <p style="font-size:12px;color:var(--text-muted);margin:0;">Se publicará automáticamente en Mercado Libre Colombia</p>
            </div>
        </div>

        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Información básica --}}
            <div class="glass-card p-4 mb-4">
                <div class="form-section-title"><i class="bi bi-info-circle"></i>Información básica</div>

                <div class="mb-4">
                    <label class="form-label">Título <span style="color:#f87171;">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title') }}" placeholder="Ej: Camiseta Nike Running Talla M">
                    @error('title')<div class="invalid-text">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                        placeholder="Describe el producto en detalle...">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-text">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Precio (COP) <span style="color:#f87171;">*</span></label>
                        <div class="input-group-dark">
                            <span class="prefix">$</span>
                            <input type="number" name="price" step="0.01" min="0"
                                class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price') }}" placeholder="0">
                        </div>
                        @error('price')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock <span style="color:#f87171;">*</span></label>
                        <input type="number" name="stock" min="0"
                            class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', 0) }}">
                        @error('stock')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Pausado</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Detalles MeLi --}}
            <div class="glass-card p-4 mb-4">
                <div class="form-section-title"><i class="bi bi-grid-3x3-gap"></i>Detalles Mercado Libre</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Condición <span style="color:#f87171;">*</span></label>
                        <select name="condition" class="form-select @error('condition') is-invalid @enderror">
                            <option value="new"  {{ old('condition', 'new') === 'new'  ? 'selected' : '' }}>Nuevo</option>
                            <option value="used" {{ old('condition') === 'used' ? 'selected' : '' }}>Usado</option>
                        </select>
                        @error('condition')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de publicación</label>
                        <select name="listing_type_id" class="form-select">
                            <option value="free"         {{ old('listing_type_id') === 'free'         ? 'selected' : '' }}>Gratuita</option>
                            <option value="bronze"       {{ old('listing_type_id', 'bronze') === 'bronze' ? 'selected' : '' }}>Clásica</option>
                            <option value="gold_special" {{ old('listing_type_id') === 'gold_special' ? 'selected' : '' }}>Premium</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoría</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-text">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Imagen --}}
            <div class="glass-card p-4 mb-4">
                <div class="form-section-title"><i class="bi bi-image"></i>Imagen del producto</div>

                <label class="form-label">Imagen principal</label>
                <div class="drop-zone" id="drop-zone" onclick="document.getElementById('image-input').click()">
                    <div id="drop-default">
                        <i class="bi bi-cloud-upload" style="font-size:32px;color:var(--neon-blue);margin-bottom:10px;display:block;"></i>
                        <p style="font-size:13px;color:var(--text-dim);margin:0;">Arrastra una imagen aquí o haz clic para seleccionar</p>
                        <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">JPG, PNG o WEBP · Máximo 5MB. Se sube a S3 y a Mercado Libre.</p>
                    </div>
                    <div id="drop-preview" style="display:none;">
                        <img id="preview-img" src="" alt="Vista previa"
                            style="max-height:140px;border-radius:8px;border:1px solid var(--border-glow);">
                        <p style="font-size:11px;color:var(--text-muted);margin-top:8px;" id="file-name"></p>
                    </div>
                </div>
                <input type="file" name="image" id="image-input"
                    class="@error('image') is-invalid @enderror"
                    accept="image/*" style="display:none;">
                @error('image')<div class="invalid-text">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-3 justify-content-end">
                <a href="{{ route('products.index') }}" class="btn-ghost">Cancelar</a>
                <button type="submit" class="btn-neon">
                    <i class="bi bi-cloud-upload me-2"></i>Crear y Publicar en MeLi
                </button>
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
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('drop-default').style.display = 'none';
        document.getElementById('drop-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.classList.remove('drag-over');
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    document.getElementById('image-input').files = dt.files;
    document.getElementById('image-input').dispatchEvent(new Event('change'));
});
</script>
@endpush
