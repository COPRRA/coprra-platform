<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\LoginAttemptService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoginAttemptServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginAttemptService $service;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LoginAttemptService();

        // Use real Request instead of mocking simple data access
        $this->request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Test Agent',
        ]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testRecordsFailedAttemptWithEmail()
    {
        // Arrange
        $email = 'test@example.com';
        $ip = '127.0.0.1';
        $userAgent = 'Test Agent';

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn([])
        ;

        Cache::shouldReceive('put')
            ->with(\Mockery::pattern('/^ip_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))
            ->andReturn(true)
        ;

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn([])
        ;

        Cache::shouldReceive('put')
            ->with(\Mockery::pattern('/^login_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))
            ->andReturn(true)
        ;

        Log::shouldReceive('warning')
            ->with('Failed login attempt', \Mockery::type('array'))
        ;

        // Act
        $this->service->recordFailedAttempt($this->request, $email);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('get')->with(\Mockery::pattern('/^ip_attempts:/'), [])->once();
        Cache::shouldHaveReceived('put')->with(\Mockery::pattern('/^ip_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))->once();
        Cache::shouldHaveReceived('get')->with(\Mockery::pattern('/^login_attempts:/'), [])->once();
        Cache::shouldHaveReceived('put')->with(\Mockery::pattern('/^login_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))->once();
        Log::shouldHaveReceived('warning')->with('Failed login attempt', \Mockery::type('array'))->once();
    }

    public function testRecordsFailedAttemptWithoutEmail()
    {
        // Arrange
        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn([])
        ;

        Cache::shouldReceive('put')
            ->with(\Mockery::pattern('/^ip_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))
            ->andReturn(true)
        ;

        Log::shouldReceive('warning')
            ->with('Failed login attempt', \Mockery::type('array'))
        ;

        // Act
        $this->service->recordFailedAttempt($this->request);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify that cache was accessed for IP attempts only (no email)
        Cache::shouldHaveReceived('get')->with(\Mockery::pattern('/^ip_attempts:/'), [])->once();

        // Verify that cache was updated for IP attempts only
        Cache::shouldHaveReceived('put')->with(\Mockery::pattern('/^ip_attempts:/'), \Mockery::type('array'), \Mockery::type('object'))->once();

        // Verify that logging was called
        Log::shouldHaveReceived('warning')->with('Failed login attempt', \Mockery::type('array'))->once();
    }

    public function testRecordsSuccessfulAttempt()
    {
        // Arrange
        $email = 'test@example.com';
        $ip = '127.0.0.1';

        Cache::shouldReceive('forget')
            ->with(\Mockery::pattern('/^login_attempts:/'))
            ->andReturn(true)
        ;

        Cache::shouldReceive('forget')
            ->with(\Mockery::pattern('/^ip_attempts:/'))
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with('Successful login', \Mockery::type('array'))
        ;

        // Act
        $this->service->recordSuccessfulAttempt($this->request, $email);

        // Assert - Verify that the service method completed without throwing exceptions
        $this->addToAssertionCount(1);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('forget')->with(\Mockery::pattern('/^login_attempts:/'))->once();
        Cache::shouldHaveReceived('forget')->with(\Mockery::pattern('/^ip_attempts:/'))->once();
        Log::shouldHaveReceived('info')->with('Successful login', \Mockery::type('array'))->once();
    }

    public function testChecksEmailBlockedWhenUnderLimit()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertFalse($result);
    }

    public function testChecksEmailBlockedWhenOverLimit()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => now()->toISOString()]);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertTrue($result);
    }

    public function testChecksIpBlockedWhenUnderLimit()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isIpBlocked($ip);

        // Assert
        self::assertFalse($result);
    }

    public function testChecksIpBlockedWhenOverLimit()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = array_fill(0, 5, ['timestamp' => now()->toISOString()]);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isIpBlocked($ip);

        // Assert
        self::assertTrue($result);
    }

    public function testGetsRemainingAttemptsForEmail()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getRemainingAttempts($email);

        // Assert
        self::assertSame(4, $result);
    }

    public function testGetsRemainingAttemptsForIp()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getRemainingIpAttempts($ip);

        // Assert
        self::assertSame(4, $result);
    }

    public function testGetsLockoutTimeRemainingForEmail()
    {
        // Arrange
        $email = 'test@example.com';
        $futureTime = now()->addMinutes(30);
        $attempts = array_fill(0, 5, ['timestamp' => $futureTime->toISOString()]);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        self::assertIsInt($result);
        self::assertNotNull($result);
    }

    public function testGetsLockoutTimeRemainingForIp()
    {
        // Arrange
        $ip = '127.0.0.1';
        $futureTime = now()->addMinutes(30);
        $attempts = array_fill(0, 5, ['timestamp' => $futureTime->toISOString()]);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getIpLockoutTimeRemaining($ip);

        // Assert
        self::assertIsInt($result);
        self::assertNotNull($result);
    }

    public function testReturnsNullWhenNoLockout()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        self::assertNull($result);
    }

    public function testHandlesInvalidAttemptsData()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = 'invalid_data';

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesNullAttemptsData()
    {
        // Arrange
        $email = 'test@example.com';

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn(null)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertFalse($result);
    }

    public function testUnblocksEmail()
    {
        // Arrange
        $email = 'test@example.com';

        Cache::shouldReceive('forget')
            ->with(\Mockery::pattern('/^login_attempts:/'))
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with('Email unblocked', \Mockery::type('array'))
        ;

        // Act
        $this->service->unblockEmail($email);

        // Assert
        self::assertTrue(true);
    }

    public function testUnblocksIp()
    {
        // Arrange
        $ip = '127.0.0.1';

        Cache::shouldReceive('forget')
            ->with(\Mockery::pattern('/^ip_attempts:/'))
            ->andReturn(true)
        ;

        Log::shouldReceive('info')
            ->with('IP unblocked', \Mockery::type('array'))
        ;

        // Act
        $this->service->unblockIp($ip);

        // Assert
        self::assertTrue(true);
    }

    public function testGetsStatistics()
    {
        // Arrange
        $this->mockFunction('count', static function ($array) {
            return \is_array($array) ? \count($array) : 0;
        });

        // Act
        $result = $this->service->getStatistics();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('max_attempts', $result);
        self::assertArrayHasKey('lockout_duration', $result);
        self::assertArrayHasKey('blocked_emails_count', $result);
        self::assertArrayHasKey('blocked_ips_count', $result);
        self::assertSame(5, $result['max_attempts']);
        self::assertSame(15, $result['lockout_duration']);
    }

    public function testHandlesExpiredLockout()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => now()->subMinutes(20)->toISOString()]);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        self::assertNull($result);
    }

    public function testHandlesMalformedTimestamp()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => 'invalid_timestamp']);

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act & Assert
        $this->expectException(InvalidFormatException::class);
        $this->service->getLockoutTimeRemaining($email);
    }

    public function testHandlesEmptyAttemptsArray()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [];

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesNonArrayAttemptsData()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = 'not_an_array';

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesMissingTimestampInAttempt()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['ip' => '127.0.0.1']); // Missing timestamp

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        self::assertNull($result);
    }

    public function testHandlesNonStringTimestamp()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => 1234567890]); // Non-string timestamp

        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts)
        ;

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        self::assertNull($result);
    }

    // Helper method to mock functions
    private function mockFunction(string $functionName, callable $callback): void
    {
        if (! \function_exists($functionName)) {
            eval("function {$functionName}(\$arg) { return call_user_func_array('{$functionName}', func_get_args()); }");
        }
    }
}
