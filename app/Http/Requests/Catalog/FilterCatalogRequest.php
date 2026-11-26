<?php

namespace App\Http\Requests\Catalog;

use App\DTOs\CatalogSearchDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class FilterCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode'             => ['nullable', Rule::in(CatalogSearchDTO::MODES)],
            'q'                => ['nullable', 'string', 'max:100', Rule::requiredIf(fn () => in_array($this->input('mode'), [CatalogSearchDTO::MODE_TITLE, CatalogSearchDTO::MODE_CATEGORY], true)), 'min:2'],
            'min_price'        => ['nullable', 'numeric', 'min:0'],
            'max_price'        => ['nullable', 'numeric', 'min:0'],
            'min_weight'       => ['nullable', 'numeric', 'min:0'],
            'max_weight'       => ['nullable', 'numeric', 'min:0'],
            'only_publishable' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required'     => 'Escribe un término para buscar por título o categoría.',
            'q.min'          => 'El término debe tener al menos 2 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (['price' => 'precio', 'weight' => 'peso'] as $field => $label) {
                $min = $this->input("min_{$field}");
                $max = $this->input("max_{$field}");

                if (is_numeric($min) && is_numeric($max) && (float) $max < (float) $min) {
                    $validator->errors()->add("max_{$field}", "El {$label} máximo debe ser mayor o igual al mínimo.");
                }
            }
        });
    }

    public function searched(): bool
    {
        return $this->filled('mode');
    }

    public function criteria(): CatalogSearchDTO
    {
        return CatalogSearchDTO::fromArray($this->validated());
    }
}
