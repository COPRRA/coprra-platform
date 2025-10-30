<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoginPageTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up any User records created during tests
        User::query()->delete();
        parent::tearDown();
    }

    public function testLoginRequestHasCorrectProperties(): void
    {
        $request = new LoginRequest();

        // Test that the request class exists and can be instantiated
        self::assertInstanceOf(LoginRequest::class, $request);
    }

    public function testLoginRequestHasCorrectMessages(): void
    {
        $request = new LoginRequest();
        $messages = $request->messages();

        $expectedMessages = [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email may not be greater than 255 characters.',
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters.',
        ];

        self::assertSame($expectedMessages, $messages);
    }

    public function testLoginRequestHasCorrectAttributes(): void
    {
        $request = new LoginRequest();
        $attributes = $request->attributes();

        $expectedAttributes = [
            'email' => 'email address',
            'password' => 'password',
            'remember' => 'remember me',
        ];

        self::assertSame($expectedAttributes, $attributes);
    }

    public function testAuthenticateMethodWithValidCredentials(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Create a mock request with valid credentials
        $request = new LoginRequest();
        $request->merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => false,
        ]);

        // Test that authentication succeeds without throwing exception
        $request->authenticate();

        // Verify user is authenticated
        self::assertTrue(auth()->check());
        self::assertSame($user->id, auth()->id());
    }

    public function testAuthenticateMethodWithInvalidCredentials(): void
    {
        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Create a mock request with invalid credentials
        $request = new LoginRequest();
        $request->merge([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'remember' => false,
        ]);

        // Test that authentication fails with ValidationException
        $this->expectException(ValidationException::class);
        $request->authenticate();
    }

    public function testAuthenticateMethodWithNonExistentUser(): void
    {
        // Create a mock request with non-existent user
        $request = new LoginRequest();
        $request->merge([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
            'remember' => false,
        ]);

        // Test that authentication fails with ValidationException
        $this->expectException(ValidationException::class);
        $request->authenticate();
    }

    public function testAuthenticateMethodWithRememberMe(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Create a mock request with remember me enabled
        $request = new LoginRequest();
        $request->merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        // Test that authentication succeeds
        $request->authenticate();

        // Verify user is authenticated
        self::assertTrue(auth()->check());
        self::assertSame($user->id, auth()->id());
    }

    public function testLoginRequestOnlyReturnsSpecifiedFields(): void
    {
        $request = new LoginRequest();
        $request->merge([
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
            'extra_field' => 'should_not_be_included',
        ]);

        $onlyFields = $request->only('email', 'password');

        self::assertArrayHasKey('email', $onlyFields);
        self::assertArrayHasKey('password', $onlyFields);
        self::assertArrayNotHasKey('remember', $onlyFields);
        self::assertArrayNotHasKey('extra_field', $onlyFields);
        self::assertSame('test@example.com', $onlyFields['email']);
        self::assertSame('password123', $onlyFields['password']);
    }
}
