<?php

namespace Tests\Feature\Checkout;

class CheckoutValidationTest extends CheckoutTestCase
{
    public function test_checkout_requires_idempotency_key_header(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkout', [
                'address' => 'Cairo',
                'payment_method' => 'cash_on_delivery',
            ]);

        $response->assertUnprocessable();
    }

    public function test_checkout_rejects_invalid_idempotency_key(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('Idempotency-Key', 'invalid-key')
            ->postJson('/api/checkout', [
                'address' => 'Cairo',
                'payment_method' => 'cash_on_delivery',
            ]);

        $response->assertUnprocessable();
    }

    public function test_checkout_requires_address(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('Idempotency-Key', (string) str()->uuid())
            ->postJson('/api/checkout', [
                'payment_method' => 'cash_on_delivery',
            ]);

        $response->assertUnprocessable();
    }

    public function test_checkout_rejects_invalid_payment_method(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('Idempotency-Key', (string) str()->uuid())
            ->postJson('/api/checkout', [
                'address' => 'Cairo',
                'payment_method' => 'bitcoin',
            ]);

        $response->assertUnprocessable();
    }
}
