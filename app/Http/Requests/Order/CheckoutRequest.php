<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'customer_name'   => ['nullable', 'string', 'max:255'],
            'customer_phone'  => ['required', 'string', 'max:30'],
            'address'         => ['required', 'string', 'max:1000'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'promotion_code'  => ['nullable', 'string', 'max:50'],
        ];
    }
}
