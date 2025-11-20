<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'status' => 'success',
            'response_code' => 200,
            'response_body' => json_encode(['status' => 'success']),
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
