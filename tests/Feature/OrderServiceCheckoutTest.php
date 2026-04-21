<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_returns_empty_cart_when_user_has_no_cart(): void
    {
        $user = User::factory()->create();

        $result = (new OrderService())->checkout($user);

        $this->assertSame('empty_cart', $result['status']);
    }

    public function test_checkout_returns_empty_cart_when_cart_has_no_items(): void
    {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id]);

        $result = (new OrderService())->checkout($user);

        $this->assertSame('empty_cart', $result['status']);
    }

    public function test_checkout_returns_out_of_stock_when_any_item_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(2, 100.00);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 100.00,
        ]);

        $result = (new OrderService())->checkout($user);

        $this->assertSame('out_of_stock', $result['status']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_checkout_creates_order_decrements_stock_and_clears_cart(): void
    {
        $user = User::factory()->create();

        $firstProduct = $this->createProduct(10, 50.00);
        $secondProduct = $this->createProduct(5, 30.00);

        $cart = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'price' => 50.00,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $secondProduct->id,
            'quantity' => 1,
            'price' => 30.00,
        ]);

        $result = (new OrderService())->checkout($user);

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('order', $result);

        /** @var Order $order */
        $order = $result['order'];
        $this->assertTrue($order->relationLoaded('items'));
        $this->assertSame(2, $order->items()->count());
        $this->assertSame(130.0, (float) $order->total);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'total' => 130.00,
            'shipping_address' => 'Not provided',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'quantity' => 2,
            'price' => 50.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $secondProduct->id,
            'quantity' => 1,
            'price' => 30.00,
        ]);

        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);

        $this->assertDatabaseHas('products', [
            'id' => $firstProduct->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $secondProduct->id,
            'stock' => 4,
        ]);
    }

    private function createProduct(int $stock, float $price): Product
    {
        $category = new Category();
        $category->name = 'Test Category';
        $category->slug = 'test-category-' . uniqid();
        $category->save();

        return Product::create([
            'name' => 'Test Product ' . uniqid(),
            'description' => 'Checkout test product',
            'price' => $price,
            'stock' => $stock,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
    }
}
