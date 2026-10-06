<?php

namespace App\Http\Requests\PostSale;

use Illuminate\Foundation\Http\FormRequest;

class ReplyPostSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:350'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.max' => 'Mercado Libre permite máximo 350 caracteres por mensaje posventa.',
        ];
    }
}
