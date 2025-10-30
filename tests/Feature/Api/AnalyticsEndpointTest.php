<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AnalyticsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestCannotAccessUserAnalytics(): void
    {
        $response = $this->getJson('/api/analytics/user');
        $response->assertStatus(401);
    }

    public function testAuthenticatedUserCanGetUserAnalytics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/analytics/user')
        ;

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'purchase_history',
                    'browsing_patterns',
                    'preferences',
                    'engagement_score',
                    'lifetime_value',
                    'recommendation_score',
                ],
            ])
        ;
    }

    public function testSiteAnalyticsIsPublicAndHasExpectedKeys(): void
    {
        $response = $this->getJson('/api/analytics/site');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'total_users',
                    'active_users',
                    'total_orders',
                    'total_revenue',
                    'average_order_value',
                    'conversion_rate',
                    'most_viewed_products',
                    'top_selling_products',
                ],
            ])
        ;
    }

    public function testTrackBehaviorRequiresAuth(): void
    {
        $response = $this->postJson('/api/analytics/track', [
            'action' => 'page_view',
            'data' => ['page' => 'home'],
        ]);

        $response->assertStatus(401);
    }

    public function testTrackBehaviorSuccessForAuthenticatedUser(): void
    {
        $user = User::factory()->create();

        DB::shouldReceive('table')
            ->with('user_behaviors')
            ->andReturnSelf()
        ;
        DB::shouldReceive('insert')
            ->once()
            ->with(\Mockery::on(static function ($arg) use ($user) {
                return $arg['user_id'] === $user->id
                    && 'page_view' === $arg['action']
                    && $arg['data'] === json_encode(['page' => 'home'])
                    && isset($arg['ip_address'], $arg['user_agent'], $arg['created_at'], $arg['updated_at']);
            }))
        ;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/analytics/track', [
                'action' => 'page_view',
                'data' => ['page' => 'home'],
            ])
        ;

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تسجيل السلوك بنجاح')
        ;

        $this->addToAssertionCount(1);
    }
}
