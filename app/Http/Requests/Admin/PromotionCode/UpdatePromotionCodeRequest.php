<?php

namespace App\Http\Requests\Admin\PromotionCode;

use App\Models\PromotionCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromotionCodeRequest extends FormRequest
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
        $promotionCode = $this->route('promotion_code');
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('promotion_codes', 'code')
                    ->ignore($promotionCode?->id),
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
                Rule::when(
                    $this->input('type') === PromotionCode::TYPE_PERCENTAGE,
                    ['max:100']
                ),
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
