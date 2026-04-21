<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    public function checkout()
{
    $result = $this->orderService->checkout(auth()->user());

    if ($result['status'] === 'empty_cart') {
        return response()->json(['message' => 'Cart is empty'], 400);
    }

    if ($result['status'] === 'out_of_stock') {
        return response()->json(['message' => 'Product out of stock'], 400);
    }

    return response()->json([
    'message' => 'Order created successfully',
    'order' => new OrderResource($result['order'])
]);


}


public function myOrders()
{
    $orders = $this->orderService->getUserOrders(auth()->user());

    return OrderResource::collection($orders);
}

public function show($id)
{
    $order = $this->orderService->getUserOrderById(auth()->user(), $id);

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    return new OrderResource($order);
}

public function adminOrders()
{
    $orders = $this->orderService->getAllOrders();

    return OrderResource::collection($orders);
}

public function adminShow($id)
{
    $order = $this->orderService->getOrderById($id);

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    return new OrderResource($order);
}

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
    ]);

    $order = $this->orderService->updateOrderStatus($id, $request->status);

    if (!$order) {
        return response()->json([
            'message' => 'Order not found'
        ], 404);
    }

    return new OrderResource($order);
}





}
