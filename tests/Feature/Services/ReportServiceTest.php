<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ReportService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGeneratesSalesReport()
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00]);

        Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        $startDate = now()->subDays(7);
        $endDate = now();

        // Act
        $report = $this->service->generateSalesReport($startDate, $endDate);

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('total_sales', $report);
        self::assertArrayHasKey('total_orders', $report);
        self::assertArrayHasKey('average_order_value', $report);
        self::assertArrayHasKey('period', $report);
        self::assertSame(100.00, $report['total_sales']);
        self::assertSame(1, $report['total_orders']);
        self::assertSame(100.00, $report['average_order_value']);
    }

    public function testGeneratesSalesReportWithDefaultDates()
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'total' => 150.00,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        // Act
        $report = $this->service->generateSalesReport();

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('total_sales', $report);
        self::assertArrayHasKey('total_orders', $report);
        self::assertArrayHasKey('period', $report);
        self::assertGreaterThanOrEqual(0, $report['total_sales']);
        self::assertGreaterThanOrEqual(0, $report['total_orders']);
    }

    public function testGeneratesSalesReportWithNullStartDate()
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'total' => 200.00,
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        $endDate = now();

        // Act
        $report = $this->service->generateSalesReport(null, $endDate);

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('total_sales', $report);
        self::assertArrayHasKey('total_orders', $report);
        self::assertSame(200.00, $report['total_sales']);
        self::assertSame(1, $report['total_orders']);
    }

    public function testGeneratesSalesReportWithNullEndDate()
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->create([
            'user_id' => $user->id,
            'total' => 75.00,
            'status' => 'completed',
            'created_at' => now()->subDays(1),
        ]);

        $startDate = now()->subDays(7);

        // Act
        $report = $this->service->generateSalesReport($startDate, null);

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('total_sales', $report);
        self::assertArrayHasKey('total_orders', $report);
        self::assertSame(75.00, $report['total_sales']);
        self::assertSame(1, $report['total_orders']);
    }

    public function testHandlesInvalidDateRange()
    {
        // Arrange
        $startDate = now();
        $endDate = now()->subDays(7); // End date before start date

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date must be after start date');

        $this->service->generateSalesReport($startDate, $endDate);
    }

    public function testGeneratesUserActivityReport()
    {
        // Arrange
        $user = User::factory()->create(['created_at' => now()->subDays(5)]);

        // Act
        $report = $this->service->generateUserActivityReport();

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('total_users', $report);
        self::assertArrayHasKey('new_users_this_month', $report);
        self::assertArrayHasKey('active_users', $report);
        self::assertGreaterThanOrEqual(1, $report['total_users']);
    }

    public function testGeneratesProductPerformanceReport()
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Test Product']);
        $user = User::factory()->create();

        Order::factory()->create([
            'user_id' => $user->id,
            'total' => 100.00,
            'status' => 'completed',
        ]);

        // Act
        $report = $this->service->generateProductPerformanceReport();

        // Assert
        self::assertIsArray($report);
        self::assertArrayHasKey('top_selling_products', $report);
        self::assertArrayHasKey('total_products', $report);
        self::assertArrayHasKey('revenue_by_product', $report);
        self::assertGreaterThanOrEqual(1, $report['total_products']);
    }
}
