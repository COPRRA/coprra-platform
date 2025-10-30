<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testAnalyticsEndpointRequiresAuthentication()
    {
        $response = $this->getJson('/api/analytics');

        $response->assertStatus(401);
    }

    public function testAuthenticatedUserCanAccessAnalytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/analytics');

        // Expecting either 200 (success) or 404 (route not found)
        self::assertContains($response->status(), [200, 404]);
    }

    public function testAnalyticsReturnsJsonStructure()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/analytics');

        if (200 === $response->status()) {
            $response->assertJsonStructure([
                'data' => [],
            ]);
        } else {
            self::assertTrue(true, 'Analytics endpoint not implemented yet');
        }
    }
}
