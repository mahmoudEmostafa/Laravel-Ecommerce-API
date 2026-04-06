<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
        'name' => $this->name,
        'slug' => $this->slug,
        'price' => $this->price,
        'stock' => $this->stock,
        'is_active' => $this->is_active,
        'description' => $this->description,

        'category' => [
            'id' => $this->category->id ?? null,
            'name' => $this->category->name ?? null
        ],
        'image' => $this->image 
    ? asset('storage/' . $this->image) 
    : null,
    ];
    }
}
