<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add (Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1'
    ]);

    $result = $this->cartService->addItem(
        $request->product_id,
        $request->user(),
        $request->quantity
    );

    return response()->json($result);
}

public function index(Request $request)
{
    $cart = $this->cartService->getCart($request->user());

    if (!$cart) {
        return response()->json([
            'message' => 'Cart is empty'
        ]);
    }

    return new CartResource($cart);
}


public function update(Request $request, $id)
{
    $request->validate([
        'quantity' => 'required|integer|min:1'
    ]);

    $result = $this->cartService->updateItem(
        $request->user(),
        $id,
        $request->quantity
    );

    return response()->json($result);
}

public function remove(Request $request, $id)
{
    $result = $this->cartService->removeItem($request->user(), $id);

    return response()->json($result);
}

}
