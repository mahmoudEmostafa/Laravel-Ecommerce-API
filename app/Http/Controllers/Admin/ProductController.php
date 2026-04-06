<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController
{

protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    public function store(StoreProductRequest $request)
{
    $product = $this->productService->store($request->validated());

    return response()->json([
        'message' => 'Product created successfully',
        'data' => new ProductResource($product)
    ], 201);
}



public function update(UpdateProductRequest $request, $id)
{
    $product = $this->productService->update($id, $request->validated());

    return response()->json([
        'message' => 'Product updated successfully',
        'data' => new ProductResource($product)
    ]);
}


public function destroy($id)
{
    $this->productService->delete($id);

    return response()->json([
        'message' => 'Product deleted successfully'
    ]);
}
public function index(ProductIndexRequest $request)
{
    $products = $this->productService->index($request);

    return ProductResource::collection($products);
}


public function show($slug) {
    $product=$this->productService->show($slug);
    
    if (!$product) {
        return response()->json([
            'message' => 'Product not found'
        ], 404);
    }

    return response()->json([
        'product' => new ProductResource($product)
    ]);

}

public function uploadImage(Request $request, $productId)
{
    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
    ]);

    $result = $this->productService->uploadImage($productId, $request->file('image'));

    if ($result === null) {
        return response()->json([
            'message' => 'Product not found'
        ], 404);
    }

    return response()->json([
        'message' => 'Image uploaded successfully',
        'product' => new ProductResource($result)
    ]);
}
}
