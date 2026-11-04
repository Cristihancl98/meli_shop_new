<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeliCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meli_client_id'     => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
            'meli_client_secret' => ['nullable', 'string', 'max:100'],
            'meli_redirect_uri'  => ['nullable', 'url:https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'meli_client_id.regex'  => 'El Client ID (App ID) de Mercado Libre es numérico.',
            'meli_redirect_uri.url' => 'La Redirect URI debe ser una URL HTTPS, igual a la registrada en tu aplicación de Mercado Libre.',
        ];
    }
}
