<ul class="nav nav-pills mb-4 gap-2" style="--bs-link-decoration:none;">
    <li class="nav-item">
        <a class="{{ request()->routeIs('publisher.create') ? 'btn-neon' : 'btn-ghost' }} text-decoration-none" href="{{ route('publisher.create') }}"><i class="bi bi-upc-scan me-1"></i> Individual</a>
    </li>
    <li class="nav-item">
        <a class="{{ request()->routeIs('publisher.catalog') ? 'btn-neon' : 'btn-ghost' }} text-decoration-none" href="{{ route('publisher.catalog') }}"><i class="bi bi-collection me-1"></i> Catálogo masivo</a>
    </li>
    <li class="nav-item">
        <a class="{{ request()->routeIs('publisher.codes') ? 'btn-neon' : 'btn-ghost' }} text-decoration-none" href="{{ route('publisher.codes') }}"><i class="bi bi-list-ol me-1"></i> Por códigos</a>
    </li>
</ul>
