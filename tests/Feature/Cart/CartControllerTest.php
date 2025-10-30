<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cart before each test
        app('cart')->clear();
    }

    public function testUserCanViewCart(): void
    {
        $response = $this->get('/cart');

        $response->assertStatus(200);
        $response->assertViewIs('cart.index');
        $response->assertViewHas(['cartItems', 'total']);
    }

    public function testUserCanAddProductToCart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
        ]);

        $response = $this->post("/cart/add/{$product->id}", [
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $cartItems = app('cart')->getContent();
        self::assertCount(1, $cartItems);
        self::assertSame(2, $cartItems->first()->quantity);
    }

    public function testUserCanUpdateCartQuantity(): void
    {
        $product = Product::factory()->create(['price' => 50.00]);

        // Add product to cart
        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $updatedItem = app('cart')->get($cartItem->id);
        self::assertSame(5, $updatedItem->quantity);
    }

    public function testUserCannotUpdateCartWithInvalidQuantity(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 0, // Invalid
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function testUserCanRemoveItemFromCart(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->delete("/cart/remove/{$cartItem->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        self::assertCount(0, app('cart')->getContent());
    }

    public function testUserCanClearEntireCart(): void
    {
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            app('cart')->add([
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'attributes' => [],
            ]);
        }

        self::assertCount(3, app('cart')->getContent());

        $response = $this->post('/cart/clear');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        self::assertCount(0, app('cart')->getContent());
    }

    public function testCartCalculatesTotalCorrectly(): void
    {
        $product1 = Product::factory()->create(['price' => 10.00]);
        $product2 = Product::factory()->create(['price' => 20.00]);

        app('cart')->add([
            'id' => $product1->id,
            'name' => $product1->name,
            'price' => $product1->price,
            'quantity' => 2, // 2 * 10 = 20
            'attributes' => [],
        ]);

        app('cart')->add([
            'id' => $product2->id,
            'name' => $product2->name,
            'price' => $product2->price,
            'quantity' => 3, // 3 * 20 = 60
            'attributes' => [],
        ]);

        $total = app('cart')->getTotal();
        self::assertSame(80.00, $total); // 20 + 60 = 80
    }

    public function testCartPersistsProductAttributes(): void
    {
        $product = Product::factory()->create([
            'slug' => 'test-product',
            'image' => 'test-image.jpg',
        ]);

        $response = $this->post("/cart/add/{$product->id}", [
            'quantity' => 1,
            'attributes' => [
                'color' => 'red',
                'size' => 'large',
            ],
        ]);

        $cartItem = app('cart')->getContent()->first();

        self::assertSame('test-product', $cartItem->attributes['slug']);
        self::assertSame('test-image.jpg', $cartItem->attributes['image']);
        self::assertSame('red', $cartItem->attributes['color']);
        self::assertSame('large', $cartItem->attributes['size']);
    }

    public function testUpdateCartRequestValidatesInput(): void
    {
        $response = $this->post('/cart/update', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['id', 'quantity']);
    }

    public function testQuantityCannotExceedMaximum(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 1000, // Exceeds max of 999
        ]);

        $response->assertSessionHasErrors('quantity');
    }
}
