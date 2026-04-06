<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_cart_endpoints(): void
    {
        $this->postJson('/api/cart/add', [
            'product_id' => 1,
            'quantity' => 1,
        ])->assertUnauthorized();

        $this->getJson('/api/cart')->assertUnauthorized();

        $this->putJson('/api/cart/item/1', [
            'quantity' => 2,
        ])->assertUnauthorized();

        $this->deleteJson('/api/cart/item/1')->assertUnauthorized();
    }

    public function test_authenticated_user_can_add_update_and_remove_own_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk()->assertJson(['status' => 'success']);

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->firstOrFail();

        $this->putJson('/api/cart/item/' . $item->id, [
            'quantity' => 5,
        ])->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);

        $this->deleteJson('/api/cart/item/' . $item->id)
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_authenticated_user_gets_empty_message_when_cart_does_not_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJson(['message' => 'Cart is empty']);
    }

    public function test_authenticated_user_can_view_cart_resource(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $user->id]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'items',
                    'total',
                ],
            ]);
    }

    public function test_user_gets_not_found_when_cart_item_does_not_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/cart/item/999999', [
            'quantity' => 3,
        ])->assertOk()->assertJson(['status' => 'not_found']);

        $this->deleteJson('/api/cart/item/999999')
            ->assertOk()
            ->assertJson(['status' => 'not_found']);
    }

    public function test_user_cannot_update_or_delete_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct();
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        Sanctum::actingAs($otherUser);

        $this->putJson('/api/cart/item/' . $item->id, [
            'quantity' => 3,
        ])->assertOk()->assertJson(['status' => 'forbidden']);

        $this->deleteJson('/api/cart/item/' . $item->id)
            ->assertOk()
            ->assertJson(['status' => 'forbidden']);

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 1,
        ]);
    }

    private function createProduct(): Product
    {
        $category = new Category();
        $category->name = 'Test Category';
        $category->slug = 'test-category-' . uniqid();
        $category->save();

        return Product::create([
            'name' => 'Test Product ' . uniqid(),
            'description' => 'Test product description',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
    }
}
