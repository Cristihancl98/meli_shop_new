<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    private const RANGE_KEYS = [
        'profit_ranges'       => 'percentage',
        'weight_ranges'       => 'price',
        'national_tax_ranges' => 'price',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (array_keys(self::RANGE_KEYS) as $key) {
            if (is_array($this->input($key))) {
                $rows = array_filter(
                    $this->input($key),
                    fn ($row) => is_array($row) && array_filter($row, fn ($v) => $v !== null && $v !== '') !== []
                );
                $this->merge([$key => array_values($rows)]);
            }
        }

        if ($this->has('sale_message_enabled')) {
            $this->merge(['sale_message_enabled' => $this->boolean('sale_message_enabled')]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'shipping_price'       => ['sometimes', 'numeric', 'min:0'],
            'dollar_price'         => ['sometimes', 'numeric', 'min:0'],
            'logistics_price'      => ['sometimes', 'numeric', 'min:0'],
            'meli_commission'      => ['sometimes', 'numeric', 'between:0,100'],
            'iva'                  => ['sometimes', 'numeric', 'between:0,100'],
            'amazon_commission'    => ['sometimes', 'numeric', 'between:0,100'],
            'manufacturing_days'   => ['sometimes', 'integer', 'min:0', 'max:90'],
            'warranty_days'        => ['sometimes', 'integer', 'min:0', 'max:730'],
            'default_quantity'     => ['sometimes', 'integer', 'min:1'],
            'listing_type'         => ['sometimes', 'in:free,gold_special,gold_pro'],
            'sale_message'         => ['sometimes', 'nullable', 'string', 'max:350'],
            'sale_message_enabled' => ['sometimes', 'boolean'],
            'description_template' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'scraping_url'         => ['sometimes', 'nullable', 'url'],
        ];

        foreach (self::RANGE_KEYS as $key => $amountField) {
            $rules[$key]                   = ['sometimes', 'array'];
            $rules["{$key}.*.from"]        = ['required', 'numeric', 'min:0'];
            $rules["{$key}.*.to"]          = ['required', 'numeric', "gte:{$key}.*.from"];
            $rules["{$key}.*.{$amountField}"] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (array_intersect(array_keys($this->all()), array_keys($this->rules())) === []) {
                    $validator->errors()->add('settings', 'Debe enviar al menos una configuración.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            '*.*.to.gte'      => 'El valor final del rango debe ser mayor o igual al inicial.',
            'listing_type.in' => 'Tipo de publicación inválido.',
        ];
    }
}
