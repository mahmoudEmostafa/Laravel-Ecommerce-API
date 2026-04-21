<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_checkout(): void
    {
        $this->postJson('/api/checkout')->assertUnauthorized();
    }

    public function test_authenticated_user_gets_empty_cart_message_when_cart_is_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout')
            ->assertStatus(400)
            ->assertJson(['message' => 'Cart is empty']);
    }

    public function test_authenticated_user_gets_out_of_stock_message_when_item_exceeds_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(1, 100.00);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/checkout')
            ->assertStatus(400)
            ->assertJson(['message' => 'Product out of stock']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_authenticated_user_can_checkout_successfully(): void
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

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/checkout')
            ->assertOk()
            ->assertJson(['message' => 'Order created successfully']);

        $orderId = $response->json('order.data.id') ?? $response->json('order.id');
        $this->assertNotNull($orderId);

        /** @var Order $order */
        $order = Order::query()->findOrFail($orderId);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'total' => 130.00,
            'total_price' => 130.00,
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
        $category->slug = 'checkout-category-' . uniqid();
        $category->save();

        return Product::create([
            'name' => 'Checkout Product ' . uniqid(),
            'description' => 'Checkout API test product',
            'price' => $price,
            'stock' => $stock,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
    }
}
