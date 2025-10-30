<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\PasswordHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PasswordHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private PasswordHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordHistoryService();
        // Provide a safe default for error logging across tests to prevent Mockery exceptions
        Log::shouldReceive('error')->byDefault()->andReturnNull();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testChecksPasswordNotInHistory()
    {
        // Arrange
        $password = 'new_password';
        $userId = 1;

        Cache::shouldReceive('get')
            ->with("password_history_{$userId}", [])
            ->andReturn([])
        ;

        // Act
        $result = $this->service->isPasswordInHistory($password, $userId);

        // Assert
        self::assertFalse($result);
    }

    public function testChecksPasswordInHistory()
    {
        // Arrange
        $password = 'old_password';
        $userId = 1;
        $hashedPassword = Hash::make($password);

        Cache::shouldReceive('get')
            ->with("password_history_{$userId}", [])
            ->andReturn([$hashedPassword])
        ;

        // Act
        $result = $this->service->isPasswordInHistory($password, $userId);

        // Assert
        self::assertTrue($result);
    }

    public function testSavesPasswordToHistory()
    {
        // Arrange
        $password = 'new_password';
        $userId = 1;

        Cache::shouldReceive('get')
            ->with("password_history_{$userId}", [])
            ->andReturn([])
        ;

        Cache::shouldReceive('put')
            ->with("password_history_{$userId}", \Mockery::type('array'), \Mockery::type('int'))
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with("Password saved to history for user {$userId}")
        ;

        // Act
        $this->service->savePasswordToHistory($password, $userId);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('get')->with("password_history_{$userId}", [])->once();
        Cache::shouldHaveReceived('put')->with("password_history_{$userId}", \Mockery::type('array'), \Mockery::type('int'))->once();
        Log::shouldHaveReceived('info')->with("Password saved to history for user {$userId}")->once();
    }

    public function testClearsPasswordHistory()
    {
        // Arrange
        $userId = 1;

        Cache::shouldReceive('forget')
            ->with("password_history_{$userId}")
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with("Password history cleared for user {$userId}")
        ;

        // Act
        $this->service->clearPasswordHistory($userId);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('forget')->with("password_history_{$userId}")->once();
        Log::shouldHaveReceived('info')->with("Password history cleared for user {$userId}")->once();
    }

    public function testHandlesPasswordHistoryCheckException()
    {
        // Arrange
        $password = 'test_password';
        $userId = 1;

        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Cache error'))
        ;

        Log::shouldReceive('error')
            ->with('Password history check failed: Cache error')
        ;

        // Act
        $result = $this->service->isPasswordInHistory($password, $userId);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesSavePasswordHistoryException()
    {
        // Arrange
        $password = 'test_password';
        $userId = 1;

        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Cache error'))
        ;

        Log::shouldReceive('error')
            ->with('Failed to save password to history: Cache error')
        ;

        // Act
        $this->service->savePasswordToHistory($password, $userId);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify error logging was called
        Log::shouldHaveReceived('error')->with('Failed to save password to history: Cache error')->once();
    }

    public function testLimitsPasswordHistoryCount()
    {
        // Arrange
        $password = 'new_password';
        $userId = 1;
        $existingHistory = array_fill(0, 10, 'existing_hash'); // More than limit

        Cache::shouldReceive('get')
            ->with("password_history_{$userId}", [])
            ->andReturn($existingHistory)
        ;

        Cache::shouldReceive('put')
            ->with("password_history_{$userId}", \Mockery::type('array'), \Mockery::type('int'))
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with("Password saved to history for user {$userId}")
        ;

        // Act
        $this->service->savePasswordToHistory($password, $userId);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('get')->with("password_history_{$userId}", [])->once();
        Cache::shouldHaveReceived('put')->with("password_history_{$userId}", \Mockery::type('array'), \Mockery::type('int'))->once();
        Log::shouldHaveReceived('info')->with("Password saved to history for user {$userId}")->once();
    }
}
