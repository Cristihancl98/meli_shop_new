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
    <form method="GET" action="{{ route('publisher.create') }}" id="sku-lookup-form">
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
                        <label class="form-label" for="category-id">Categoría MeLi</label>
                        <div class="d-flex gap-2">
                            <input type="text" name="meli_category_id" id="category-id" class="form-control" value="{{ old('meli_category_id', $suggestedCategory) }}" placeholder="MCO1234" required>
                            <button type="button" id="category-open" class="btn-ghost d-inline-flex align-items-center text-decoration-none" style="padding:0 12px;"
                                    data-url="{{ route('publisher.categories') }}" title="Elegir categoría de Mercado Libre" aria-label="Elegir categoría de Mercado Libre" @disabled(!$account)>
                                <i class="bi bi-diagram-3"></i>
                            </button>
                        </div>
                        <small id="category-path" class="d-block" style="color:var(--text-dim);font-size:11px;"></small>
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
@elseif($scraperUrl)
    <div class="empty-state" id="scraping-placeholder">
        <i class="bi bi-hourglass-split" style="font-size:40px;"></i>
        <p>El SKU <strong>{{ $sku }}</strong> no está en el catálogo. Se está consultando en Amazon a través del servicio de scraping; la página se actualizará sola.</p>
    </div>
@elseif(!$lookupError)
    <div class="empty-state">
        <i class="bi bi-upc-scan" style="font-size:40px;"></i>
        <p>Consulta un SKU para buscarlo en el catálogo y calcular su precio de venta.</p>
    </div>
@endif

<div class="modal fade" id="category-modal" tabindex="-1" aria-labelledby="category-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title" id="category-modal-title" style="color:var(--text-strong);"><i class="bi bi-diagram-3 me-1"></i> Categoría de Mercado Libre</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="px-3 pt-2">
                <nav id="category-breadcrumb" class="d-flex flex-wrap align-items-center gap-1 mb-2" style="font-size:12px;"></nav>
                <input type="search" id="category-filter" class="form-control form-control-sm" placeholder="Filtrar este nivel…" autocomplete="off">
            </div>
            <div class="modal-body pt-2">
                <div id="category-selected" class="d-none flash-alert mb-2" style="background:var(--hover-bg);border:1px solid var(--border-glow);color:var(--text-dim);">
                    <i class="bi bi-check2-circle" style="color:var(--neon-green);"></i>
                    <span><strong id="category-selected-name"></strong> <code id="category-selected-id"></code> es una categoría final.</span>
                    <button type="button" id="category-use" class="btn-neon ms-auto" style="padding:4px 14px;">Usar esta categoría</button>
                </div>
                <div id="category-message" class="text-center py-4 d-none" style="color:var(--text-muted);font-size:13px;"></div>
                <div id="category-list" class="list-group list-group-flush"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loading-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loading-modal-title" aria-hidden="true"
     @if($scraperUrl) data-scraper-url="{{ $scraperUrl }}" data-reload-url="{{ route('publisher.create', ['sku' => $sku, 'scraped' => 1]) }}" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 p-4 text-center">
            <div class="spinner-border mx-auto mb-3" role="status" style="color:var(--neon-blue);width:3rem;height:3rem;"></div>
            <h6 id="loading-modal-title" class="mb-2" style="color:var(--text-strong);">Buscando el producto…</h6>
            <p id="loading-modal-text" class="mb-1" style="color:var(--text-muted);font-size:13px;">Consultando el catálogo.</p>
            <small id="loading-modal-elapsed" style="color:var(--text-faint);font-size:11px;"></small>
            <div id="loading-modal-actions" class="mt-3 d-none">
                <a href="{{ route('publisher.create', ['sku' => $sku]) }}" class="btn-neon me-2">Reintentar</a>
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const openButton = document.getElementById('category-open');
    if (!openButton) return;

    const input       = document.getElementById('category-id');
    const pathLabel   = document.getElementById('category-path');
    const modal       = bootstrap.Modal.getOrCreateInstance(document.getElementById('category-modal'));
    const breadcrumb  = document.getElementById('category-breadcrumb');
    const filter      = document.getElementById('category-filter');
    const list        = document.getElementById('category-list');
    const message     = document.getElementById('category-message');
    const selected    = document.getElementById('category-selected');
    const url         = openButton.dataset.url;
    let current       = null;

    const fetchLevel = async (categoryId) => (await axios.get(url, { params: categoryId ? { category_id: categoryId } : {} })).data.data;

    const pathText = (level) => [...level.path.map((node) => node.name)].join(' › ');

    const showMessage = (text) => {
        message.textContent = text;
        message.classList.toggle('d-none', !text);
    };

    const crumb = (label, categoryId, active) => {
        const element = document.createElement(active ? 'span' : 'button');
        element.textContent = label;
        element.style.color = active ? 'var(--text-strong)' : 'var(--neon-blue)';
        if (!active) {
            element.type = 'button';
            element.className = 'btn btn-link p-0 border-0 text-decoration-none';
            element.style.fontSize = '12px';
            element.addEventListener('click', () => load(categoryId));
        }
        return element;
    };

    const render = (level) => {
        current = level;
        filter.value = '';

        breadcrumb.replaceChildren(crumb('Todas', null, level.id === null));
        level.path.forEach((node, index) => {
            const separator = document.createElement('span');
            separator.textContent = '›';
            separator.style.color = 'var(--text-faint)';
            breadcrumb.append(separator, crumb(node.name, node.id, index === level.path.length - 1));
        });

        selected.classList.toggle('d-none', !level.is_leaf);
        if (level.is_leaf) {
            document.getElementById('category-selected-name').textContent = level.name;
            document.getElementById('category-selected-id').textContent = level.id;
        }

        list.replaceChildren(...level.children.map((child) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            item.style.cssText = 'background:transparent;color:var(--text-dim);border-color:var(--border-glow);font-size:13px;';
            item.dataset.name = child.name.toLowerCase();
            item.innerHTML = '<span></span><i class="bi bi-chevron-right" style="color:var(--text-faint);"></i>';
            item.firstElementChild.textContent = child.name;
            item.addEventListener('click', () => load(child.id));
            return item;
        }));

        showMessage('');
        filter.classList.toggle('d-none', level.children.length === 0);
    };

    const load = async (categoryId) => {
        list.replaceChildren();
        selected.classList.add('d-none');
        showMessage('Cargando categorías…');

        try {
            render(await fetchLevel(categoryId));
        } catch (error) {
            if (categoryId && error.response?.status === 422) {
                return load(null);
            }
            showMessage(error.response?.data?.message ?? 'No fue posible consultar las categorías de Mercado Libre.');
        }
    };

    filter.addEventListener('input', () => {
        const term = filter.value.trim().toLowerCase();
        list.querySelectorAll('[data-name]').forEach((item) => item.classList.toggle('d-none', !item.dataset.name.includes(term)));
    });

    document.getElementById('category-use').addEventListener('click', () => {
        input.value = current.id;
        pathLabel.textContent = pathText(current);
        modal.hide();
    });

    openButton.addEventListener('click', () => {
        modal.show();
        load(input.value.trim() || null);
    });

    input.addEventListener('change', () => { pathLabel.textContent = ''; });

    if (input.value.trim()) {
        fetchLevel(input.value.trim()).then((level) => { pathLabel.textContent = pathText(level); }).catch(() => {});
    }
})();

(function () {
    const modalElement = document.getElementById('loading-modal');
    const modal        = bootstrap.Modal.getOrCreateInstance(modalElement);
    const title        = document.getElementById('loading-modal-title');
    const text         = document.getElementById('loading-modal-text');
    const elapsed      = document.getElementById('loading-modal-elapsed');
    const actions      = document.getElementById('loading-modal-actions');
    const scraperUrl   = modalElement.dataset.scraperUrl;
    const reloadUrl    = modalElement.dataset.reloadUrl;
    const MAX_WAIT_MS  = 5 * 60 * 1000;

    const show = (heading, message) => {
        title.textContent = heading;
        text.textContent  = message;
        modal.show();
    };

    document.getElementById('sku-lookup-form').addEventListener('submit', () => {
        show('Buscando el producto…', 'Consultando el catálogo.');
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) modal.hide();
    });

    if (!scraperUrl) return;

    const startedAt = Date.now();
    show('Consultando Amazon…', 'El producto no está en el catálogo. Se está obteniendo de Amazon con el servicio de scraping; puede tardar 1 o 2 minutos.');

    const tick = setInterval(() => {
        elapsed.textContent = Math.round((Date.now() - startedAt) / 1000) + ' s';
    }, 1000);

    const fail = (heading, message) => {
        clearInterval(tick);
        document.querySelector('#loading-modal .spinner-border').classList.add('d-none');
        title.textContent = heading;
        text.textContent  = message;
        actions.classList.remove('d-none');
    };

    const scrape = async () => {
        const controller = new AbortController();
        const timeout    = setTimeout(() => controller.abort(), MAX_WAIT_MS);

        try {
            const response = await fetch(scraperUrl, { signal: controller.signal });
            const data     = await response.json().catch(() => ({}));
            const exists   = /ya existe/i.test(data.error ?? '');

            if ((response.ok && data.titulo) || exists) {
                window.location.assign(reloadUrl);
                return;
            }

            fail('No fue posible obtener el producto', data.error ?? ('El scraper respondió HTTP ' + response.status + '.'));
        } catch (error) {
            error.name === 'AbortError'
                ? fail('La consulta tardó demasiado', 'El scraper no respondió en 5 minutos. Inténtalo de nuevo.')
                : fail('No se pudo conectar con el scraper', 'Verifica que ' + new URL(scraperUrl).origin + ' sea accesible desde el navegador y use HTTPS.');
        } finally {
            clearTimeout(timeout);
        }
    };

    scrape();
})();

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
