<?php

namespace App\Http\Requests\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'integer',
                'exists:products,id',
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
