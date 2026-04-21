<?php

namespace App\Http\Controllers\Payment;


use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function checkout(Request $request, $orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);

        // 🔒 تأكد أن الطلب للمستخدم
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $url = $this->paymentService->createCheckoutSession($order);

        return response()->json([
            'checkout_url' => $url
        ]);
    }
}
