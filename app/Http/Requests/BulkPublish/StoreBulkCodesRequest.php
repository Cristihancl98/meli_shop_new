<?php

namespace App\Http\Requests\BulkPublish;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('codes'))) {
            $this->merge(['skus' => array_values(array_filter(array_map('trim', preg_split('/[\r\n,;\s]+/', $this->input('codes')))))]);
        }
    }

    public function rules(): array
    {
        return [
            'skus'   => ['required', 'array', 'min:1', 'max:500'],
            'skus.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'skus.required' => 'Debe enviar al menos un código.',
            'skus.max'      => 'Máximo 500 códigos por carga.',
        ];
    }
}
