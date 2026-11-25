<?php

namespace App\Http\Requests\BulkPublish;

use Illuminate\Foundation\Http\FormRequest;

class BatchItemsActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'   => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Marca al menos un artículo.',
        ];
    }
}
