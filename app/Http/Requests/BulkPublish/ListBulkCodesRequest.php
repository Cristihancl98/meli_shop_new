<?php

namespace App\Http\Requests\BulkPublish;

use Illuminate\Foundation\Http\FormRequest;

class ListBulkCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
