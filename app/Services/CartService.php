<?php
namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartService {

  public function addItem ($productId,$user,$quantity) {
    $product=Product::findOrFail($productId);
    if (!$product || !$product->is_active) {
         return ['status' => 'not_found'];

    }
    if ($product->stock <$quantity) {
         return ['status' => 'out_of_stock'];   

    }
    $cart=Cart::firstOrCreate([
        "user_id"=>$user->id
    ]);

    $item= $cart->items()->where("product_id",$productId)->first();
     if ($item) {
        $item->update([
            'quantity'=>$item->quantity+$quantity
        ]);
     } else {
        $cart->items()->create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product->price
        ]);
     }
      return ['status' => 'success'];
  
  
    }

 public function getCart ($user) {
    return Cart::with('items.product')->where('user_id',$user->id)->first();
 }
public function updateItem($user,$itemId,$quantity) {
    $item = CartItem::whereKey($itemId)
        ->whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->first();

    if (!$item) {
        $exists = CartItem::whereKey($itemId)->exists();

        return ['status' => $exists ? 'forbidden' : 'not_found'];

    }
    $item->update([
        "quantity"=>$quantity
    ]);

     return ['status' => 'success'];
}

public function removeItem($user, $itemId)
{
    $item = CartItem::whereKey($itemId)
        ->whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->first();

    if (!$item) {
        $exists = CartItem::whereKey($itemId)->exists();

        return ['status' => $exists ? 'forbidden' : 'not_found'];
    }

    $item->delete();

    return ['status' => 'success'];
}


}