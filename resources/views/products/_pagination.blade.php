<div class="products-pagination d-flex justify-content-between align-items-center gap-2 flex-wrap {{ $position === 'top' ? 'mb-3' : 'mt-4' }}">
    <p style="font-size:12px;color:var(--text-muted);margin:0;">
        Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }}
        de {{ $products->total() }} productos
    </p>
    {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
</div>
