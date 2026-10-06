<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateListingRequest extends FormRequest
{
    private const FIELDS = ['title', 'price', 'base_price', 'quantity', 'pictures', 'description'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('pictures'))) {
            $pictures = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $this->input('pictures')))));
            $this->merge(['pictures' => $pictures ?: null]);
        }

        foreach (self::FIELDS as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:60'],
            'price'       => ['nullable', 'numeric', 'min:1'],
            'base_price'  => ['nullable', 'numeric', 'min:0'],
            'quantity'    => ['nullable', 'integer', 'min:0'],
            'pictures'    => ['nullable', 'array', 'min:1', 'max:10'],
            'pictures.*'  => ['url'],
            'description' => ['nullable', 'string', 'max:50000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (array_filter($this->only(self::FIELDS), fn ($value) => $value !== null) === []) {
                    $validator->errors()->add('listing', 'Debe enviar al menos un campo para actualizar.');
                }
            },
        ];
    }
}
