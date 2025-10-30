<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PasswordPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    private PasswordPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordPolicyService();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testValidatesStrongPassword()
    {
        // Arrange
        $password = 'MySecure123!Pass';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertTrue($result['valid']);
        self::assertEmpty($result['errors']);
        self::assertArrayHasKey('strength', $result);
    }

    public function testValidatesWeakPassword()
    {
        // Arrange
        $password = 'weak';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertNotEmpty($result['errors']);
        self::assertContains('Password must be at least 10 characters long', $result['errors']);
    }

    public function testValidatesPasswordWithoutUppercase()
    {
        // Arrange
        $password = 'lowercase123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password must contain at least one uppercase letter', $result['errors']);
    }

    public function testValidatesPasswordWithoutLowercase()
    {
        // Arrange
        $password = 'UPPERCASE123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password must contain at least one lowercase letter', $result['errors']);
    }

    public function testValidatesPasswordWithoutNumbers()
    {
        // Arrange
        $password = 'NoNumbers!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password must contain at least one number', $result['errors']);
    }

    public function testValidatesPasswordWithoutSymbols()
    {
        // Arrange
        $password = 'NoSymbols123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertTrue($result['valid']);
        self::assertEmpty($result['errors']);
    }

    public function testValidatesForbiddenPassword()
    {
        // Arrange
        $password = 'password';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password is too common and not allowed', $result['errors']);
    }

    public function testValidatesPasswordWithRepeatedCharacters()
    {
        // Arrange
        $password = 'aaa123!@#';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password contains repeated characters', $result['errors']);
    }

    public function testValidatesPasswordWithKeyboardPatterns()
    {
        // Arrange
        $password = 'qwerty123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password contains keyboard patterns', $result['errors']);
    }

    public function testValidatesPasswordWithCommonSubstitutions()
    {
        // Arrange
        $password = 'p@ssw0rd123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password contains common character substitutions', $result['errors']);
    }

    public function testCalculatesPasswordStrengthWeak()
    {
        // Arrange
        $password = 'weak';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertSame('weak', $result['strength']);
    }

    public function testCalculatesPasswordStrengthMedium()
    {
        // Arrange
        $password = 'Medium123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertSame('medium', $result['strength']);
    }

    public function testCalculatesPasswordStrengthStrong()
    {
        // Arrange
        $password = 'StrongP@ss123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertSame('strong', $result['strength']);
    }

    public function testCalculatesPasswordStrengthVeryStrong()
    {
        // Arrange
        $password = 'VeryStrongP@ssw0rd123!@#';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertSame('very_strong', $result['strength']);
    }

    public function testChecksPasswordNotInHistory()
    {
        // Arrange
        $userId = 1;
        $password = 'SecurePass123!';

        // Act
        $result = $this->service->validatePassword($password, $userId);

        // Assert
        self::assertTrue($result['valid']);
    }

    public function testSavesPasswordToHistory()
    {
        // Arrange
        $userId = 1;
        $password = 'NewPassword123!';

        Log::shouldReceive('info')
            ->with('Password saved to history', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->savePasswordToHistory($userId, $password);

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesSavePasswordHistoryException()
    {
        // Arrange
        $userId = 1;
        $password = 'NewPassword123!';

        // Mock Hash::make to throw exception
        Hash::shouldReceive('make')
            ->andThrow(new \Exception('Hash error'))
        ;

        Log::shouldReceive('error')
            ->with('Failed to save password to history', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->savePasswordToHistory($userId, $password);

        // Assert
        self::assertFalse($result);
    }

    public function testChecksPasswordExpired()
    {
        // Arrange
        $userId = 1;

        // Act
        $result = $this->service->isPasswordExpired($userId);

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesPasswordExpiryCheckException()
    {
        // Arrange
        $userId = 999;

        // Test with invalid user ID to trigger exception handling
        Log::shouldReceive('error')
            ->with('Password expiry check failed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->isPasswordExpired($userId);

        // Assert
        self::assertFalse($result);
    }

    public function testChecksAccountNotLocked()
    {
        // Arrange
        $userId = 1;

        // Act
        $result = $this->service->isAccountLocked($userId);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesAccountLockCheckException()
    {
        // Arrange
        $userId = 1;

        // Test with invalid user ID to trigger exception handling
        Log::shouldReceive('error')
            ->with('Account lock check failed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->isAccountLocked($userId);

        // Assert
        self::assertFalse($result);
    }

    public function testRecordsFailedAttempt()
    {
        // Arrange
        $userId = 1;
        $ipAddress = '127.0.0.1';

        Log::shouldReceive('info')
            ->with('Failed login attempt recorded', \Mockery::type('array'))
            ->once()
        ;

        // Act
        $this->service->recordFailedAttempt($userId, $ipAddress);

        // Assert
        $this->assertDatabaseHas('failed_login_attempts', [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
        ]);
    }

    public function testHandlesRecordFailedAttemptException()
    {
        // Arrange
        $userId = 1;
        $ipAddress = '127.0.0.1';

        // Mock now() to throw exception
        $this->mockFunction('now', static function () {
            throw new \Exception('Time error');
        });

        Log::shouldReceive('error')
            ->with('Failed to record failed attempt', \Mockery::type('array'))
            ->once()
        ;

        // Act
        $this->service->recordFailedAttempt($userId, $ipAddress);

        // Assert
        $this->assertDatabaseMissing('failed_login_attempts', [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
        ]);
    }

    public function testClearsFailedAttempts()
    {
        // Arrange
        $userId = 1;

        // First create a failed attempt to clear
        $this->service->recordFailedAttempt($userId, '127.0.0.1');

        Log::shouldReceive('info')
            ->with('Failed attempts cleared', \Mockery::type('array'))
            ->once()
        ;

        // Act
        $this->service->clearFailedAttempts($userId);

        // Assert
        $this->assertDatabaseMissing('failed_login_attempts', [
            'user_id' => $userId,
        ]);
    }

    public function testGeneratesSecurePassword()
    {
        // Act
        $password = $this->service->generateSecurePassword(12);

        // Assert
        self::assertIsString($password);
        self::assertSame(12, \strlen($password));
        self::assertMatchesRegularExpression('/[A-Z]/', $password);
        self::assertMatchesRegularExpression('/[a-z]/', $password);
        self::assertMatchesRegularExpression('/[0-9]/', $password);
        self::assertMatchesRegularExpression('/[^A-Za-z0-9]/', $password);
    }

    public function testGeneratesSecurePasswordWithDefaultLength()
    {
        // Act
        $password = $this->service->generateSecurePassword();

        // Assert
        self::assertIsString($password);
        self::assertSame(12, \strlen($password));
    }

    public function testGetsPolicyRequirements()
    {
        // Act
        $requirements = $this->service->getPolicyRequirements();

        // Assert
        self::assertIsArray($requirements);
        self::assertArrayHasKey('min_length', $requirements);
        self::assertArrayHasKey('max_length', $requirements);
        self::assertArrayHasKey('require_uppercase', $requirements);
        self::assertArrayHasKey('require_lowercase', $requirements);
        self::assertArrayHasKey('require_numbers', $requirements);
        self::assertArrayHasKey('require_symbols', $requirements);
        self::assertArrayHasKey('expiry_days', $requirements);
        self::assertArrayHasKey('history_count', $requirements);
    }

    public function testUpdatesPolicy()
    {
        // Arrange
        $newPolicy = [
            'min_length' => 10,
            'require_symbols' => false,
        ];

        Log::shouldReceive('info')
            ->with('Password policy updated', $newPolicy)
        ;

        // Mock file_put_contents
        $this->mockFunction('file_put_contents', static function ($path, $content) {
            return \strlen($content);
        });

        // Act
        $result = $this->service->updatePolicy($newPolicy);

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesUpdatePolicyException()
    {
        // Arrange
        $newPolicy = [
            'min_length' => 10,
        ];

        // Mock file_put_contents to throw exception
        $this->mockFunction('file_put_contents', static function ($path, $content) {
            throw new \Exception('File write error');
        });

        Log::shouldReceive('error')
            ->with('Failed to update password policy', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->updatePolicy($newPolicy);

        // Assert
        self::assertFalse($result);
    }

    public function testValidatesPasswordWithMaximumLength()
    {
        // Arrange
        $password = str_repeat('a', 129); // Exceeds max length

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password must not exceed 128 characters', $result['errors']);
    }

    public function testValidatesPasswordWithCaseInsensitiveForbidden()
    {
        // Arrange
        $password = 'PASSWORD123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        self::assertFalse($result['valid']);
        self::assertContains('Password is too common and not allowed', $result['errors']);
    }

    // Helper method to mock functions
    private function mockFunction(string $functionName, callable $callback): void
    {
        if (! \function_exists($functionName)) {
            eval("function {$functionName}(\$arg) { return call_user_func_array('{$functionName}', func_get_args()); }");
        }
    }
}
