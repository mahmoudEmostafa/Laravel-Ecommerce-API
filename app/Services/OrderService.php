<?php
namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService {








public function checkout($user)
{
    $cart = Cart::with('items.product')
        ->where('user_id', $user->id)
        ->first();

    if (!$cart || $cart->items->isEmpty()) {
        return ['status' => 'empty_cart'];
    }

    return DB::transaction(function () use ($cart, $user) {

        $total = 0;

        foreach ($cart->items as $item) {

            if ($item->product->stock < $item->quantity) {
                return ['status' => 'out_of_stock'];
            }

            $total += $item->price * $item->quantity;
        }

        $order = Order::create([
            'user_id' => $user->id,
            'total' => $total,
            'total_price' => $total,
            'shipping_address' => 'Not provided',
            'status' => 'pending'
        ]);

        foreach ($cart->items as $item) {

            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price
            ]);

            $item->product->decrement('stock', $item->quantity);
        }

        // حذف عناصر السلة
        $cart->items()->delete();
        $order->load('items.product');
        return [
            'status' => 'success',
            'order' => $order
        ];
    });
}


public function getUserOrders($user)
{
    return Order::with('items.product')
        ->where('user_id', $user->id)
        ->latest()
        ->paginate(10);
}

public function getUserOrderById($user, $orderId)
{
    $order = Order::with('items.product')
        ->where('user_id', $user->id)
        ->where('id', $orderId)
        ->first();

    if (!$order) {
        return null;
    }

    return $order;
}

//admin
public function getAllOrders() {
    return Order::with('items.product','user')->latest()->paginate(10);
}
public function getOrderById($orderId) {
    return Order::with('items.product','user')->find($orderId);
}
public function updateOrderStatus($orderId, $status) {
    $order = Order::find($orderId);
    if (!$order) {
        return null;
    }
    $order->status = $status;
    $order->save();
    return $order;
}


}