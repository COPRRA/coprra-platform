# COPRRA Testing Guidelines

## 🎯 Testing Philosophy

Our testing strategy follows the **Testing Pyramid** principle:
- **70% Unit Tests** - Fast, isolated, focused
- **20% Integration Tests** - Component interactions
- **10% End-to-End Tests** - Full user workflows

## 📊 Current Test Metrics

- **Total Tests**: 644 across 7 test suites
- **Test Success Rate**: 100% (64 tests, 205 assertions)
- **Test-to-Source Ratio**: 3.2:1
- **Coverage Areas**: Controllers (69.8%), Services (39.9%), Models (181.5%)

## 🏗️ Test Suite Architecture

### Test Suite Breakdown

| Suite | Purpose | Test Count | Focus Area |
|-------|---------|------------|------------|
| **Unit** | Isolated component testing | 514 | Business logic, utilities |
| **Feature** | Laravel feature testing | 112 | HTTP endpoints, workflows |
| **AI** | AI functionality testing | 4 | Machine learning features |
| **Security** | Security vulnerability testing | 6 | Authentication, authorization |
| **Performance** | Performance benchmarking | 8 | Response times, memory usage |
| **Integration** | External service integration | 0 | Third-party APIs |
| **Architecture** | Architectural constraints | 0 | Design patterns, dependencies |

## 🧪 Unit Testing Guidelines

### Test Structure & Organization

#### 1. Test Class Organization
```php
<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private MockObject $orderRepository;
    private MockObject $notificationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        
        $this->orderService = new OrderService(
            $this->orderRepository,
            $this->notificationService
        );
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up any test-specific resources
    }
}
```

#### 2. Test Method Structure (AAA Pattern)
```php
/** @test */
public function it_calculates_order_total_with_tax_correctly(): void
{
    // Arrange
    $order = Order::factory()->make([
        'subtotal' => 100.00,
        'tax_rate' => 0.08
    ]);
    
    // Act
    $total = $this->orderService->calculateTotal($order);
    
    // Assert
    $this->assertEquals(108.00, $total);
}
```

### Test Naming Conventions

#### ✅ Good Test Names
```php
// Behavior-driven naming
public function it_throws_exception_when_order_not_found(): void
public function it_sends_notification_after_successful_payment(): void
public function it_calculates_discount_for_premium_customers(): void

// Action-outcome naming
public function create_order_with_valid_data_returns_order_instance(): void
public function update_order_status_to_completed_triggers_notification(): void
public function delete_order_removes_associated_items(): void
```

#### ❌ Bad Test Names
```php
public function testOrder(): void
public function testCalculation(): void
public function test1(): void
public function orderTest(): void
```

### Mocking Best Practices

#### 1. Mock External Dependencies
```php
/** @test */
public function it_processes_payment_through_gateway(): void
{
    // Arrange
    $paymentGateway = $this->createMock(PaymentGateway::class);
    $paymentGateway
        ->expects($this->once())
        ->method('charge')
        ->with(
            $this->equalTo(100.00),
            $this->equalTo('4111111111111111')
        )
        ->willReturn(new PaymentResult(true, 'txn_123'));
    
    $paymentService = new PaymentService($paymentGateway);
    
    // Act
    $result = $paymentService->processPayment(100.00, '4111111111111111');
    
    // Assert
    $this->assertTrue($result->isSuccessful());
    $this->assertEquals('txn_123', $result->getTransactionId());
}
```

#### 2. Avoid Over-Mocking
```php
// ✅ Good - Mock external dependencies only
public function it_creates_order_with_items(): void
{
    $orderRepository = $this->createMock(OrderRepository::class);
    $orderRepository->expects($this->once())->method('save');
    
    $orderService = new OrderService($orderRepository);
    $order = Order::factory()->make();
    
    $orderService->createOrder($order);
}

// ❌ Bad - Over-mocking internal objects
public function it_creates_order_with_items(): void
{
    $order = $this->createMock(Order::class);
    $orderItem = $this->createMock(OrderItem::class);
    // ... excessive mocking of value objects
}
```

### Data Providers for Parameterized Tests

```php
/**
 * @test
 * @dataProvider discountCalculationProvider
 */
public function it_calculates_discount_correctly(
    float $orderTotal,
    string $customerType,
    float $expectedDiscount
): void {
    $customer = Customer::factory()->make(['type' => $customerType]);
    $order = Order::factory()->make(['total' => $orderTotal]);
    
    $discount = $this->discountService->calculateDiscount($order, $customer);
    
    $this->assertEquals($expectedDiscount, $discount);
}

public static function discountCalculationProvider(): array
{
    return [
        'regular customer, small order' => [50.00, 'regular', 0.00],
        'regular customer, large order' => [500.00, 'regular', 25.00],
        'premium customer, small order' => [50.00, 'premium', 5.00],
        'premium customer, large order' => [500.00, 'premium', 75.00],
        'vip customer, any order' => [100.00, 'vip', 20.00],
    ];
}
```

## 🌐 Feature Testing Guidelines

### HTTP Testing

#### 1. API Endpoint Testing
```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function authenticated_user_can_create_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 99.99]);
        
        $orderData = [
            'product_id' => $product->id,
            'quantity' => 2,
            'shipping_address' => [
                'street' => '123 Test St',
                'city' => 'Test City',
                'postal_code' => '12345'
            ]
        ];
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/orders', $orderData);
        
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'product_id',
                    'quantity',
                    'total',
                    'status',
                    'created_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'total' => 199.98
                ]
            ]);
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }
    
    /** @test */
    public function unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', []);
        
        $response->assertStatus(401);
    }
    
    /** @test */
    public function order_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/orders', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }
}
```

#### 2. Web Route Testing
```php
/** @test */
public function user_can_view_order_details_page(): void
{
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    
    $response = $this->actingAs($user)
        ->get("/orders/{$order->id}");
    
    $response->assertStatus(200)
        ->assertViewIs('orders.show')
        ->assertViewHas('order', $order)
        ->assertSee($order->id)
        ->assertSee($order->total);
}
```

### Database Testing

#### 1. Factory Usage
```php
// Create related models
$order = Order::factory()
    ->for(User::factory()->create())
    ->has(OrderItem::factory()->count(3))
    ->create();

// Create with specific states
$pendingOrder = Order::factory()->pending()->create();
$completedOrder = Order::factory()->completed()->create();

// Create with custom attributes
$expensiveOrder = Order::factory()->create([
    'total' => 1000.00,
    'status' => 'pending'
]);
```

#### 2. Database Assertions
```php
/** @test */
public function order_creation_stores_correct_data(): void
{
    $user = User::factory()->create();
    $orderData = [
        'product_id' => Product::factory()->create()->id,
        'quantity' => 2
    ];
    
    $this->actingAs($user)
        ->post('/orders', $orderData);
    
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'product_id' => $orderData['product_id'],
        'quantity' => $orderData['quantity']
    ]);
    
    $this->assertDatabaseCount('orders', 1);
}

/** @test */
public function order_deletion_removes_associated_items(): void
{
    $order = Order::factory()
        ->has(OrderItem::factory()->count(3))
        ->create();
    
    $order->delete();
    
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    $this->assertDatabaseCount('order_items', 0);
}
```

## 🔒 Security Testing

### Authentication & Authorization Tests

```php
<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSecurityTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_cannot_access_other_users_orders(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->for($user2)->create();
        
        $response = $this->actingAs($user1)
            ->get("/orders/{$order->id}");
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_access_all_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        
        $response = $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}");
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function api_endpoints_require_valid_token(): void
    {
        $response = $this->getJson('/api/orders');
        
        $response->assertStatus(401);
    }
    
    /** @test */
    public function sql_injection_attempts_are_prevented(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/orders?search=\'; DROP TABLE orders; --');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', []); // Table still exists
    }
}
```

### Input Validation Tests

```php
/** @test */
public function order_creation_sanitizes_input(): void
{
    $user = User::factory()->create();
    
    $maliciousData = [
        'product_id' => 1,
        'quantity' => 1,
        'notes' => '<script>alert("xss")</script>',
        'shipping_address' => [
            'street' => '<img src=x onerror=alert(1)>',
            'city' => 'Normal City'
        ]
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/orders', $maliciousData);
    
    $response->assertStatus(201);
    
    $order = Order::latest()->first();
    $this->assertStringNotContainsString('<script>', $order->notes);
    $this->assertStringNotContainsString('<img', $order->shipping_address['street']);
}
```

## ⚡ Performance Testing

### Response Time Testing

```php
<?php

namespace Tests\Feature\Performance;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function order_list_loads_within_acceptable_time(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(100)->create();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)
            ->get('/orders');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'Order list should load in under 500ms');
    }
    
    /** @test */
    public function api_pagination_handles_large_datasets(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(1000)->create();
        
        $response = $this->actingAs($user)
            ->getJson('/api/orders?per_page=50');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page']
            ]);
        
        $this->assertCount(50, $response->json('data'));
    }
}
```

### Memory Usage Testing

```php
/** @test */
public function bulk_order_processing_stays_within_memory_limits(): void
{
    $initialMemory = memory_get_usage();
    
    $orders = Order::factory()->count(1000)->create();
    
    foreach ($orders as $order) {
        $this->orderService->processOrder($order);
    }
    
    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;
    
    // Should not increase memory by more than 50MB
    $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
}
```

## 🤖 AI Feature Testing

### AI Model Testing

```php
<?php

namespace Tests\Feature\AI;

use App\Services\AI\RecommendationService;
use App\Models\User;
use App\Models\Product;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    private RecommendationService $recommendationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->recommendationService = app(RecommendationService::class);
    }
    
    /** @test */
    public function it_generates_product_recommendations_for_user(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(10)->create();
        
        $recommendations = $this->recommendationService
            ->getRecommendationsForUser($user);
        
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertLessThanOrEqual(5, count($recommendations));
        
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('product_id', $recommendation);
            $this->assertArrayHasKey('score', $recommendation);
            $this->assertIsFloat($recommendation['score']);
            $this->assertGreaterThanOrEqual(0, $recommendation['score']);
            $this->assertLessThanOrEqual(1, $recommendation['score']);
        }
    }
    
    /** @test */
    public function it_handles_new_users_without_purchase_history(): void
    {
        $newUser = User::factory()->create();
        
        $recommendations = $this->recommendationService
            ->getRecommendationsForUser($newUser);
        
        $this->assertIsArray($recommendations);
        // Should return popular products for new users
        $this->assertNotEmpty($recommendations);
    }
}
```

## 🔧 Test Utilities & Helpers

### Custom Assertions

```php
<?php

namespace Tests\Concerns;

trait CustomAssertions
{
    protected function assertValidUuid(string $uuid): void
    {
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
            'Expected valid UUID format'
        );
    }
    
    protected function assertValidEmail(string $email): void
    {
        $this->assertMatchesRegularExpression(
            '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            $email,
            'Expected valid email format'
        );
    }
    
    protected function assertValidMoney(float $amount): void
    {
        $this->assertGreaterThanOrEqual(0, $amount);
        $this->assertEquals(round($amount, 2), $amount, 'Money should have max 2 decimal places');
    }
    
    protected function assertOrderStatus(Order $order, string $expectedStatus): void
    {
        $this->assertEquals(
            $expectedStatus,
            $order->status,
            "Expected order status to be {$expectedStatus}, got {$order->status}"
        );
    }
}
```

### Test Data Builders

```php
<?php

namespace Tests\Builders;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;

class OrderBuilder
{
    private array $attributes = [];
    
    public static function make(): self
    {
        return new self();
    }
    
    public function forUser(User $user): self
    {
        $this->attributes['user_id'] = $user->id;
        return $this;
    }
    
    public function withProduct(Product $product, int $quantity = 1): self
    {
        $this->attributes['product_id'] = $product->id;
        $this->attributes['quantity'] = $quantity;
        return $this;
    }
    
    public function withStatus(string $status): self
    {
        $this->attributes['status'] = $status;
        return $this;
    }
    
    public function withTotal(float $total): self
    {
        $this->attributes['total'] = $total;
        return $this;
    }
    
    public function build(): Order
    {
        return Order::factory()->create($this->attributes);
    }
}

// Usage in tests:
$order = OrderBuilder::make()
    ->forUser($user)
    ->withProduct($product, 2)
    ->withStatus('completed')
    ->withTotal(199.98)
    ->build();
```

## 📊 Test Coverage & Reporting

### Coverage Goals

| Component Type | Target Coverage | Current Coverage |
|----------------|-----------------|------------------|
| Controllers | 90% | 69.8% |
| Services | 95% | 39.9% |
| Models | 85% | 181.5% |
| Repositories | 90% | TBD |
| Utilities | 95% | TBD |

### Coverage Analysis Commands

```bash
# Generate coverage report (requires Xdebug or PCOV)
vendor/bin/phpunit --coverage-html reports/coverage

# Generate coverage text report
vendor/bin/phpunit --coverage-text

# Generate coverage for specific directory
vendor/bin/phpunit --coverage-text app/Services

# Check coverage threshold
vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

### Test Reporting

```bash
# Generate test documentation
vendor/bin/phpunit --testdox

# Generate JUnit XML for CI
vendor/bin/phpunit --log-junit reports/junit.xml

# Verbose test output
vendor/bin/phpunit --verbose

# Test specific suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
```

## 🚀 Continuous Integration

### GitHub Actions Test Matrix

```yaml
strategy:
  matrix:
    php-version: [8.1, 8.2, 8.3]
    dependency-version: [prefer-lowest, prefer-stable]
    
steps:
  - name: Run PHPUnit Tests
    run: vendor/bin/phpunit --coverage-clover=coverage.xml
    
  - name: Upload Coverage to Codecov
    uses: codecov/codecov-action@v3
    with:
      file: ./coverage.xml
```

### Test Environment Setup

```bash
# Setup test database
php artisan migrate:fresh --env=testing
php artisan db:seed --class=TestDatabaseSeeder --env=testing

# Run specific test suites
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
vendor/bin/phpunit --testsuite=Security

# Parallel testing (if configured)
vendor/bin/paratest --processes=4
```

## 🔍 Debugging Tests

### Common Debugging Techniques

```php
/** @test */
public function debug_test_example(): void
{
    // Dump and die for debugging
    $order = Order::factory()->create();
    dd($order->toArray());
    
    // Dump without stopping
    dump($order->status);
    
    // Ray debugging (if installed)
    ray($order)->label('Order Data');
    
    // Laravel Log debugging
    \Log::info('Test Debug', ['order' => $order]);
    
    // Assert with custom message
    $this->assertEquals(
        'pending',
        $order->status,
        'Order status should be pending after creation'
    );
}
```

### Test Isolation Issues

```php
// Ensure proper test isolation
protected function setUp(): void
{
    parent::setUp();
    
    // Clear any cached data
    Cache::flush();
    
    // Reset any global state
    Config::set('app.debug', true);
}

protected function tearDown(): void
{
    // Clean up after test
    Storage::fake('local')->deleteDirectory('test-files');
    
    parent::tearDown();
}
```

## 📋 Testing Checklist

### Before Writing Tests
- [ ] Understand the requirement clearly
- [ ] Identify edge cases and error conditions
- [ ] Plan test data requirements
- [ ] Consider performance implications

### Writing Tests
- [ ] Follow AAA pattern (Arrange, Act, Assert)
- [ ] Use descriptive test names
- [ ] Test one behavior per test method
- [ ] Mock external dependencies appropriately
- [ ] Include both positive and negative test cases

### After Writing Tests
- [ ] Ensure tests pass consistently
- [ ] Verify test isolation (tests don't affect each other)
- [ ] Check test performance (avoid slow tests)
- [ ] Review test coverage
- [ ] Update documentation if needed

### Code Review for Tests
- [ ] Tests are readable and maintainable
- [ ] Test names clearly describe behavior
- [ ] Appropriate use of mocks and stubs
- [ ] Edge cases are covered
- [ ] No flaky or brittle tests

## 🎓 Testing Best Practices Summary

1. **Write tests first** (TDD) or alongside code
2. **Keep tests simple** and focused
3. **Use meaningful test names** that describe behavior
4. **Follow the AAA pattern** consistently
5. **Mock external dependencies** but not internal objects
6. **Test behavior, not implementation**
7. **Maintain test independence** - tests should not depend on each other
8. **Keep tests fast** - slow tests discourage running them
9. **Use factories and builders** for test data creation
10. **Regular test maintenance** - update tests when code changes

---

## 📞 Support & Resources

- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Laravel Testing**: https://laravel.com/docs/testing
- **Mockery Documentation**: http://docs.mockery.io/
- **Test-Driven Development**: https://martinfowler.com/bliki/TestDrivenDevelopment.html

**Remember**: Good tests are an investment in code quality and team productivity! 🧪✨