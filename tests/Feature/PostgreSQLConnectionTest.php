<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PostgreSQLConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itHandlesPgsqlConnectionBasedOnEnvironment(): void
    {
        $default = (string) config('database.default');

        if ('pgsql' === $default || 'postgres' === $default || 'postgresql' === $default) {
            // Should connect successfully when PostgreSQL is the default driver
            $pdo = DB::connection()->getPdo();
            self::assertNotNull($pdo);
        } else {
            // In testing we expect sqlite memory; any attempt to use PostgreSQL should fail
            $this->expectException(\Throwable::class);
            DB::connection('pgsql')->getPdo();
        }
    }
}
