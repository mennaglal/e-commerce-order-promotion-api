<?php

namespace App\Http\Requests\Admin\PromotionCode;

use App\Models\PromotionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'unique:promotion_codes,code',
            ],

            'type' => [
                'required',
                Rule::in([
                    PromotionCode::TYPE_PERCENTAGE,
                    PromotionCode::TYPE_FIXED,
                ]),
            ],

            'value' => [
                'required',
                'numeric',
                'gt:0',
                'max:' . ($this->input('type') === PromotionCode::TYPE_PERCENTAGE ? 100 : '999999999999'),
            ],

            'min_cart_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0'],

            'starts_at' => ['nullable', 'date'],

            'ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],

            'is_active' => ['required', 'boolean'],
        ];
    }
}
