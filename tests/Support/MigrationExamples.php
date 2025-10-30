<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Services\CacheService;
use App\Services\ProductService;
use App\Services\UserService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * Migration Examples: From Facade Mocking to Dependency Injection.
 *
 * This class provides practical examples of how to refactor tests
 * from problematic facade mocking to proper dependency injection.
 *
 * @internal
 *
 * @coversNothing
 */
final class MigrationExamples extends TestCase
{
    /**
     * BEFORE: Problematic facade mocking
     * Issues: Tight coupling, hard to test, brittle.
     */
    public function exampleBeforeFacadeMocking()
    {
        // ❌ BAD: Direct facade mocking
        /*
        Cache::shouldReceive('get')
            ->with('user.123')
            ->andReturn(['name' => 'John']);

        Cache::shouldReceive('put')
            ->with('user.123', Mockery::any(), 3600)
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('User cached', Mockery::any());
        */
    }

    /**
     * AFTER: Proper dependency injection with interface mocking
     * Benefits: Loose coupling, testable, maintainable.
     */
    public function exampleAfterDependencyInjection()
    {
        // ✅ GOOD: Mock interfaces, inject dependencies
        $cacheMock = \Mockery::mock(CacheRepository::class);
        $loggerMock = \Mockery::mock(LoggerInterface::class);

        // Verify method existence for reliability
        self::assertTrue(
            method_exists(CacheRepository::class, 'get'),
            'CacheRepository must have get method'
        );

        $cacheMock->expects(self::once())
            ->method('get')
            ->with('user.123')
            ->willReturn(['name' => 'John'])
        ;

        $cacheMock->expects(self::once())
            ->method('put')
            ->with('user.123', self::anything(), 3600)
            ->willReturn(true)
        ;

        $loggerMock->expects(self::once())
            ->method('info')
            ->with('User cached', self::isType('array'))
        ;

        // Inject mocks into service
        $service = new UserService($cacheMock, $loggerMock);
        $result = $service->cacheUser(['id' => 123, 'name' => 'John']);

        self::assertTrue($result);
    }

    /**
     * BEFORE: Over-mocking simple data structures.
     */
    public function exampleBeforeOverMocking()
    {
        // ❌ BAD: Mocking simple Request for basic data
        /*
        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('get')->with('page', 1)->andReturn(2);
        $requestMock->shouldReceive('ip')->andReturn('127.0.0.1');
        $requestMock->shouldReceive('userAgent')->andReturn('Test Agent');
        */
    }

    /**
     * AFTER: Using real instances for simple data structures.
     */
    public function exampleAfterReducingOverMocking()
    {
        // ✅ GOOD: Use real Request with test data
        $request = Request::create('/test', 'GET', ['page' => 2], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Test Agent',
        ]);

        // Or use Laravel's testing helpers
        $this->withSession(['user_id' => 123]);

        self::assertSame(2, $request->get('page', 1));
        self::assertSame('127.0.0.1', $request->ip());
        self::assertSame('Test Agent', $request->userAgent());
    }

    /**
     * BEFORE: Complex model mocking that's brittle.
     */
    public function exampleBeforeComplexModelMocking()
    {
        // ❌ BAD: Complex in-memory model mocking
        /*
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')->with('id')->andReturn(123);
        $userMock->shouldReceive('getAttribute')->with('email')->andReturn('test@example.com');
        $userMock->shouldReceive('save')->andReturn(true);
        */
    }

    /**
     * AFTER: Using factories and real database interactions.
     */
    public function exampleAfterSimplifiedModelTesting()
    {
        // ✅ GOOD: Use factories for real model instances
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        self::assertSame(123, $user->id);
        self::assertSame('test@example.com', $user->email);

        // Test actual behavior, not mocked behavior
        $user->name = 'Updated Name';
        $result = $user->save();

        self::assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * BEFORE: Under-mocking external dependencies.
     */
    public function exampleBeforeUnderMocking()
    {
        // ❌ BAD: Making real HTTP calls in tests
        /*
        $response = Http::get('https://api.external.com/data');
        $this->assertEquals(200, $response->status());
        */
    }

    /**
     * AFTER: Properly mocking external dependencies.
     */
    public function exampleAfterProperExternalMocking()
    {
        // ✅ GOOD: Mock external HTTP calls
        Http::fake([
            'api.external.com/*' => Http::response([
                'data' => 'test_data',
            ], 200),
        ]);

        $response = Http::get('https://api.external.com/data');

        self::assertSame(200, $response->status());
        self::assertSame('test_data', $response->json('data'));

        // Verify the call was made
        Http::assertSent(static function ($request) {
            return 'https://api.external.com/data' === $request->url();
        });
    }

    /**
     * Example of proper service testing with dependency injection.
     */
    public function exampleProperServiceTesting()
    {
        // Arrange: Create mocks for dependencies
        $cacheMock = \Mockery::mock(CacheRepository::class);
        $loggerMock = \Mockery::mock(LoggerInterface::class);

        // Set up expectations
        $cacheMock->expects(self::once())
            ->method('remember')
            ->with('products.featured', 3600, self::isType('callable'))
            ->willReturn(collect([['id' => 1, 'name' => 'Product 1']]))
        ;

        $loggerMock->expects(self::once())
            ->method('info')
            ->with('Featured products retrieved from cache')
        ;

        // Act: Test the service with injected dependencies
        $service = new ProductService($cacheMock, $loggerMock);
        $result = $service->getFeaturedProducts();

        // Assert: Verify the result
        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(1, $result);
        self::assertSame('Product 1', $result->first()['name']);
    }

    /**
     * Example of testing with proper isolation.
     */
    public function exampleProperTestIsolation()
    {
        // Use the TestIsolationTrait for proper cleanup
        $this->backupGlobalState();

        try {
            // Test logic here
            self::assertTrue(true);
        } finally {
            // Ensure cleanup happens
            $this->restoreGlobalState();
            $this->clearTestCaches();
            \Mockery::close();
        }
    }

    /**
     * Example of validating mock signatures.
     */
    public function exampleMockValidation()
    {
        // Use MockValidationTrait for signature validation
        $cacheMock = \Mockery::mock(CacheRepository::class);

        // Validate that the mock implements the expected interface
        $this->assertImplementsInterface(CacheRepository::class, $cacheMock);

        // Validate specific method signatures
        $this->assertMethodExists(CacheRepository::class, 'get');
        $this->assertMethodSignature(
            CacheRepository::class,
            'get',
            ['string', 'mixed'],
            'mixed'
        );

        // Validate service dependencies
        $this->validateServiceMock(CacheService::class, [
            CacheRepository::class,
            LoggerInterface::class,
        ]);
    }
}
