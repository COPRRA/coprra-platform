# COPRRA Code Quality Standards & Testing Guidelines

## Overview

This document outlines the code quality standards, testing guidelines, and best practices for the COPRRA project. Following these standards ensures maintainable, reliable, and scalable code.

## 📊 Current Quality Metrics

- **PHPStan Level**: 8 (Maximum)
- **Test Coverage**: Comprehensive across 7 test suites
- **Code Style**: PSR-12 compliant via Laravel Pint
- **Static Analysis**: Zero errors at level 8
- **Test Success Rate**: 100% (64 tests, 205 assertions)

## 🔧 Development Tools & Setup

### Required Tools

1. **PHPStan** - Static analysis at level 8
2. **Laravel Pint** - Code formatting (PSR-12)
3. **PHPMD** - Mess detection
4. **PHPUnit** - Testing framework
5. **Husky** - Git hooks for quality gates

### IDE Configuration

#### VS Code Extensions
```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "bradlc.vscode-tailwindcss",
    "vue.volar",
    "ms-vscode.vscode-json"
  ]
}
```

#### PHPStorm Settings
- Enable PHPStan integration
- Configure Pint as external formatter
- Set up PHPUnit test runner

## 📝 Code Standards

### PHP Code Standards

#### 1. Type Declarations
```php
// ✅ Good - Explicit types
public function processOrder(Order $order, int $quantity): OrderResult
{
    return new OrderResult($order, $quantity);
}

// ❌ Bad - Missing types
public function processOrder($order, $quantity)
{
    return new OrderResult($order, $quantity);
}
```

#### 2. PHPDoc Standards
```php
// ✅ Good - Minimal, focused PHPDoc
/**
 * Process customer order with validation.
 */
public function processOrder(Order $order, int $quantity): OrderResult
{
    // Implementation
}

// ❌ Bad - Redundant PHPDoc
/**
 * Process customer order with validation.
 * 
 * @param Order $order The order to process
 * @param int $quantity The quantity to process
 * @return OrderResult The result of processing
 */
public function processOrder(Order $order, int $quantity): OrderResult
{
    // Implementation
}
```

#### 3. Method Visibility
```php
// ✅ Good - Explicit visibility
class OrderService
{
    public function processOrder(Order $order): void
    {
        $this->validateOrder($order);
        $this->saveOrder($order);
    }
    
    private function validateOrder(Order $order): void
    {
        // Validation logic
    }
}
```

#### 4. Exception Handling
```php
// ✅ Good - Specific exceptions
public function findOrder(int $id): Order
{
    $order = Order::find($id);
    
    if (!$order) {
        throw new OrderNotFoundException("Order {$id} not found");
    }
    
    return $order;
}
```

### Frontend Code Standards

#### 1. Vue.js Components
```vue
<!-- ✅ Good - Composition API with TypeScript -->
<script setup lang="ts">
interface Props {
  order: Order
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false
})

const emit = defineEmits<{
  update: [order: Order]
  delete: [id: number]
}>()
</script>
```

#### 2. CSS/SCSS Standards
```scss
// ✅ Good - BEM methodology
.order-card {
  @apply bg-white rounded-lg shadow-md p-6;
  
  &__header {
    @apply flex justify-between items-center mb-4;
  }
  
  &__title {
    @apply text-lg font-semibold text-gray-900;
  }
  
  &--processing {
    @apply border-l-4 border-yellow-500;
  }
}
```

## 🧪 Testing Standards

### Test Suite Architecture

```
tests/
├── Unit/           # Isolated unit tests
├── Feature/        # Integration tests
├── AI/            # AI-specific functionality
├── Security/      # Security-related tests
├── Performance/   # Performance benchmarks
├── Integration/   # External service integration
└── Architecture/  # Architectural constraints
```

### Unit Testing Guidelines

#### 1. Test Structure
```php
class OrderServiceTest extends TestCase
{
    private OrderService $orderService;
    private MockObject $orderRepository;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->orderService = new OrderService($this->orderRepository);
    }
    
    /** @test */
    public function it_processes_valid_order(): void
    {
        // Arrange
        $order = Order::factory()->make();
        $this->orderRepository
            ->expects($this->once())
            ->method('save')
            ->with($order);
        
        // Act
        $result = $this->orderService->processOrder($order);
        
        // Assert
        $this->assertInstanceOf(OrderResult::class, $result);
        $this->assertTrue($result->isSuccessful());
    }
}
```

#### 2. Test Naming Conventions
```php
// ✅ Good - Descriptive test names
public function it_throws_exception_when_order_not_found(): void
public function it_calculates_total_with_tax_correctly(): void
public function it_sends_notification_after_successful_payment(): void

// ❌ Bad - Unclear test names
public function testOrder(): void
public function testCalculation(): void
public function testNotification(): void
```

### Feature Testing Guidelines

#### 1. API Testing
```php
class OrderApiTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_creates_order_via_api(): void
    {
        $user = User::factory()->create();
        $orderData = [
            'product_id' => Product::factory()->create()->id,
            'quantity' => 2,
            'notes' => 'Test order'
        ];
        
        $response = $this->actingAs($user)
            ->postJson('/api/orders', $orderData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'product_id',
                    'quantity',
                    'total',
                    'status'
                ]
            ]);
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $orderData['product_id'],
            'quantity' => $orderData['quantity']
        ]);
    }
}
```

### Test Data Management

#### 1. Factory Usage
```php
// ✅ Good - Specific factory states
$pendingOrder = Order::factory()->pending()->create();
$completedOrder = Order::factory()->completed()->create();
$expensiveProduct = Product::factory()->expensive()->create();

// ✅ Good - Factory relationships
$orderWithItems = Order::factory()
    ->has(OrderItem::factory()->count(3))
    ->create();
```

#### 2. Database Seeding for Tests
```php
class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users with specific roles
        User::factory()->admin()->create(['email' => 'admin@test.com']);
        User::factory()->customer()->create(['email' => 'customer@test.com']);
        
        // Create test products
        Product::factory()->count(10)->create();
    }
}
```

## 🔍 Static Analysis Configuration

### PHPStan Configuration
```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - app
        - config
        - database
        - routes
    excludePaths:
        - app/Console/Kernel.php
        - app/Http/Kernel.php
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
```

### PHPMD Configuration
```xml
<!-- phpmd.xml -->
<?xml version="1.0"?>
<ruleset name="COPRRA PHPMD Rules">
    <rule ref="rulesets/cleancode.xml">
        <exclude name="StaticAccess"/>
    </rule>
    <rule ref="rulesets/codesize.xml"/>
    <rule ref="rulesets/controversial.xml">
        <exclude name="Superglobals"/>
    </rule>
    <rule ref="rulesets/design.xml"/>
    <rule ref="rulesets/naming.xml">
        <exclude name="ShortVariable"/>
    </rule>
    <rule ref="rulesets/unusedcode.xml"/>
</ruleset>
```

## 🚀 CI/CD Integration

### Pre-commit Hooks
The project uses Husky with lint-staged for automated quality checks:

```json
{
  "lint-staged": {
    "*.php": [
      "vendor/bin/pint",
      "vendor/bin/phpstan analyse --memory-limit=1G"
    ],
    "*.{js,vue}": [
      "eslint --fix",
      "prettier --write"
    ],
    "*.{css,scss,vue}": [
      "stylelint --fix",
      "prettier --write"
    ]
  }
}
```

### GitHub Actions Workflow
- **Quality Gates**: PHPStan, Pint, PHPMD, ESLint, Stylelint
- **Test Matrix**: PHP 8.1, 8.2, 8.3
- **Security Scanning**: Trivy, Snyk
- **Coverage Reporting**: Codecov integration
- **Deployment**: Staging and production environments

## 📋 Code Review Checklist

### For Reviewers

#### PHP Code Review
- [ ] All methods have explicit return types
- [ ] PHPDoc is minimal and adds value
- [ ] No PHPStan errors at level 8
- [ ] Follows PSR-12 code style
- [ ] Proper exception handling
- [ ] No code duplication
- [ ] Appropriate visibility modifiers

#### Test Review
- [ ] Tests follow AAA pattern (Arrange, Act, Assert)
- [ ] Test names are descriptive
- [ ] Edge cases are covered
- [ ] Mocks are used appropriately
- [ ] Database state is properly managed
- [ ] No flaky tests

#### Frontend Review
- [ ] Components use Composition API
- [ ] TypeScript types are defined
- [ ] CSS follows BEM methodology
- [ ] Accessibility standards met
- [ ] Performance considerations

### For Authors

#### Before Submitting PR
- [ ] Run `vendor/bin/phpstan analyse`
- [ ] Run `vendor/bin/pint --test`
- [ ] Run `vendor/bin/phpunit`
- [ ] Run `npm run lint`
- [ ] Run `npm run test`
- [ ] Update documentation if needed
- [ ] Add/update tests for new features

## 🛠️ Development Workflow

### 1. Feature Development
```bash
# Create feature branch
git checkout -b feature/order-processing

# Make changes with quality checks
npm run dev  # Frontend development
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/phpunit  # Run tests

# Commit with pre-commit hooks
git add .
git commit -m "feat: add order processing functionality"

# Push and create PR
git push origin feature/order-processing
```

### 2. Bug Fixes
```bash
# Create bugfix branch
git checkout -b bugfix/order-calculation

# Write failing test first
vendor/bin/phpunit --filter=OrderCalculationTest

# Fix the bug
# Ensure test passes
vendor/bin/phpunit --filter=OrderCalculationTest

# Run full test suite
vendor/bin/phpunit
```

### 3. Refactoring
```bash
# Ensure all tests pass before refactoring
vendor/bin/phpunit

# Make refactoring changes
# Ensure tests still pass
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse
```

## 📚 Resources & Training

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Vue.js 3 Documentation](https://vuejs.org/guide/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

### Code Quality Tools
- [Laravel Pint](https://laravel.com/docs/pint)
- [PHPUnit](https://phpunit.de/documentation.html)
- [ESLint](https://eslint.org/docs/user-guide/)
- [Stylelint](https://stylelint.io/user-guide/)

### Best Practices
- [Clean Code Principles](https://github.com/jupeter/clean-code-php)
- [SOLID Principles](https://github.com/wataridori/solid-php-example)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)

## 🔄 Continuous Improvement

### Monthly Quality Reviews
- Review PHPStan error trends
- Analyze test coverage reports
- Update coding standards as needed
- Team training on new tools/practices

### Quality Metrics Tracking
- PHPStan error count
- Test coverage percentage
- Code review turnaround time
- Bug escape rate

### Tool Updates
- Keep static analysis tools updated
- Review and update coding standards
- Evaluate new quality tools
- Update CI/CD pipeline as needed

---

## 📞 Support & Questions

For questions about code quality standards or tooling:
- Create an issue in the project repository
- Discuss in team meetings
- Refer to this documentation
- Consult with senior developers

**Remember**: Quality is everyone's responsibility. These standards help us build better software together! 🚀