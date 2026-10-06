@extends('layouts.app')

@section('title', 'Configuración')
@section('page-title', 'Configuración de la tienda')

@section('content')

@if(!$account)
    <div class="flash-alert mb-4" style="background:var(--hover-bg);border:1px solid var(--border-glow);color:var(--text-dim);">
        <i class="bi bi-info-circle"></i>
        Esta configuración es de la tienda y aplica a todas sus cuentas. La reputación aparecerá al vincular una cuenta de Mercado Libre.
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-label">Reputación</div>
            <div class="metric-value" style="text-transform:capitalize;">{{ $reputation['reputation'] ?? 'Sin datos' }}</div>
            <small style="color:var(--text-muted);">{{ $account ? 'Actualizada: ' . ($reputation['updated_at'] ?? '—') : 'Sin cuenta vinculada' }}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-label">Dólar de la tienda</div>
            <div class="metric-value">${{ number_format($settings['dollar_price'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card">
            <div class="metric-label">Tipo de publicación</div>
            <div class="metric-value" style="font-size:20px;">{{ $settings['listing_type'] }}</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3"><i class="bi bi-calculator me-1"></i> Costos y comisiones</div>
                <div class="row g-3">
                    @foreach([
                        'amazon_commission' => ['Comisión Amazon (%)', '0.01'],
                        'iva'               => ['IVA (%)', '0.01'],
                        'meli_commission'   => ['Comisión Mercado Libre (%)', '0.01'],
                        'logistics_price'   => ['Precio logística (USD)', '0.01'],
                        'shipping_price'    => ['Precio envío (USD)', '0.01'],
                        'dollar_price'      => ['Precio del dólar (COP)', '0.01'],
                    ] as $field => [$label, $step])
                        <div class="col-md-6">
                            <label class="form-label">{{ $label }}</label>
                            <input type="number" step="{{ $step }}" min="0" name="{{ $field }}" class="form-control"
                                   value="{{ old($field, $settings[$field]) }}" @disabled(!$canEdit)>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3"><i class="bi bi-megaphone me-1"></i> Publicación</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de publicación</label>
                        <select name="listing_type" class="form-select" @disabled(!$canEdit)>
                            @foreach(['gold_special' => 'Clásica', 'gold_pro' => 'Premium', 'free' => 'Gratuita'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('listing_type', $settings['listing_type']) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad por defecto</label>
                        <input type="number" min="1" name="default_quantity" class="form-control" value="{{ old('default_quantity', $settings['default_quantity']) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Días de disponibilidad</label>
                        <input type="number" min="0" name="manufacturing_days" class="form-control" value="{{ old('manufacturing_days', $settings['manufacturing_days']) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Días de garantía</label>
                        <input type="number" min="0" name="warranty_days" class="form-control" value="{{ old('warranty_days', $settings['warranty_days']) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="col-12">
                        <label class="form-label">URL del servicio de scraping</label>
                        <input type="url" name="scraping_url" class="form-control" placeholder="https://scraper.midominio.com/"
                               value="{{ old('scraping_url', $settings['scraping_url'] ?? '') }}" @disabled(!$canEdit)>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3">Envío USA → Colombia (USD por lb)</div>
                @include('settings._ranges', ['name' => 'weight_ranges', 'rows' => $settings['weight_ranges'], 'amountField' => 'price', 'amountLabel' => 'USD/lb', 'fromLabel' => 'Desde (lb)', 'toLabel' => 'Hasta (lb)', 'disabled' => !$canEdit])
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3">Impuesto nacional por peso (USD)</div>
                @include('settings._ranges', ['name' => 'national_tax_ranges', 'rows' => $settings['national_tax_ranges'], 'amountField' => 'price', 'amountLabel' => 'USD', 'fromLabel' => 'Desde (lb)', 'toLabel' => 'Hasta (lb)', 'disabled' => !$canEdit])
            </div>
        </div>
        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3">Ganancia por precio base</div>
                @include('settings._ranges', ['name' => 'profit_ranges', 'rows' => $settings['profit_ranges'], 'amountField' => 'percentage', 'amountLabel' => '%', 'fromLabel' => 'Desde (USD)', 'toLabel' => 'Hasta (USD)', 'disabled' => !$canEdit])
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3"><i class="bi bi-chat-heart me-1"></i> Mensaje automático de venta</div>
                <input type="hidden" name="sale_message_enabled" value="0">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="sale_message_enabled" name="sale_message_enabled" value="1"
                           @checked(old('sale_message_enabled', $settings['sale_message_enabled'])) @disabled(!$canEdit)>
                    <label class="form-check-label" for="sale_message_enabled" style="color:var(--text-dim);">Enviar al comprador al confirmarse la venta</label>
                </div>
                <textarea name="sale_message" rows="5" maxlength="350" class="form-control" @disabled(!$canEdit)>{{ old('sale_message', $settings['sale_message']) }}</textarea>
                <small style="color:var(--text-muted);font-size:11px;">Máximo 350 caracteres.</small>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass-card p-4 h-100">
                <div class="chart-title mb-3"><i class="bi bi-file-text me-1"></i> Plantilla de descripción</div>
                <textarea name="description_template" rows="7" class="form-control" @disabled(!$canEdit)>{{ old('description_template', $settings['description_template']) }}</textarea>
                <small style="color:var(--text-muted);font-size:11px;">Se agrega al final de la descripción de cada publicación.</small>
            </div>
        </div>
    </div>

    @if($canEdit)
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn-neon"><i class="bi bi-save me-1"></i> Guardar configuración</button>
        </div>
    @endif
</form>
@endsection
