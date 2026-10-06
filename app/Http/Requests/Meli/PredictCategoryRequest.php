<?php

namespace App\Http\Requests\Meli;

use Illuminate\Foundation\Http\FormRequest;

class PredictCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
        ];
    }
}
