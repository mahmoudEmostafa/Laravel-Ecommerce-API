<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Order;

class PaymentService
{
    public function createCheckoutSession(Order $order)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                    'unit_amount' => $item->price * 100,
                ],
                'quantity' => $item->quantity,
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',

            'success_url' => 'http://localhost:3000/success',
            'cancel_url' => 'http://localhost:3000/cancel',

            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        // حفظ session id
        $order->update([
            'stripe_session_id' => $session->id
        ]);

        return $session->url;
    }
}