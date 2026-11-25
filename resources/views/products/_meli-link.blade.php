@if($product->meli_url)
    <a href="{{ $product->meli_url }}" target="_blank" rel="noopener noreferrer" class="meli-link"
       title="Abrir {{ $product->meli_item_id }} en Mercado Libre">{{ $product->meli_item_id }}<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
@else
    <span class="meli-link-none">Sin publicar</span>
@endif
