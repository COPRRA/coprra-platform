<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Currency;
use Tests\TestCase;

/**
 * Simple test to verify database setup without RefreshDatabase conflicts.
 *
 * @internal
 *
 * @coversNothing
 */
final class DatabaseTestSimple extends TestCase
{
    public function testCurrencyTableExists(): void
    {
        // Test that we can create a currency
        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimal_places' => 2,
        ]);

        self::assertNotNull($currency->id);
        self::assertSame('USD', $currency->code);
        self::assertSame('US Dollar', $currency->name);
        self::assertSame('$', $currency->symbol);
        self::assertSame(1.0, $currency->exchange_rate);
        self::assertSame(2, $currency->decimal_places);
    }

    public function testCurrencyCanBeRetrieved(): void
    {
        // Create a currency
        Currency::create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.85,
            'decimal_places' => 2,
        ]);

        // Retrieve it
        $currency = Currency::where('code', 'EUR')->first();

        self::assertNotNull($currency);
        self::assertSame('EUR', $currency->code);
        self::assertSame('Euro', $currency->name);
    }
}
