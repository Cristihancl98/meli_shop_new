<?php

namespace App\View\Components;

use App\Enums\ProductSort;
use Illuminate\View\Component;
use Illuminate\View\View;

class SortLink extends Component
{
    public bool $active;
    public string $href;
    public string $icon;

    public function __construct(public ProductSort $column, ProductSort $current, string $direction)
    {
        $this->active = $column === $current;
        $next         = $this->active ? ($direction === 'asc' ? 'desc' : 'asc') : $column->defaultDirection();
        $this->href   = request()->fullUrlWithQuery(['sort' => $column->value, 'direction' => $next, 'page' => null]);
        $this->icon   = !$this->active ? 'bi-arrow-down-up' : ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down');
    }

    public function render(): View
    {
        return view('components.sort-link');
    }
}
