<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class PriceAlertControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'price' => 100.00,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function itCanCreatePriceAlert(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/price-alerts', [
                'product_id' => $this->product->id,
                'target_price' => 80.00,
                'email' => $this->user->email,
            ])
        ;

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'product_id',
                'user_id',
                'target_price',
                'email',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('price_alerts', [
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'target_price' => 80.00,
            'email' => $this->user->email,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function itCanListUserPriceAlerts(): void
    {
        $priceAlerts = PriceAlert::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/price-alerts')
        ;

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'user_id',
                    'target_price',
                    'email',
                    'is_active',
                    'product' => [
                        'id',
                        'name',
                        'price',
                    ],
                ],
            ],
        ]);

        self::assertCount(3, $response->json('data'));
    }

    #[Test]
    public function itCanUpdatePriceAlert(): void
    {
        $priceAlert = PriceAlert::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'target_price' => 90.00,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/price-alerts/{$priceAlert->id}", [
                'target_price' => 75.00,
                'email' => $this->user->email,
            ])
        ;

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'target_price' => 75.00,
        ]);

        $this->assertDatabaseHas('price_alerts', [
            'id' => $priceAlert->id,
            'target_price' => 75.00,
        ]);
    }

    #[Test]
    public function itCanDeletePriceAlert(): void
    {
        $priceAlert = PriceAlert::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("/price-alerts/{$priceAlert->id}")
        ;

        $response->assertStatus(204);

        $this->assertDatabaseMissing('price_alerts', [
            'id' => $priceAlert->id,
        ]);
    }

    #[Test]
    public function itValidatesCreatePriceAlertRequest(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/price-alerts', [])
        ;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'product_id',
            'target_price',
            'email',
        ]);
    }

    #[Test]
    public function itValidatesTargetPriceIsPositive(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/price-alerts', [
                'product_id' => $this->product->id,
                'target_price' => -10.00,
                'email' => $this->user->email,
            ])
        ;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_price']);
    }

    #[Test]
    public function itValidatesEmailFormat(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/price-alerts', [
                'product_id' => $this->product->id,
                'target_price' => 80.00,
                'email' => 'invalid-email',
            ])
        ;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function itValidatesProductExists(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/price-alerts', [
                'product_id' => 99999,
                'target_price' => 80.00,
                'email' => $this->user->email,
            ])
        ;

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id']);
    }

    #[Test]
    public function itRequiresAuthenticationForCreatingPriceAlert(): void
    {
        $response = $this->post('/price-alerts', [
            'product_id' => $this->product->id,
            'target_price' => 80.00,
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function itRequiresAuthenticationForListingPriceAlerts(): void
    {
        $response = $this->get('/price-alerts');

        $response->assertStatus(401);
    }

    #[Test]
    public function itPreventsAccessToOtherUsersPriceAlerts(): void
    {
        $otherUser = User::factory()->create();
        $priceAlert = PriceAlert::factory()->create([
            'user_id' => $otherUser->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/price-alerts/{$priceAlert->id}", [
                'target_price' => 75.00,
            ])
        ;

        $response->assertStatus(403);
    }

    #[Test]
    public function itCanTogglePriceAlertStatus(): void
    {
        $priceAlert = PriceAlert::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch("/price-alerts/{$priceAlert->id}/toggle")
        ;

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('price_alerts', [
            'id' => $priceAlert->id,
            'is_active' => false,
        ]);
    }
}
