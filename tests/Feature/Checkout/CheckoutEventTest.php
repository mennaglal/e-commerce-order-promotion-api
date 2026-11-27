<?php

namespace Tests\Feature\Checkout;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Event;

class CheckoutEventTest extends CheckoutTestCase
{
    public function test_order_placed_event_is_dispatched_after_successful_checkout(): void
    {
        Event::fake([OrderPlaced::class]);

        $product = $this->createProduct();
        $this->addToCart($product);

        $this->checkout((string) str()->uuid());

        Event::assertDispatched(OrderPlaced::class, 1);
    }

    public function test_order_placed_event_is_not_dispatched_when_checkout_fails(): void
    {
        Event::fake([OrderPlaced::class]);

        $product = $this->createProduct(stock: 0);
        $this->addToCart($product);

        $this->checkout((string) str()->uuid());

        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_order_placed_event_is_not_dispatched_again_for_same_idempotency_key(): void
    {
        Event::fake([OrderPlaced::class]);

        $product = $this->createProduct();
        $this->addToCart($product);

        $key = (string) str()->uuid();

        $this->checkout($key);
        $this->checkout($key);

        Event::assertDispatched(OrderPlaced::class, 1);
    }
}
