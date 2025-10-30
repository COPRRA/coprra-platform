<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
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
final class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGenerateProductPerformanceReport(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        // Create some test data
        Order::factory()->count(5)->create([
            'product_id' => $product->id,
            'created_at' => now()->subDays(7),
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports/product-performance');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'product_id',
                    'product_name',
                    'total_sales',
                    'total_revenue',
                    'average_rating',
                ],
            ],
            'period',
            'generated_at',
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGenerateUserActivityReport(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create test users with activity
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            Order::factory()->count(2)->create(['user_id' => $user->id]);
        }

        $response = $this->actingAs($admin)->get('/admin/reports/user-activity', [
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_users',
                'active_users',
                'new_registrations',
                'user_engagement' => [
                    '*' => [
                        'user_id',
                        'login_count',
                        'order_count',
                        'last_activity',
                    ],
                ],
            ],
            'period',
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGenerateSalesReport(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create test orders
        Order::factory()->count(10)->create([
            'total_amount' => 100.00,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports/sales', [
            'period' => 'weekly',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'total_revenue',
                'total_orders',
                'average_order_value',
                'daily_breakdown' => [
                    '*' => [
                        'date',
                        'revenue',
                        'orders',
                    ],
                ],
            ],
            'period',
            'comparison' => [
                'previous_period_revenue',
                'growth_percentage',
            ],
        ]);

        $responseData = $response->json();
        self::assertIsNumeric($responseData['data']['total_revenue']);
        self::assertIsInt($responseData['data']['total_orders']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGetSystemOverview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/reports/system-overview');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'system_health' => [
                'database_status',
                'cache_status',
                'storage_status',
            ],
            'statistics' => [
                'total_users',
                'total_products',
                'total_orders',
                'total_revenue',
            ],
            'performance_metrics' => [
                'average_response_time',
                'error_rate',
                'uptime_percentage',
            ],
            'generated_at',
        ]);

        $responseData = $response->json();
        self::assertContains($responseData['system_health']['database_status'], ['healthy', 'warning', 'error']);
        self::assertIsNumeric($responseData['statistics']['total_users']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGetEngagementMetrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create test data for engagement metrics
        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            Review::factory()->count(2)->create(['user_id' => $user->id]);
        }

        $response = $this->actingAs($admin)->get('/admin/reports/engagement-metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user_engagement' => [
                    'daily_active_users',
                    'weekly_active_users',
                    'monthly_active_users',
                ],
                'content_engagement' => [
                    'total_reviews',
                    'average_rating',
                    'review_response_rate',
                ],
                'conversion_metrics' => [
                    'visitor_to_user_rate',
                    'user_to_customer_rate',
                    'repeat_purchase_rate',
                ],
            ],
            'trends' => [
                '*' => [
                    'date',
                    'active_users',
                    'engagement_score',
                ],
            ],
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itRequiresAdminAccessForReports(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $reportEndpoints = [
            '/admin/reports/product-performance',
            '/admin/reports/user-activity',
            '/admin/reports/sales',
            '/admin/reports/system-overview',
            '/admin/reports/engagement-metrics',
        ];

        foreach ($reportEndpoints as $endpoint) {
            $response = $this->actingAs($user)->get($endpoint);
            $response->assertStatus(403);
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itValidatesDateRangeParameters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Test invalid date format
        $response = $this->actingAs($admin)->get('/admin/reports/sales', [
            'start_date' => 'invalid-date',
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date']);

        // Test end date before start date
        $response = $this->actingAs($admin)->get('/admin/reports/sales', [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}
