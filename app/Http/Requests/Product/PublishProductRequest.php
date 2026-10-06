<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class PublishProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pictures = $this->input('pictures');

        if (is_string($pictures)) {
            $pictures = preg_split('/[\r\n,]+/', $pictures);
        }

        if (is_array($pictures)) {
            $this->merge(['pictures' => array_values(array_unique(array_filter(array_map('trim', $pictures))))]);
        }

        if (is_array($this->input('extra_attributes'))) {
            $this->merge(['extra_attributes' => array_values(array_filter(
                $this->input('extra_attributes'),
                fn ($row) => !empty($row['id']) && !empty($row['value'])
            ))]);
        }
    }

    public function rules(): array
    {
        return [
            'sku'                    => ['required', 'string', 'max:100'],
            'title'                  => ['required', 'string', 'max:60'],
            'meli_category_id'       => ['required', 'string', 'max:30'],
            'final_price'            => ['required', 'numeric', 'min:1'],
            'base_price'             => ['required', 'numeric', 'min:0'],
            'quantity'               => ['required', 'integer', 'min:1'],
            'weight'                 => ['required', 'numeric', 'min:0'],
            'brand'                  => ['required', 'string', 'max:100'],
            'pictures'               => ['required', 'array', 'min:1', 'max:10'],
            'pictures.*'             => ['url'],
            'description'            => ['nullable', 'string', 'max:50000'],
            'height'                 => ['nullable', 'numeric', 'min:0'],
            'width'                  => ['nullable', 'numeric', 'min:0'],
            'length'                 => ['nullable', 'numeric', 'min:0'],
            'model'                  => ['nullable', 'string', 'max:100'],
            'ean'                    => ['nullable', 'string', 'max:20'],
            'extra_attributes'       => ['nullable', 'array'],
            'extra_attributes.*.id'    => ['required', 'string', 'max:60'],
            'extra_attributes.*.value' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max'         => 'El título no puede superar 60 caracteres (límite de Mercado Libre).',
            'pictures.required' => 'Debe enviar al menos una imagen.',
            'pictures.*.url'    => 'Cada imagen debe ser una URL válida.',
        ];
    }
}
