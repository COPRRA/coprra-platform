<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Helpers\PriceHelper;
use App\Models\Currency;
use Tests\TestCase;

/**
 * Fixed version of PriceHelperTest without RefreshDatabase to avoid transaction conflicts.
 *
 * @internal
 *
 * @coversNothing
 */
final class PriceHelperTestFixed extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing currencies first
        try {
            Currency::truncate();
        } catch (\Exception $e) {
            // Table might not exist yet, continue
        }

        // Create test currencies
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.85,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.73,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'exchange_rate' => 110.0,
            'decimal_places' => 0,
        ]);
    }

    public function testFormatPrice(): void
    {
        self::assertSame('$10.99', PriceHelper::formatPrice(10.99, 'USD'));
        self::assertSame('€8.50', PriceHelper::formatPrice(8.50, 'EUR'));
        self::assertSame('£7.30', PriceHelper::formatPrice(7.30, 'GBP'));
        self::assertSame('¥1,100.00', PriceHelper::formatPrice(1100, 'JPY'));
    }

    public function testFormatPriceWithInvalidCurrency(): void
    {
        // Invalid currency should return the currency code as symbol
        self::assertSame('INVALID10.99', PriceHelper::formatPrice(10.99, 'INVALID'));
    }

    public function testConvertCurrency(): void
    {
        // USD to EUR: 10 / 1.0 * 0.85 = 8.5
        self::assertSame(8.5, PriceHelper::convertCurrency(10.0, 'USD', 'EUR'));

        // EUR to USD: 8.5 / 0.85 * 1.0 = 10
        self::assertSame(10.0, PriceHelper::convertCurrency(8.5, 'EUR', 'USD'));

        // USD to GBP: 10 / 1.0 * 0.73 = 7.3
        self::assertSame(7.3, PriceHelper::convertCurrency(10.0, 'USD', 'GBP'));
    }

    public function testConvertCurrencyWithSameCurrency(): void
    {
        self::assertSame(10.0, PriceHelper::convertCurrency(10.0, 'USD', 'USD'));
    }

    public function testCalculatePriceDifference(): void
    {
        // 20% increase: (12 - 10) / 10 * 100 = 20
        self::assertSame(20.0, PriceHelper::calculatePriceDifference(10.0, 12.0));

        // 25% decrease: (7.5 - 10) / 10 * 100 = -25
        self::assertSame(-25.0, PriceHelper::calculatePriceDifference(10.0, 7.5));

        // No change
        self::assertSame(0.0, PriceHelper::calculatePriceDifference(10.0, 10.0));
    }

    public function testCalculatePriceDifferenceWithZeroOriginal(): void
    {
        self::assertSame(0.0, PriceHelper::calculatePriceDifference(0.0, 10.0));
    }

    public function testGetPriceDifferenceString(): void
    {
        self::assertSame('+20.0%', PriceHelper::getPriceDifferenceString(10.0, 12.0));
        self::assertSame('-25.0%', PriceHelper::getPriceDifferenceString(10.0, 7.5));
        self::assertSame('0%', PriceHelper::getPriceDifferenceString(10.0, 10.0));
    }

    public function testIsGoodDeal(): void
    {
        $prices = [10.0, 15.0, 20.0]; // Average: 15.0

        self::assertTrue(PriceHelper::isGoodDeal(12.0, $prices)); // Below average
        self::assertFalse(PriceHelper::isGoodDeal(15.0, $prices)); // Equal to average
        self::assertFalse(PriceHelper::isGoodDeal(18.0, $prices)); // Above average
    }

    public function testIsGoodDealWithEmptyArray(): void
    {
        self::assertFalse(PriceHelper::isGoodDeal(10.0, []));
    }

    public function testGetBestPrice(): void
    {
        $prices = [15.0, 10.0, 20.0, 8.5];
        self::assertSame(8.5, PriceHelper::getBestPrice($prices));
    }

    public function testGetBestPriceWithEmptyArray(): void
    {
        self::assertNull(PriceHelper::getBestPrice([]));
    }

    public function testFormatPriceRange(): void
    {
        self::assertSame('$10.00 - $20.00', PriceHelper::formatPriceRange(10.0, 20.0, 'USD'));
        self::assertSame('€8.50 - €17.00', PriceHelper::formatPriceRange(8.5, 17.0, 'EUR'));
    }

    public function testFormatPriceRangeWithSamePrice(): void
    {
        self::assertSame('$10.00', PriceHelper::formatPriceRange(10.0, 10.0, 'USD'));
    }

    public function testFormatPriceRangeWithStringInputs(): void
    {
        self::assertSame('$10.00 - $20.00', PriceHelper::formatPriceRange('10.0', '20.0', 'USD'));
        self::assertSame('$15.00', PriceHelper::formatPriceRange('15', '15', 'USD'));
    }
}
