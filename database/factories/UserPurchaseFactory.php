<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\UserPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPurchase>
 */
class UserPurchaseFactory extends Factory
{
    protected $model = UserPurchase::class;

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
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'purchased_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'order_id' => null, // Can be set explicitly when needed
        ];
    }

    /**
     * Create a purchase for a specific user and product.
     */
    public function forUserAndProduct(int $userId, int $productId): static
    {
        return $this->state([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Create a recent purchase.
     */
    public function recent(): static
    {
        return $this->state([
            'purchased_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Create an old purchase.
     */
    public function old(): static
    {
        return $this->state([
            'purchased_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }
}
