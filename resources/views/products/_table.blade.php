<div class="glass-card" style="overflow:hidden;">
    <div class="table-responsive">
        <table class="table dark-table mb-0 products-table">
            <thead>
                <tr>
                    <th style="width:56px;"></th>
                    <th><x-sort-link :column="\App\Enums\ProductSort::Title" :current="$sort" :direction="$direction">Producto</x-sort-link></th>
                    <th><x-sort-link :column="\App\Enums\ProductSort::Sku" :current="$sort" :direction="$direction" /></th>
                    <th class="text-end"><x-sort-link :column="\App\Enums\ProductSort::Price" :current="$sort" :direction="$direction" /></th>
                    <th class="text-end"><x-sort-link :column="\App\Enums\ProductSort::Stock" :current="$sort" :direction="$direction" /></th>
                    <th class="text-end"><x-sort-link :column="\App\Enums\ProductSort::Sold" :current="$sort" :direction="$direction" /></th>
                    <th><x-sort-link :column="\App\Enums\ProductSort::Status" :current="$sort" :direction="$direction" /></th>
                    <th><x-sort-link :column="\App\Enums\ProductSort::Published" :current="$sort" :direction="$direction">Publicado</x-sort-link></th>
                    <th><x-sort-link :column="\App\Enums\ProductSort::Updated" :current="$sort" :direction="$direction">Actualizado</x-sort-link></th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>
                            <img src="{{ $product->thumbnail ?: 'https://placehold.co/80x80/0d1733/475569?text=%E2%80%94' }}"
                                 alt="" class="product-thumb" loading="lazy"
                                 onerror="this.src='https://placehold.co/80x80/0d1733/475569?text=%E2%80%94'">
                        </td>
                        <td class="product-cell">
                            <a href="{{ route('products.show', $product) }}" class="product-cell-title" title="{{ $product->title }}">{{ $product->title }}</a>
                            <small>{{ $product->meli_item_id ?? 'Sin publicar' }}@if($product->category) · {{ $product->category->name }}@endif</small>
                        </td>
                        <td>@if($product->sku)<code class="dark">{{ $product->sku }}</code>@else<span style="color:var(--text-faint);">—</span>@endif</td>
                        <td class="text-end num"><strong style="color:var(--text-strong);">${{ number_format($product->price, 0, ',', '.') }}</strong></td>
                        <td class="text-end num {{ $product->stock <= 5 ? 'stock-low' : '' }}">{{ number_format($product->stock, 0, ',', '.') }}</td>
                        <td class="text-end num">{{ number_format((int) $product->sold_quantity, 0, ',', '.') }}</td>
                        <td><span class="badge badge-{{ $product->status }}" style="font-size:10px;">{{ $product->status_label }}</span></td>
                        <td class="num" style="white-space:nowrap;">{{ ($product->published_at ?? $product->created_at)?->format('d/m/Y') }}</td>
                        <td style="white-space:nowrap;" title="{{ $product->updated_at->format('d/m/Y H:i') }}">{{ $product->updated_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('products.show', $product) }}" class="btn-ghost" style="padding:5px 9px;" title="Ver"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('products.edit', $product) }}" class="btn-ghost" style="padding:5px 9px;color:var(--neon-blue);" title="Editar"><i class="bi bi-pencil"></i></a>
                                @if($product->meli_item_id)
                                    <form method="POST" action="{{ route('products.sync', $product) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn-ghost" style="padding:5px 9px;color:var(--success-fg);" title="Sincronizar"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
