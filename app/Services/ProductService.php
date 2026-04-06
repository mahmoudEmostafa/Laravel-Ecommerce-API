<?php

namespace App\Services;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class ProductService {
public function store(array $data)
{
    return Product::create($data);
}


public function update($id, array $data)
{
    $product = Product::findOrFail($id);

    $product->update($data);

    return $product;
}

public function delete($id)
{
    $product = Product::findOrFail($id);

    $product->delete(); // Soft delete

    return true;
}
public function index($request)
{
    $query = Product::query();

    // Search
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    // Filter
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // Pagination
    return $query->paginate(10);
}


public function show ($slug) {
    $product= Product::with('category')->where('slug',$slug)->firstOrFail();
    return $product;
}



public function uploadImage($productId, $image)
{
    $product = Product::find($productId);

    if (!$product) {
        return null;
    }

    // حذف الصورة القديمة (احترافي)
    if ($product->image) {
        Storage::disk('public')->delete($product->image);
    }

    // رفع صورة جديدة
    $path = $image->store('products', 'public');

    $product->update([
        'image' => $path
    ]);

    return $product;
}




}