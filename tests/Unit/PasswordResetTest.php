<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function testPasswordCanBeReset(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true,
        ]);

        $oldPassword = $user->password;

        // Update password directly (simulating password reset)
        $user->update([
            'password' => Hash::make('newpassword123'),
        ]);

        $user->refresh();

        // Verify password was changed
        self::assertNotSame($oldPassword, $user->password);
        self::assertTrue(Hash::check('newpassword123', $user->password));
        self::assertFalse(Hash::check('oldpassword', $user->password));
    }

    public function testPasswordResetTokenCanBeGenerated(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Generate password reset token
        $token = Password::createToken($user);

        // Verify token is a string and not empty
        self::assertIsString($token);
        self::assertNotEmpty($token);
        self::assertGreaterThan(10, \strlen($token)); // Token should be reasonably long
    }

    public function testPasswordResetWithValidToken(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true,
        ]);

        // Generate password reset token
        $token = Password::createToken($user);

        // Simulate password reset
        $credentials = [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ];

        $status = Password::reset($credentials, static function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        // Verify password reset was successful
        self::assertSame(Password::PASSWORD_RESET, $status);

        $user->refresh();
        self::assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function testPasswordResetWithInvalidToken(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true,
        ]);

        // Use invalid token
        $credentials = [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'invalid-token',
        ];

        $status = Password::reset($credentials, static function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        // Verify password reset failed
        self::assertSame(Password::INVALID_TOKEN, $status);

        $user->refresh();
        self::assertTrue(Hash::check('oldpassword', $user->password));
    }

    public function testPasswordResetWithNonExistentUser(): void
    {
        // Use non-existent email
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'some-token',
        ];

        $status = Password::reset($credentials, static function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        // Verify password reset failed
        self::assertSame(Password::INVALID_USER, $status);
    }

    public function testPasswordHashingIsSecure(): void
    {
        $password = 'testpassword123';
        $hashedPassword = Hash::make($password);

        // Verify password is hashed (not plain text)
        self::assertNotSame($password, $hashedPassword);

        // Verify hash can be verified
        self::assertTrue(Hash::check($password, $hashedPassword));

        // Verify wrong password fails verification
        self::assertFalse(Hash::check('wrongpassword', $hashedPassword));

        // Verify hash is reasonably long (bcrypt produces 60 character hashes)
        self::assertGreaterThan(50, \strlen($hashedPassword));
    }

    public function testPasswordUpdateTimestamp(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true,
        ]);

        $originalUpdatedAt = $user->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Update password
        $user->update([
            'password' => Hash::make('newpassword123'),
        ]);

        $user->refresh();

        // Verify updated_at timestamp changed
        self::assertNotSame($originalUpdatedAt, $user->updated_at);
        self::assertTrue($user->updated_at->greaterThan($originalUpdatedAt));
    }
}
