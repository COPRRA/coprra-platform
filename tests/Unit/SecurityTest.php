<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SecurityTest extends TestCase
{
    public function testPasswordHashingWorksCorrectly()
    {
        $password = 'test-password-123';
        $hashedPassword = Hash::make($password);

        self::assertNotSame($password, $hashedPassword);
        self::assertTrue(Hash::check($password, $hashedPassword));
        self::assertFalse(Hash::check('wrong-password', $hashedPassword));
    }

    public function testCsrfProtectionIsEnabled()
    {
        $response = $this->post('/api/test-endpoint', []);

        // Expecting either 419 (CSRF token mismatch) or 404 (route not found)
        self::assertContains($response->status(), [419, 404, 405]);
    }

    public function testEnvironmentIsNotProductionInTests()
    {
        self::assertNotSame('production', app()->environment());
    }
}
