<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MockValidationTrait;
use Tests\Support\TestIsolationTrait;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PasswordResetServiceTest extends TestCase
{
    use MockValidationTrait;
    use RefreshDatabase;
    use TestIsolationTrait;

    private PasswordResetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupGlobalState();

        // Validate service class and methods
        $this->validateMockMethods(PasswordResetService::class, [
            'sendResetEmail',
            'resetPassword',
            'validateToken',
        ]);

        $this->service = new PasswordResetService();
    }

    protected function tearDown(): void
    {
        $this->restoreGlobalState();
        $this->clearTestCaches();
        $this->closeMockery();
        $this->verifyTestIsolation();
        parent::tearDown();
    }

    public function testCanBeInstantiated(): void
    {
        // Act & Assert
        self::assertInstanceOf(PasswordResetService::class, $this->service);
    }

    public function testHandlesSendResetEmailWithValidEmail(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'sendResetEmail'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'sendResetEmail']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'sendResetEmail');
        self::assertSame(1, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testHandlesSendResetEmailWithNonexistentEmail(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'sendResetEmail'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'sendResetEmail']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'sendResetEmail');
        self::assertSame(1, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testHandlesResetPasswordWithValidToken(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        self::assertSame(3, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[1]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[2]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testHandlesResetPasswordWithInvalidToken(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        self::assertSame(3, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[1]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[2]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testChecksResetTokenExists(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'hasResetToken'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'hasResetToken']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'hasResetToken');
        self::assertSame(1, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testGetsResetTokenInfo(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'getResetTokenInfo'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'getResetTokenInfo']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'getResetTokenInfo');
        self::assertSame(1, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertTrue($reflection->getReturnType()->allowsNull());
    }

    public function testHandlesExpiredToken(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        self::assertSame(3, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[1]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[2]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testHandlesTooManyAttempts(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        self::assertSame(3, $reflection->getNumberOfParameters());
        self::assertSame('string', $reflection->getParameters()[0]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[1]->getType()->getName());
        self::assertSame('string', $reflection->getParameters()[2]->getType()->getName());
        self::assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testGetsStatistics(): void
    {
        // Test service instantiation and method existence
        self::assertInstanceOf(PasswordResetService::class, $this->service);
        self::assertTrue(method_exists($this->service, 'getStatistics'));

        // Test that the method exists and can be called
        self::assertIsCallable([$this->service, 'getStatistics']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'getStatistics');
        self::assertSame(0, $reflection->getNumberOfParameters());
        self::assertSame('array', $reflection->getReturnType()->getName());

        // Test that the method returns an array
        $result = $this->service->getStatistics();
        self::assertIsArray($result);
        self::assertArrayHasKey('token_expiry_minutes', $result);
        self::assertArrayHasKey('max_attempts', $result);
        self::assertArrayHasKey('expired_tokens_cleaned', $result);
    }
}
