<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
        'id' => $this->id,
        'quantity' => $this->quantity,
        'price' => $this->price,

        'product' => [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->product->price,
            'image' => $this->product->image 
                ? asset('storage/' . $this->product->image)
                : null,
        ],

        'subtotal' => $this->price * $this->quantity
    ];
    }
}
