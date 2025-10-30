<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;

/**
 * @internal
 *
 * @coversNothing
 */
final class SimpleDbTest extends TestCase
{
    public function testDatabaseConnection(): void
    {
        // Test basic database connection
        $this->assertDatabaseCount('users', 0);

        // Try to create a user
        $user = User::factory()->create();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
