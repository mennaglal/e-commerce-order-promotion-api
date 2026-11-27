<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;


class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('items');

        Mail::to($order->customer_email)
            ->send(new OrderConfirmationMail($order));
    }
}
