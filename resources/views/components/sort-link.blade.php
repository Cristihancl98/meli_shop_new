<a href="{{ $href }}" class="sort-link {{ $active ? 'active' : '' }}">
    {{ $slot->isEmpty() ? $column->label() : $slot }}
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
</a>
