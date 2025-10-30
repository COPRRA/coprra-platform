<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanBeCreatedWithValidData()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ];

        $user = User::create($userData);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Test User', $user->name);
        self::assertSame('test@example.com', $user->email);
        self::assertTrue(Hash::check('password123', $user->password));
    }

    public function testUserEmailMustBeUnique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(QueryException::class);

        User::create([
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    public function testUserPasswordIsHashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('plaintext-password'),
        ]);

        self::assertNotSame('plaintext-password', $user->password);
        self::assertTrue(Hash::check('plaintext-password', $user->password));
    }

    public function testUserHasFillableAttributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        self::assertContains('name', $fillable);
        self::assertContains('email', $fillable);
        self::assertContains('password', $fillable);
    }

    public function testUserHasHiddenAttributes()
    {
        $user = new User();
        $hidden = $user->getHidden();

        self::assertContains('password', $hidden);
        self::assertContains('remember_token', $hidden);
    }
}
