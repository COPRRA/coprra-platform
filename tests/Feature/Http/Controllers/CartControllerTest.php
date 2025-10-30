<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanDisplayCartIndex(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/cart');

        $response->assertStatus(200)
            ->assertViewIs('cart.index')
            ->assertViewHas('cartItems')
        ;
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanAddProductToCart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 29.99,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect('/cart')
            ->assertSessionHas('success')
        ;

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itValidatesAddToCartRequest(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => '',
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors(['product_id', 'quantity']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturns404WhenAddingNonexistentProductToCart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanUpdateCartItemQuantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->patch("/cart/{$cartItem->id}", [
            'quantity' => 3,
        ]);

        $response->assertRedirect('/cart')
            ->assertSessionHas('success')
        ;

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanRemoveProductFromCart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)->delete("/cart/{$cartItem->id}");

        $response->assertRedirect('/cart')
            ->assertSessionHas('success')
        ;

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanClearEntireCart(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product1->id,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product2->id,
        ]);

        $response = $this->actingAs($user)->delete('/cart/clear');

        $response->assertRedirect('/cart')
            ->assertSessionHas('success')
        ;

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itRequiresAuthenticationForAllCartRoutes(): void
    {
        $product = Product::factory()->create();

        // Test cart index
        $response = $this->get('/cart');
        $response->assertRedirect('/login');

        // Test add to cart
        $response = $this->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $response->assertRedirect('/login');

        // Test update cart
        $response = $this->patch('/cart/1', ['quantity' => 2]);
        $response->assertRedirect('/login');

        // Test remove from cart
        $response = $this->delete('/cart/1');
        $response->assertRedirect('/login');

        // Test clear cart
        $response = $this->delete('/cart/clear');
        $response->assertRedirect('/login');
    }
}
