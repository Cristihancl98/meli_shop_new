<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class ConnectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'connection_code' => ['required', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'connection_code.required' => 'Ingresa el código de conexión de tu tienda.',
        ];
    }
}
