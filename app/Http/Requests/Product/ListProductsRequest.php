<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    public const VIEWS = ['cards', 'table'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string', 'max:255'],
            'status'       => ['nullable', 'string', 'max:30'],
            'category_id'  => ['nullable', 'integer'],
            'min_price'    => ['nullable', 'numeric', 'min:0'],
            'max_price'    => ['nullable', 'numeric', 'min:0'],
            'sku'          => ['nullable', 'string', 'max:100'],
            'published_on' => ['nullable', 'date'],
            'sort'         => ['nullable', Rule::enum(ProductSort::class)],
            'direction'    => ['nullable', 'in:asc,desc'],
            'view'         => ['nullable', Rule::in(self::VIEWS)],
        ];
    }

    public function filters(): array
    {
        return collect($this->validated())->except('view')->filter(fn ($value) => $value !== null && $value !== '')->all();
    }
}
