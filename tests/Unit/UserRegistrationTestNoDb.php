<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserRegistrationTestNoDb extends TestCase
{
    public function testUserModelCanBeInstantiated(): void
    {
        $user = new User();
        self::assertInstanceOf(User::class, $user);
    }

    public function testUserModelHasExpectedAttributes(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => false,
            'is_active' => true,
            'is_blocked' => false,
            'role' => 'user',
        ]);

        self::assertSame('John Doe', $user->name);
        self::assertSame('john@example.com', $user->email);
        self::assertFalse($user->is_admin);
        self::assertTrue($user->is_active);
        self::assertFalse($user->is_blocked);
        self::assertSame('user', $user->role);
    }
}
