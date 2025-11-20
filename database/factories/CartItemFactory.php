<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 5, 200),
            'session_id' => $this->faker->uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a cart item for a specific user.
     */
    public function forUser(int $userId): static
    {
        return $this->state([
            'user_id' => $userId,
        ]);
    }

    /**
     * Create a cart item for a specific product.
     */
    public function forProduct(int $productId): static
    {
        return $this->state([
            'product_id' => $productId,
        ]);
    }

    /**
     * Create a cart item with a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state([
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create a cart item for a guest user (session-based).
     */
    public function forGuest(): static
    {
        return $this->state([
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
        ]);
    }
}
