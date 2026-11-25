<?php

namespace App\Http\Requests\BulkPublish;

use App\Services\PublishBatchService;
use Illuminate\Foundation\Http\FormRequest;

class StorePublishBatchRequest extends FormRequest
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
            'skus'   => ['required', 'array', 'min:1', 'max:' . PublishBatchService::MAX_SKUS],
            'skus.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'skus.required' => 'Pega al menos un SKU.',
            'skus.max'      => 'Máximo ' . PublishBatchService::MAX_SKUS . ' SKU por lote.',
            'skus.*.max'    => 'Cada SKU puede tener máximo 100 caracteres.',
        ];
    }
}
