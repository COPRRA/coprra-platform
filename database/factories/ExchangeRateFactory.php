<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency' => 'USD',
            'to_currency' => $this->faker->randomElement(['EUR', 'GBP', 'EGP', 'SAR']),
            'rate' => $this->faker->randomFloat(4, 0.5, 2.0),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }
}
