<?php

declare(strict_types=1);

namespace Tests\Unit\Debug;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseDebugTest extends TestCase
{
    use RefreshDatabase;

    public function testDatabaseConnection()
    {
        // Check if we can connect to the database
        self::assertTrue(null !== DB::connection()->getPdo());
    }

    public function testUsersTableExists()
    {
        // Check if users table exists
        self::assertTrue(Schema::hasTable('users'));
    }

    public function testDatabaseConfiguration()
    {
        // Check database configuration
        $connection = config('database.default');
        self::assertNotNull($connection);

        $dbConfig = config("database.connections.{$connection}");
        self::assertNotNull($dbConfig);

        // Output debug info
        dump([
            'default_connection' => $connection,
            'database_config' => $dbConfig,
            'tables' => DB::select("SELECT name FROM sqlite_master WHERE type='table'"),
        ]);
    }
}
