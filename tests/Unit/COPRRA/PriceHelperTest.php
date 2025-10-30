<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Helpers\PriceHelper;
use App\Models\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(PriceHelper::class)]
final class PriceHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            'symbol' => 'â‚¬',
            'exchange_rate' => 0.85,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'symbol' => 'Ø±.Ø³',
            'exchange_rate' => 3.75,
            'decimal_places' => 2,
        ]);
    }

    public function testItFormatsPriceWithDefaultCurrency(): void
    {
        $result = PriceHelper::formatPrice(100.50);

        self::assertIsString($result);
        self::assertStringContainsString('100.50', $result);
        self::assertStringContainsString('$', $result);
    }

    public function testItFormatsPriceWithSpecificCurrency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'EUR');

        self::assertIsString($result);
        self::assertStringContainsString('100.50', $result);
        self::assertStringContainsString('â‚¬', $result);
    }

    public function testItFormatsPriceWithSarCurrency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'SAR');

        self::assertIsString($result);
        self::assertStringContainsString('100.50', $result);
        self::assertStringContainsString('Ø±.Ø³', $result);
    }

    public function testItHandlesNonExistentCurrency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'XYZ');

        self::assertIsString($result);
        self::assertStringContainsString('100.50', $result);
        self::assertStringContainsString('XYZ', $result);
    }

    public function testItCalculatesPriceDifferencePercentage(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 120.0);

        self::assertIsFloat($result);
        self::assertSame(20.0, $result);
    }

    public function testItCalculatesNegativePriceDifference(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 80.0);

        self::assertIsFloat($result);
        self::assertSame(-20.0, $result);
    }

    public function testItReturnsZeroForSamePrices(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 100.0);

        self::assertSame(0.0, $result);
    }

    public function testItHandlesZeroOriginalPrice(): void
    {
        $result = PriceHelper::calculatePriceDifference(0.0, 100.0);

        self::assertSame(0.0, $result);
    }

    public function testItHandlesNegativeOriginalPrice(): void
    {
        $result = PriceHelper::calculatePriceDifference(-10.0, 100.0);

        self::assertSame(0.0, $result);
    }

    public function testItFormatsPositivePriceDifferenceString(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 120.0);

        self::assertIsString($result);
        self::assertStringContainsString('+', $result);
        self::assertStringContainsString('20.0', $result);
        self::assertStringContainsString('%', $result);
    }

    public function testItFormatsNegativePriceDifferenceString(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 80.0);

        self::assertIsString($result);
        self::assertStringContainsString('-20.0', $result);
        self::assertStringContainsString('%', $result);
    }

    public function testItFormatsZeroPriceDifferenceString(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 100.0);

        self::assertSame('0%', $result);
    }

    public function testItIdentifiesGoodDeal(): void
    {
        $allPrices = [100.0, 110.0, 120.0, 130.0];
        $result = PriceHelper::isGoodDeal(95.0, $allPrices);

        self::assertTrue($result);
    }

    public function testItIdentifiesNotGoodDeal(): void
    {
        $allPrices = [100.0, 110.0, 120.0, 130.0];
        $result = PriceHelper::isGoodDeal(120.0, $allPrices);

        self::assertFalse($result);
    }

    public function testItHandlesEmptyPricesArrayForGoodDeal(): void
    {
        $result = PriceHelper::isGoodDeal(100.0, []);

        self::assertFalse($result);
    }

    public function testItGetsBestPriceFromArray(): void
    {
        $prices = [100.0, 80.0, 120.0, 90.0];
        $result = PriceHelper::getBestPrice($prices);

        self::assertSame(80.0, $result);
    }

    public function testItReturnsNullForEmptyPricesArray(): void
    {
        $result = PriceHelper::getBestPrice([]);

        self::assertNull($result);
    }

    public function testItConvertsCurrency(): void
    {
        $result = PriceHelper::convertCurrency(100.0, 'USD', 'EUR');

        self::assertIsFloat($result);
        // USD to EUR: 100 / 1.0 * 0.85 = 85.0
        self::assertSame(85.0, $result);
    }

    public function testItConvertsCurrencyToSar(): void
    {
        $result = PriceHelper::convertCurrency(100.0, 'USD', 'SAR');

        self::assertIsFloat($result);
        // USD to SAR: 100 / 1.0 * 3.75 = 375.0
        self::assertSame(375.0, $result);
    }

    public function testItFormatsPriceRangeWithSamePrices(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 100.0, 'USD');

        self::assertIsString($result);
        self::assertStringContainsString('$', $result);
        self::assertStringContainsString('100.00', $result);
        self::assertStringNotContainsString('-', $result);
    }

    public function testItFormatsPriceRangeWithDifferentPrices(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0, 'USD');

        self::assertIsString($result);
        self::assertStringContainsString('$', $result);
        self::assertStringContainsString('100.00', $result);
        self::assertStringContainsString('200.00', $result);
        self::assertStringContainsString('-', $result);
    }

    public function testItFormatsPriceRangeWithDefaultCurrency(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0);

        self::assertIsString($result);
        self::assertStringContainsString('$', $result);
    }

    public function testItFormatsPriceRangeWithNonExistentCurrency(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0, 'XYZ');

        self::assertIsString($result);
        self::assertStringContainsString('XYZ', $result);
        self::assertStringContainsString('100.00', $result);
        self::assertStringContainsString('200.00', $result);
    }

    public function testItHandlesDecimalPricesCorrectly(): void
    {
        $result = PriceHelper::formatPrice(99.99, 'USD');

        self::assertStringContainsString('99.99', $result);
    }

    public function testItHandlesLargePrices(): void
    {
        $result = PriceHelper::formatPrice(9999999.99, 'USD');

        self::assertStringContainsString('9,999,999.99', $result);
    }

    public function testItCalculatesAccuratePercentageForSmallDifferences(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 101.0);

        self::assertSame(1.0, $result);
    }

    public function testItCalculatesAccuratePercentageForLargeDifferences(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 200.0);

        self::assertSame(100.0, $result);
    }
}
