<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test basic user creation.
     */
    public function testUserCreation(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Test User', $user->name);
        self::assertSame('test@example.com', $user->email);
    }

    /**
     * Test user can be found in database.
     */
    public function testUserInDatabase(): void
    {
        $user = User::factory()->create([
            'name' => 'Database Test User',
            'email' => 'dbtest@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Database Test User',
            'email' => 'dbtest@example.com',
        ]);
    }

    /**
     * Test user authentication.
     */
    public function testUserAuthentication(): void
    {
        $user = User::factory()->create([
            'email' => 'auth@example.com',
            'password' => bcrypt('password123'),
        ]);

        self::assertTrue(auth()->attempt([
            'email' => 'auth@example.com',
            'password' => 'password123',
        ]));

        self::assertSame($user->id, auth()->id());
    }
}
