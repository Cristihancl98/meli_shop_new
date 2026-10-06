@extends('layouts.app')

@section('title', 'Publicador')
@section('page-title', 'Publicador individual')

@section('content')

@include('publisher._tabs')

@if(!$account)
    <div class="flash-alert flash-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i> Puedes consultar productos, pero para publicar debes vincular una cuenta de Mercado Libre.
    </div>
@endif

<div class="filter-panel mb-4">
    <form method="GET" action="{{ route('publisher.create') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">SKU / ASIN de Amazon</label>
                <input type="text" name="sku" class="form-control" placeholder="Ej: B0C1234567" value="{{ $sku }}" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn-neon w-100"><i class="bi bi-search me-1"></i> Consultar</button>
            </div>
        </div>
    </form>
</div>

@if($lookupError)
    <div class="flash-alert flash-danger mb-4"><i class="bi bi-exclamation-circle-fill"></i> {{ $lookupError }}</div>
@endif

@if($lookup)
<div class="flash-alert mb-4" style="background:var(--hover-bg);border:1px solid var(--border-glow);color:var(--text-dim);">
    <i class="bi bi-{{ $lookup['source'] === 'catalog' ? 'database-check' : 'globe2' }}"></i>
    {{ $lookup['source'] === 'catalog' ? 'Producto encontrado en el catálogo (MongoDB).' : 'Producto obtenido del servicio de scraping.' }}
    <a href="https://www.amazon.com/dp/{{ urlencode($lookup['sku'] ?? $sku) }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none ms-1" title="Ver en Amazon">
        <code class="dark">{{ $lookup['sku'] ?? $sku }} <i class="bi bi-box-arrow-up-right"></i></code>
    </a>
</div>

<form method="POST" action="{{ route('publisher.store') }}">
    @csrf
    <input type="hidden" name="sku" value="{{ $sku }}">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 mb-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Título (máx. 60)</label>
                        <input type="text" name="title" maxlength="60" class="form-control" value="{{ old('title', $lookup['titulo_meli'] ?: mb_substr((string) $lookup['titulo'], 0, 60)) }}" required>
                        @if(mb_strlen((string) $lookup['titulo']) > 60)
                            <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:4px;">Título original: {{ $lookup['titulo'] }}</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoría MeLi</label>
                        <input type="text" name="meli_category_id" class="form-control" value="{{ old('meli_category_id', $suggestedCategory) }}" placeholder="MCO1234" required>
                        @if($lookup['categoria_meli'])
                            <small style="color:var(--neon-green);font-size:11px;">Categoría del catálogo</small>
                        @elseif($suggestedCategory)
                            <small style="color:var(--neon-green);font-size:11px;">Sugerida por Mercado Libre</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marca</label>
                        <input type="text" name="brand" class="form-control" value="{{ old('brand', $lookup['marca'] ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="model" class="form-control" value="{{ old('model', $lookup['modelo'] ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Peso (lb)</label>
                        <input type="number" step="any" min="0" name="weight" id="price-weight" class="form-control" value="{{ old('weight', $lookup['peso'] ?? 0) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Alto (cm)</label>
                        <input type="number" step="any" min="0" name="height" class="form-control" value="{{ old('height', $lookup['alto'] ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ancho (cm)</label>
                        <input type="number" step="any" min="0" name="width" class="form-control" value="{{ old('width', $lookup['ancho'] ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Largo (cm)</label>
                        <input type="number" step="any" min="0" name="length" class="form-control" value="{{ old('length', $lookup['largo'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">EAN / UPC</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="ean" inputmode="numeric" class="form-control" placeholder="Código universal" value="{{ old('ean', $lookup['ean'] ?? '') }}">
                            <a href="https://ca.camelcamelcamel.com/product/{{ urlencode($lookup['sku'] ?? $sku) }}" target="_blank" rel="noopener noreferrer"
                               class="btn-ghost d-inline-flex align-items-center text-decoration-none" style="padding:0 12px;"
                               title="Buscar el código universal en camelcamelcamel" aria-label="Buscar el código universal en camelcamelcamel">
                                <i class="bi bi-search"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad</label>
                        <input type="number" min="1" name="quantity" class="form-control" value="{{ old('quantity', $lookup['default_quantity']) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price-base">Precio del artículo (USD)</label>
                        <div class="d-flex gap-2">
                            <input type="number" step="0.01" min="0" name="base_price" id="price-base" class="form-control" value="{{ old('base_price', $lookup['precio'] ?? 0) }}" required>
                            <button type="button" id="price-recalculate" class="btn-ghost text-nowrap d-inline-flex align-items-center gap-1" data-url="{{ route('publisher.price') }}">
                                <i class="bi bi-arrow-repeat"></i> Recalcular
                            </button>
                        </div>
                        <small id="price-status" class="d-block mt-1" style="font-size:11px;color:var(--text-muted);">Cambia el precio o el peso y pulsa Recalcular.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price-final">Precio final (COP)</label>
                        <input type="number" min="1" step="1" name="final_price" id="price-final" class="form-control" value="{{ old('final_price', $lookup['final_price']) }}" required>
                        <small class="d-block mt-1" style="font-size:11px;color:var(--text-muted);">Calculado con la configuración de la tienda; puedes ajustarlo a mano.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Imágenes</label>
                        <x-picture-editor :pictures="(array) old('pictures', $lookup['imagenes'] ?? [])" preview="#publisher-preview" />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" rows="8" class="form-control">{{ old('description', $lookup['descripcion'] ?? '') }}</textarea>
                        <small style="color:var(--text-muted);font-size:11px;">La plantilla de descripción configurada se agrega automáticamente al final.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Atributos adicionales</label>
                        <div class="row g-2">
                            @foreach(array_pad($lookup['atributos_extra'], max(3, count($lookup['atributos_extra']) + 1), []) as $i => $extra)
                                <div class="col-md-6">
                                    <input type="text" name="extra_attributes[{{ $i }}][id]" class="form-control form-control-sm" placeholder="ID (ej. COLOR)" value="{{ old("extra_attributes.{$i}.id", $extra['id'] ?? '') }}" title="{{ $extra['name'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="extra_attributes[{{ $i }}][value]" class="form-control form-control-sm" placeholder="{{ $extra['name'] ?? 'Valor' }}" value="{{ old("extra_attributes.{$i}.value", $extra['value'] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                        <small style="color:var(--text-muted);font-size:11px;">Los atributos sin valor no se envían a Mercado Libre.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-3 mb-4 text-center">
                <img id="publisher-preview" src="{{ ($lookup['imagenes'] ?? [])[0] ?? 'https://placehold.co/400x300/0d1733/475569?text=Sin+imagen' }}" class="img-fluid rounded-2" style="max-height:240px;object-fit:contain;" alt="">
            </div>
            <div class="glass-card p-4 mb-4">
                <div class="chart-title mb-3">Detalle del precio</div>
                <table class="table dark-table mb-0">
                    <tbody>
                        @foreach([
                            'base_price' => 'Precio Amazon (USD)', 'amazon_commission' => 'Comisión Amazon', 'iva' => 'IVA',
                            'logistics_price' => 'Logística', 'shipping_price' => 'Envío', 'national_tax' => 'Impuesto nacional',
                            'usa_shipping' => 'Envío USA', 'profit' => 'Ganancia', 'meli_commission' => 'Comisión MeLi',
                            'subtotal_usd' => 'Subtotal (USD)', 'dollar_price' => 'Dólar (COP)',
                        ] as $key => $label)
                            <tr><td>{{ $label }}</td><td class="text-end" data-breakdown="{{ $key }}">{{ number_format($lookup['price_breakdown'][$key], 2, ',', '.') }}</td></tr>
                        @endforeach
                        <tr><td><strong>Precio final</strong></td><td class="text-end"><strong id="breakdown-final" style="color:var(--neon-blue);">${{ number_format($lookup['final_price'], 0, ',', '.') }}</strong></td></tr>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn-neon w-100" @disabled(!$account)><i class="bi bi-cloud-upload me-1"></i> Publicar en Mercado Libre</button>
            @unless($account)
                <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:6px;text-align:center;">Vincula una cuenta de Mercado Libre para publicar.</small>
            @endunless
        </div>
    </div>
</form>
@elseif(!$lookupError)
    <div class="empty-state">
        <i class="bi bi-upc-scan" style="font-size:40px;"></i>
        <p>Consulta un SKU para buscarlo en el catálogo y calcular su precio de venta.</p>
    </div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const button = document.getElementById('price-recalculate');
    if (!button) return;

    const base    = document.getElementById('price-base');
    const weight  = document.getElementById('price-weight');
    const final   = document.getElementById('price-final');
    const status  = document.getElementById('price-status');
    const decimal = new Intl.NumberFormat('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pesos   = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

    const setStatus = (text, color) => { status.textContent = text; status.style.color = color; };

    const recalculate = async () => {
        if (!base.reportValidity() || !weight.reportValidity()) return;

        button.disabled = true;
        button.querySelector('i').classList.add('spin');
        setStatus('Recalculando…', 'var(--text-muted)');

        try {
            const { data } = await axios.get(button.dataset.url, { params: { base_price: base.value, weight: weight.value } });
            const result = data.data;

            final.value = Math.round(result.final_price);
            document.querySelectorAll('[data-breakdown]').forEach((cell) => {
                cell.textContent = decimal.format(result[cell.dataset.breakdown] ?? 0);
            });
            document.getElementById('breakdown-final').textContent = '$' + pesos.format(result.final_price);
            setStatus('Precio recalculado: $' + pesos.format(result.final_price) + ' COP', 'var(--success-fg)');
        } catch (error) {
            const message = error.response?.data?.message ?? 'No fue posible recalcular el precio.';
            setStatus(message, 'var(--danger-fg)');
        } finally {
            button.disabled = false;
            button.querySelector('i').classList.remove('spin');
        }
    };

    button.addEventListener('click', recalculate);
    [base, weight].forEach((input) => input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); recalculate(); }
    }));
})();
</script>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    .spin { display: inline-block; animation: spin .8s linear infinite; }
</style>
@endpush
