# COPRRA Mocking Strategy Guide

## Overview

This document outlines the comprehensive mocking strategy for the COPRRA project, providing guidelines, best practices, and recommendations based on our testing framework audit.

## Table of Contents

1. [Current State Analysis](#current-state-analysis)
2. [Mocking Framework Standards](#mocking-framework-standards)
3. [Best Practices](#best-practices)
4. [Common Anti-Patterns](#common-anti-patterns)
5. [Implementation Guidelines](#implementation-guidelines)
6. [Test Isolation Strategy](#test-isolation-strategy)
7. [Mock Validation](#mock-validation)
8. [Examples](#examples)
9. [Migration Guide](#migration-guide)

## Current State Analysis

### Identified Issues

1. **Mixed Mocking Frameworks**: Inconsistent use of Mockery and PHPUnit's createMock
2. **Direct Facade Mocking**: Direct mocking of Laravel facades (DB, Cache) causing tight coupling
3. **Missing Method Validation**: Mocks created without verifying method existence
4. **Incomplete Cleanup**: Missing `Mockery::close()` in tearDown methods
5. **Over-mocking**: Mocking simple dependencies that could use real implementations
6. **Test Isolation Issues**: Static variables and shared state affecting test independence

### Audit Findings Summary

- **Total Test Files Analyzed**: 50+
- **Files with Mocking Issues**: 15
- **Critical Issues Fixed**: 8
- **Recommended Improvements**: 12

## Mocking Framework Standards

### Primary Framework: PHPUnit createMock

**Use PHPUnit's `createMock()` as the primary mocking framework for:**
- Interface mocking
- Simple class mocking
- Type-safe mock creation

```php
// ✅ Preferred approach
$loggerMock = $this->createMock(LoggerInterface::class);
$serviceMock = $this->createMock(UserServiceInterface::class);
```

### Secondary Framework: Mockery

**Use Mockery only when:**
- Complex mock behavior is required
- Partial mocking is necessary
- Advanced expectations are needed

```php
// ✅ Acceptable for complex scenarios
$serviceMock = \Mockery::mock(ComplexService::class)->makePartial();
$serviceMock->shouldReceive('complexMethod')
    ->with(\Mockery::on(function ($arg) {
        return $arg instanceof ComplexObject && $arg->isValid();
    }))
    ->andReturn($expectedResult);
```

## Best Practices

### 1. Mock Interfaces, Not Concrete Classes

```php
// ✅ Good - Mock the interface
$repositoryMock = $this->createMock(UserRepositoryInterface::class);

// ❌ Avoid - Mocking concrete class
$repositoryMock = $this->createMock(EloquentUserRepository::class);
```

### 2. Verify Method Existence

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Verify interface methods exist
    $this->assertTrue(
        method_exists(LoggerInterface::class, 'info'),
        'LoggerInterface must have info method'
    );
    
    $this->loggerMock = $this->createMock(LoggerInterface::class);
}
```

### 3. Proper Mock Cleanup

```php
protected function tearDown(): void
{
    if (class_exists(\Mockery::class)) {
        \Mockery::close();
    }
    parent::tearDown();
}
```

### 4. Use Real Database for Data Operations

```php
// ✅ Preferred - Use real database with transactions
class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function testCreateUser(): void
    {
        $userData = ['name' => 'John', 'email' => 'john@example.com'];
        
        $user = $this->userService->create($userData);
        
        $this->assertDatabaseHas('users', $userData);
        $this->assertInstanceOf(User::class, $user);
    }
}

// ❌ Avoid - Mocking database operations
DB::shouldReceive('table')->with('users')->andReturn($queryMock);
```

### 5. Mock External Dependencies Only

```php
// ✅ Good - Mock external API service
$apiClientMock = $this->createMock(ExternalApiClientInterface::class);

// ✅ Good - Mock third-party services
$paymentGatewayMock = $this->createMock(PaymentGatewayInterface::class);

// ❌ Avoid - Mocking internal business logic
$userServiceMock = $this->createMock(UserService::class);
```

## Common Anti-Patterns

### 1. Facade Mocking

```php
// ❌ Anti-pattern - Direct facade mocking
DB::shouldReceive('table')->with('users')->andReturn($mock);
Cache::shouldReceive('get')->with('key')->andReturn($value);

// ✅ Better - Use repository pattern with interface
$userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
$cacheServiceMock = $this->createMock(CacheServiceInterface::class);
```

### 2. Over-Mocking Simple Dependencies

```php
// ❌ Over-mocking - Simple value objects
$configMock = $this->createMock(Config::class);
$configMock->method('get')->willReturn('simple-value');

// ✅ Better - Use real config or simple array
$config = ['key' => 'simple-value'];
```

### 3. Mocking Without Verification

```php
// ❌ No verification of method calls
$serviceMock = $this->createMock(ServiceInterface::class);
$serviceMock->method('process')->willReturn(true);

// ✅ Verify interactions
$serviceMock = $this->createMock(ServiceInterface::class);
$serviceMock->expects($this->once())
    ->method('process')
    ->with($expectedData)
    ->willReturn(true);
```

## Implementation Guidelines

### Test Structure Template

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ServiceInterface;
use App\Services\ConcreteService;
use Tests\TestCase;

final class ConcreteServiceTest extends TestCase
{
    private ConcreteService $service;
    private ServiceInterface $dependencyMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Verify dependencies
        $this->assertTrue(
            method_exists(ServiceInterface::class, 'requiredMethod'),
            'ServiceInterface must have requiredMethod'
        );
        
        // Create mocks
        $this->dependencyMock = $this->createMock(ServiceInterface::class);
        
        // Initialize service under test
        $this->service = new ConcreteService($this->dependencyMock);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testMethodWithValidInput(): void
    {
        // Arrange
        $input = ['valid' => 'data'];
        $expectedOutput = ['processed' => 'data'];
        
        $this->dependencyMock->expects($this->once())
            ->method('requiredMethod')
            ->with($input)
            ->willReturn($expectedOutput);

        // Act
        $result = $this->service->processData($input);

        // Assert
        $this->assertEquals($expectedOutput, $result);
    }
}
```

### Feature Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateUserEndpoint(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secure-password'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }
}
```

## Test Isolation Strategy

### 1. Database Isolation

```php
// Use RefreshDatabase for feature tests
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;
    
    // Tests automatically run in transactions
}
```

### 2. Static State Management

```php
// Reset static state in tearDown
protected function tearDown(): void
{
    // Reset singletons
    app()->forgetInstances();
    
    // Clear static caches
    StaticCache::clear();
    
    parent::tearDown();
}
```

### 3. Global State Protection

```php
// Use PreserveGlobalState attribute when necessary
use PHPUnit\Framework\Attributes\PreserveGlobalState;

#[PreserveGlobalState(false)]
public function testWithGlobalStateIsolation(): void
{
    // Test implementation
}
```

## Mock Validation

### 1. Interface Compliance

```php
public function testMockImplementsInterface(): void
{
    $mock = $this->createMock(ServiceInterface::class);
    
    $this->assertInstanceOf(ServiceInterface::class, $mock);
    $this->assertTrue(method_exists($mock, 'requiredMethod'));
}
```

### 2. Method Signature Validation

```php
protected function validateMockSignature(string $interface, string $method, array $parameters): void
{
    $reflection = new \ReflectionClass($interface);
    $this->assertTrue($reflection->hasMethod($method));
    
    $methodReflection = $reflection->getMethod($method);
    $this->assertCount(count($parameters), $methodReflection->getParameters());
}
```

## Examples

### Good Mock Usage

```php
// Testing service with external dependency
public function testUserNotificationService(): void
{
    // Mock external email service
    $emailServiceMock = $this->createMock(EmailServiceInterface::class);
    $emailServiceMock->expects($this->once())
        ->method('send')
        ->with(
            $this->equalTo('user@example.com'),
            $this->stringContains('Welcome')
        )
        ->willReturn(true);

    // Use real user from database
    $user = User::factory()->create(['email' => 'user@example.com']);
    
    $notificationService = new UserNotificationService($emailServiceMock);
    $result = $notificationService->sendWelcomeEmail($user);
    
    $this->assertTrue($result);
}
```

### Bad Mock Usage

```php
// ❌ Don't do this - Over-mocking internal logic
public function testUserCreationBadExample(): void
{
    $userMock = $this->createMock(User::class);
    $userMock->method('save')->willReturn(true);
    $userMock->method('getId')->willReturn(1);
    
    // This doesn't test real behavior
    $this->assertTrue($userMock->save());
}
```

## Migration Guide

### Phase 1: Immediate Fixes (Week 1-2)

1. Add missing `Mockery::close()` calls
2. Fix direct facade mocking
3. Add method existence checks

### Phase 2: Standardization (Week 3-4)

1. Convert Mockery mocks to PHPUnit createMock where appropriate
2. Implement proper tearDown methods
3. Add interface compliance tests

### Phase 3: Optimization (Week 5-6)

1. Identify and fix over-mocking
2. Improve test isolation
3. Add mock validation utilities

## Tools and Utilities

### Mock Validation Helper

```php
trait MockValidationTrait
{
    protected function validateMockInterface(object $mock, string $interface): void
    {
        $this->assertInstanceOf($interface, $mock);
        
        $reflection = new \ReflectionClass($interface);
        foreach ($reflection->getMethods() as $method) {
            $this->assertTrue(
                method_exists($mock, $method->getName()),
                "Mock must implement {$method->getName()} method"
            );
        }
    }
}
```

### Test Isolation Checker

```php
class TestIsolationChecker
{
    public static function checkStaticState(): array
    {
        $issues = [];
        
        // Check for static variables
        if (!empty(self::getStaticVariables())) {
            $issues[] = 'Static variables detected';
        }
        
        // Check for singletons
        if (!empty(self::getSingletonInstances())) {
            $issues[] = 'Singleton instances detected';
        }
        
        return $issues;
    }
}
```

## Conclusion

This mocking strategy provides a foundation for consistent, maintainable, and reliable tests in the COPRRA project. By following these guidelines, we ensure:

- **Consistency**: Standardized approach across all tests
- **Maintainability**: Easy to update and modify tests
- **Reliability**: Tests that accurately reflect real behavior
- **Performance**: Efficient test execution
- **Isolation**: Independent tests that don't affect each other

Regular reviews and updates of this strategy will ensure it remains effective as the project evolves.

---

**Last Updated**: January 2025  
**Version**: 1.0  
**Next Review**: March 2025