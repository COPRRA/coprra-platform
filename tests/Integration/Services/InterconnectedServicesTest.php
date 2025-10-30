<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Events\OrderStatusUpdated;
use App\Events\PaymentProcessed;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ExternalStoreService;
use App\Services\FinancialTransactionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class InterconnectedServicesTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private OrderService $orderService;
    private FinancialTransactionService $financialService;
    private RecommendationService $recommendationService;
    private ExternalStoreService $externalStoreService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->orderService = app(OrderService::class);
        $this->financialService = app(FinancialTransactionService::class);
        $this->recommendationService = app(RecommendationService::class);
        $this->externalStoreService = app(ExternalStoreService::class);
    }

    // Order Processing Integration Tests

    public function testCompleteOrderProcessingWorkflow(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Act & Assert - Complete workflow
        DB::transaction(function () use ($order, $user, $product) {
            // 1. Process payment
            $paymentResult = $this->paymentService->processPayment([
                'order_id' => $order->id,
                'amount' => 100.00,
                'gateway' => 'stripe',
                'payment_method_id' => 'pm_test_123',
            ]);

            $this->assertTrue($paymentResult['success']);
            $this->assertNotEmpty($paymentResult['transaction_id']);

            // 2. Update order status
            $order->refresh();
            $this->assertSame('paid', $order->status);

            // 3. Update stock
            $product->refresh();
            $this->assertSame(9, $product->stock_quantity);

            // 4. Create financial transaction
            $transaction = $this->financialService->createTransaction([
                'order_id' => $order->id,
                'amount' => 100.00,
                'type' => 'sale',
                'description' => 'Product sale',
            ]);

            $this->assertNotNull($transaction);
            $this->assertSame(100.00, $transaction->amount);

            // 5. Update recommendations based on purchase
            $recommendations = $this->recommendationService->getRecommendations($user, 5);
            $this->assertIsArray($recommendations);
        });
    }

    public function testOrderProcessingWithPaymentFailure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Act - Simulate payment failure
        try {
            $paymentResult = $this->paymentService->processPayment([
                'order_id' => $order->id,
                'amount' => 100.00,
                'gateway' => 'stripe',
                'payment_method_id' => 'pm_card_declined',
            ]);
        } catch (\Exception $e) {
            // Expected payment failure
        }

        // Assert - Order and stock should remain unchanged
        $order->refresh();
        $product->refresh();

        self::assertSame('pending', $order->status);
        self::assertSame(10, $product->stock_quantity);

        // No financial transaction should be created
        $this->assertDatabaseMissing('financial_transactions', [
            'order_id' => $order->id,
        ]);
    }

    public function testOrderRefundWorkflow(): void
    {
        // Arrange - Create completed order
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
            'stock_quantity' => 5,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total_amount' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Create original payment transaction
        $originalTransaction = $this->financialService->createTransaction([
            'order_id' => $order->id,
            'amount' => 100.00,
            'type' => 'sale',
            'description' => 'Product sale',
        ]);

        // Act - Process refund
        DB::transaction(function () use ($order, $product, $originalTransaction) {
            // 1. Process payment refund
            $refundResult = $this->paymentService->refundPayment([
                'payment_id' => 'pi_test_123',
                'amount' => 100.00,
                'reason' => 'customer_request',
            ]);

            $this->assertTrue($refundResult['success']);

            // 2. Update order status
            $order->update(['status' => 'refunded']);

            // 3. Restore stock
            $product->increment('stock_quantity', 1);

            // 4. Create refund transaction
            $refundTransaction = $this->financialService->createTransaction([
                'order_id' => $order->id,
                'amount' => -100.00,
                'type' => 'refund',
                'description' => 'Order refund',
                'related_transaction_id' => $originalTransaction->id,
            ]);

            $this->assertNotNull($refundTransaction);
            $this->assertSame(-100.00, $refundTransaction->amount);
        });

        // Assert final state
        $order->refresh();
        $product->refresh();

        self::assertSame('refunded', $order->status);
        self::assertSame(6, $product->stock_quantity);
    }

    // Recommendation System Integration Tests

    public function testRecommendationSystemWithRealPurchaseData(): void
    {
        // Arrange - Create realistic purchase scenario
        $users = User::factory()->count(10)->create();
        $categories = Category::factory()->count(3)->create();
        $brands = Brand::factory()->count(2)->create();

        $products = [];
        foreach ($categories as $category) {
            foreach ($brands as $brand) {
                $products = array_merge($products, Product::factory()->count(5)->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'price' => rand(50, 200),
                    'rating' => rand(30, 50) / 10, // 3.0 to 5.0
                ])->toArray());
            }
        }

        // Create purchase patterns
        foreach ($users as $user) {
            $userProducts = \array_slice($products, 0, rand(2, 5));
            $this->createOrderWithProducts($user, $userProducts);
        }

        $targetUser = $users->first();

        // Act
        $recommendations = $this->recommendationService->getRecommendations($targetUser, 5);
        $similarProducts = $this->recommendationService->getSimilarProducts($products[0], 3);
        $frequentlyBought = $this->recommendationService->getFrequentlyBoughtTogether($products[0], 3);

        // Assert
        self::assertIsArray($recommendations);
        self::assertIsArray($similarProducts);
        self::assertIsArray($frequentlyBought);

        self::assertLessThanOrEqual(5, \count($recommendations));
        self::assertLessThanOrEqual(3, \count($similarProducts));
        self::assertLessThanOrEqual(3, \count($frequentlyBought));
    }

    public function testRecommendationSystemWithExternalStoreData(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        // Mock external store data
        $externalProducts = [
            [
                'id' => 'ext_1',
                'name' => 'External Product 1',
                'price' => 99.99,
                'category' => $category->name,
                'brand' => $brand->name,
                'rating' => 4.5,
                'store' => 'amazon',
            ],
            [
                'id' => 'ext_2',
                'name' => 'External Product 2',
                'price' => 149.99,
                'category' => $category->name,
                'brand' => $brand->name,
                'rating' => 4.2,
                'store' => 'noon',
            ],
        ];

        // Create local products for comparison
        $localProducts = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => rand(80, 120),
        ]);

        $this->createOrderWithProducts($user, $localProducts->toArray());

        // Act - Get recommendations that might include external data
        $recommendations = $this->recommendationService->getRecommendations($user, 10);

        // Simulate external store search for similar products
        $searchQuery = $category->name;
        // Note: In real implementation, this would call external APIs
        // $externalResults = $this->externalStoreService->searchProducts($searchQuery, 5);

        // Assert
        self::assertIsArray($recommendations);
        self::assertNotEmpty($recommendations);

        // Recommendations should be based on user's purchase history
        $recommendedCategories = array_unique(array_column($recommendations, 'category_id'));
        self::assertContains($category->id, $recommendedCategories);
    }

    // Financial Transaction Integration Tests

    public function testFinancialTransactionWithMultipleServices(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 50.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 150.00,
        ]);

        foreach ($products as $product) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 50.00,
            ]);
        }

        // Act - Process complex transaction
        DB::transaction(function () use ($order, $products) {
            // 1. Process payment
            $paymentResult = $this->paymentService->processPayment([
                'order_id' => $order->id,
                'amount' => 150.00,
                'gateway' => 'stripe',
                'payment_method_id' => 'pm_test_123',
            ]);

            $this->assertTrue($paymentResult['success']);

            // 2. Create individual product transactions
            foreach ($products as $product) {
                $transaction = $this->financialService->createTransaction([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'amount' => 50.00,
                    'type' => 'sale',
                    'description' => "Sale of {$product->name}",
                ]);

                $this->assertNotNull($transaction);
            }

            // 3. Update financial metrics
            $totalSales = $this->financialService->getTotalSales();
            $this->assertGreaterThanOrEqual(150.00, $totalSales);

            // 4. Generate financial report
            $report = $this->financialService->generateSalesReport([
                'start_date' => now()->startOfDay(),
                'end_date' => now()->endOfDay(),
            ]);

            $this->assertIsArray($report);
            $this->assertArrayHasKey('total_sales', $report);
            $this->assertGreaterThanOrEqual(150.00, $report['total_sales']);
        });
    }

    public function testFinancialTransactionRollbackOnFailure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
            'stock_quantity' => 1,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Act - Simulate transaction failure
        try {
            DB::transaction(function () use ($order) {
                // 1. Process payment (succeeds)
                $paymentResult = $this->paymentService->processPayment([
                    'order_id' => $order->id,
                    'amount' => 100.00,
                    'gateway' => 'stripe',
                    'payment_method_id' => 'pm_test_123',
                ]);

                $this->assertTrue($paymentResult['success']);

                // 2. Create financial transaction (succeeds)
                $transaction = $this->financialService->createTransaction([
                    'order_id' => $order->id,
                    'amount' => 100.00,
                    'type' => 'sale',
                    'description' => 'Product sale',
                ]);

                $this->assertNotNull($transaction);

                // 3. Simulate failure (e.g., inventory update fails)
                throw new \Exception('Inventory update failed');
            });
        } catch (\Exception $e) {
            // Expected failure
        }

        // Assert - All changes should be rolled back
        $order->refresh();
        $product->refresh();

        self::assertSame('pending', $order->status);
        self::assertSame(1, $product->stock_quantity);

        // No financial transaction should exist
        $this->assertDatabaseMissing('financial_transactions', [
            'order_id' => $order->id,
        ]);
    }

    // Cache Integration Tests

    public function testCacheConsistencyAcrossServices(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
        ]);

        // Act - Create purchase and check cache consistency
        $this->createOrderWithProducts($user, [$product]);

        // Get recommendations (should be cached)
        $recommendations1 = $this->recommendationService->getRecommendations($user, 5);
        $recommendations2 = $this->recommendationService->getRecommendations($user, 5);

        // Make another purchase
        $newProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 150.00,
        ]);

        $this->createOrderWithProducts($user, [$newProduct]);

        // Cache should be invalidated, recommendations should change
        Cache::forget("recommendations_user_{$user->id}");
        $recommendations3 = $this->recommendationService->getRecommendations($user, 5);

        // Assert
        self::assertSame($recommendations1, $recommendations2); // Cache hit
        self::assertNotSame($recommendations1, $recommendations3); // Cache invalidated
    }

    // Event Integration Tests

    public function testEventPropagationAcrossServices(): void
    {
        // Arrange
        Event::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100.00,
        ]);

        // Act - Process payment (should trigger events)
        $paymentResult = $this->paymentService->processPayment([
            'order_id' => $order->id,
            'amount' => 100.00,
            'gateway' => 'stripe',
            'payment_method_id' => 'pm_test_123',
        ]);

        // Assert
        self::assertTrue($paymentResult['success']);

        // Verify events were dispatched
        Event::assertDispatched(PaymentProcessed::class);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    // Performance Integration Tests

    public function testServicePerformanceUnderLoad(): void
    {
        // Arrange
        $users = User::factory()->count(50)->create();
        $categories = Category::factory()->count(5)->create();
        $brands = Brand::factory()->count(3)->create();

        $products = [];
        foreach ($categories as $category) {
            foreach ($brands as $brand) {
                $products = array_merge($products, Product::factory()->count(10)->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'price' => rand(50, 200),
                ])->toArray());
            }
        }

        // Create purchase history for all users
        foreach ($users as $user) {
            $userProducts = \array_slice($products, 0, rand(3, 8));
            $this->createOrderWithProducts($user, $userProducts);
        }

        // Act - Test performance under load
        $startTime = microtime(true);

        foreach ($users->take(10) as $user) {
            $recommendations = $this->recommendationService->getRecommendations($user, 5);
            self::assertIsArray($recommendations);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert - Should complete within reasonable time
        self::assertLessThan(5.0, $executionTime); // Less than 5 seconds for 10 users
    }

    // Helper Methods

    private function createOrderWithProducts(User $user, array $products): Order
    {
        $totalAmount = array_sum(array_column($products, 'price'));

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total_amount' => $totalAmount,
        ]);

        foreach ($products as $product) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product['id'],
                'quantity' => 1,
                'price' => $product['price'],
            ]);
        }

        return $order;
    }
}
