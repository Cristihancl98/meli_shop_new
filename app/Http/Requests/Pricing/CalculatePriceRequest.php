<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_price' => ['required', 'numeric', 'min:0'],
            'weight'     => ['required', 'numeric', 'min:0'],
        ];
    }
}
