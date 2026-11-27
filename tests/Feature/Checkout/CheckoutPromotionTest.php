<?php

namespace Tests\Feature\Checkout;

use App\Models\PromotionCode;

class CheckoutPromotionTest extends CheckoutTestCase
{
    public function test_percentage_promotion_is_applied_correctly(): void
    {
        $product = $this->createProduct(price: '200.00');

        $this->addToCart($product);

        $promotion = PromotionCode::factory()->create([
            'code' => 'SAVE10',
            'type' => PromotionCode::TYPE_PERCENTAGE,
            'value' => '10.00',
            'max_discount_amount' => null,
            'min_cart_amount' => null,
            'is_active' => true,
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertTrue($result['success']);

        $this->assertSame('200.00', $result['order']->subtotal);
        $this->assertSame('20.00', $result['order']->discount_amount);
        $this->assertSame('180.00', $result['order']->total);

        $this->assertDatabaseHas('promotion_usages', [
            'promotion_id' => $promotion->id,
            'user_id' => $this->user->id,
            'order_id' => $result['order']->id,
            'cancelled_at' => null,
        ]);
    }

    public function test_fixed_promotion_is_applied_correctly(): void
    {
        $product = $this->createProduct(price: '200.00');

        $this->addToCart($product);

        $promotion = PromotionCode::factory()->create([
            'code' => 'FIXED25',
            'type' => PromotionCode::TYPE_FIXED,
            'value' => '25.00',
            'is_active' => true,
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertTrue($result['success']);

        $this->assertSame('200.00', $result['order']->subtotal);
        $this->assertSame('25.00', $result['order']->discount_amount);
        $this->assertSame('175.00', $result['order']->total);
    }

    public function test_inactive_promotion_is_rejected(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product);

        $promotion = PromotionCode::factory()->inactive()->create([
            'code' => 'INACTIVE',
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertFalse($result['success']);
        $this->assertSame('coupon_invalid', $result['errors']['code']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_expired_promotion_is_rejected(): void
    {
        $product = $this->createProduct();
        $this->addToCart($product);

        $promotion = PromotionCode::factory()->expired()->create([
            'code' => 'EXPIRED',
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertFalse($result['success']);
        $this->assertSame('coupon_expired', $result['errors']['code']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_promotion_is_rejected_when_minimum_cart_amount_is_not_reached(): void
    {
        $product = $this->createProduct(price: '50.00');
        $this->addToCart($product);

        $promotion = PromotionCode::factory()->create([
            'code' => 'MIN100',
            'min_cart_amount' => '100.00',
            'is_active' => true,
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertFalse($result['success']);
        $this->assertSame('coupon_min_amount', $result['errors']['code']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_percentage_discount_respects_maximum_discount_amount(): void
    {
        $product = $this->createProduct(price: '500.00');
        $this->addToCart($product);

        $promotion = PromotionCode::factory()->create([
            'code' => 'MAX50',
            'type' => PromotionCode::TYPE_PERCENTAGE,
            'value' => '20.00',
            'max_discount_amount' => '50.00',
            'is_active' => true,
        ]);

        $result = $this->checkout(
            (string) str()->uuid(),
            ['promotion_code' => $promotion->code]
        );

        $this->assertTrue($result['success']);
        $this->assertSame('50.00', $result['order']->discount_amount);
        $this->assertSame('450.00', $result['order']->total);
    }
}
