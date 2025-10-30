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
final class SQLiteConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itConnectsToSqliteMemoryInTestingEnvironment(): void
    {
        $default = (string) config('database.default');
        self::assertSame('sqlite', $default, 'Ø¨ÙŠØ¦Ø© Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø± ÙŠØ¬Ø¨ Ø£Ù† ØªØ³ØªØ®Ø¯Ù… sqlite');

        // Ensure connection works and can run a basic query
        $result = DB::select('select 1 as one');
        self::assertNotEmpty($result);
        self::assertSame(1, $result[0]->one ?? null);
    }
}
