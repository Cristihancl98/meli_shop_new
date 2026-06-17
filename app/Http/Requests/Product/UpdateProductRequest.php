<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTAuth::parseToken()->authenticate()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'  => ['sometimes', 'string', 'max:255'],
            'price'  => ['sometimes', 'numeric', 'min:0'],
            'stock'  => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,paused,closed'],
            'image'  => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min'   => 'El precio debe ser mayor a 0.',
            'stock.min'   => 'El stock no puede ser negativo.',
            'status.in'   => 'Estado inválido.',
            'image.max'   => 'La imagen no puede superar 5MB.',
        ];
    }
}
