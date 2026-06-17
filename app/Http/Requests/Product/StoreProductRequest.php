<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Tymon\JWTAuth\Facades\JWTAuth;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTAuth::parseToken()->authenticate()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'price'           => ['required', 'numeric', 'min:0'],
            'stock'           => ['required', 'integer', 'min:0'],
            'condition'       => ['required', 'in:new,used'],
            'category_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'listing_type_id' => ['nullable', 'in:free,bronze,gold_special,gold_pro'],
            'status'          => ['nullable', 'in:active,paused'],
            'image'           => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'     => 'El título es obligatorio.',
            'price.required'     => 'El precio es obligatorio.',
            'price.min'          => 'El precio debe ser mayor a 0.',
            'stock.required'     => 'El stock es obligatorio.',
            'condition.in'       => 'La condición debe ser nuevo o usado.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'image.max'          => 'La imagen no puede superar 5MB.',
        ];
    }
}
