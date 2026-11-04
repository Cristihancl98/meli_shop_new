<?php

namespace App\Http\Requests\Meli;

use Illuminate\Foundation\Http\FormRequest;

class CategoryLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'string', 'regex:/^[A-Z]{3}\d+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.regex' => 'El ID de categoría debe tener el formato MCO1234.',
        ];
    }
}
