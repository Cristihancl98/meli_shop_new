<div class="row g-3">
    @foreach($products as $product)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="product-card">
                <img
                    src="{{ $product->thumbnail ?: 'https://placehold.co/300x180/0d1733/475569?text=Sin+imagen' }}"
                    alt="{{ $product->title }}"
                    class="product-img"
                    loading="lazy"
                    onerror="this.src='https://placehold.co/300x180/0d1733/475569?text=Sin+imagen'"
                >
                <div class="product-body">
                    <p class="product-title" title="{{ $product->title }}">{{ $product->title }}</p>
                    <div class="product-meta mb-1">@include('products._meli-link')</div>

                    <div class="product-price">
                        ${{ number_format($product->price, 0, ',', '.') }}
                        <small style="font-size:10px;color:var(--text-muted);font-weight:400;">COP</small>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="product-meta">
                            <i class="bi bi-box me-1"></i>{{ $product->stock }} en stock
                        </span>
                        <span class="badge badge-{{ $product->status }}" style="font-size:9px;">{{ $product->status_label }}</span>
                    </div>

                    <p class="product-meta mb-3" style="font-size:11px;">
                        <i class="bi bi-clock me-1"></i>
                        {{ $product->updated_at->diffForHumans() }}
                    </p>

                    <div class="d-flex gap-1">
                        <a href="{{ route('products.show', $product) }}"
                           class="btn-ghost flex-fill text-center" style="padding:7px;font-size:13px;" title="Ver">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('products.edit', $product) }}"
                           class="btn-ghost flex-fill text-center" style="padding:7px;font-size:13px;color:var(--neon-blue);" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($product->meli_item_id && auth()->user()->can('sync', $product))
                            <form method="POST" action="{{ route('products.sync', $product) }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn-ghost w-100" style="padding:7px;font-size:13px;color:var(--success-fg);" title="Consultar en Mercado Libre y actualizar estado">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
