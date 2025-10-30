<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\COPRRA\PriceHelper;
use App\Models\Currency;
use Tests\SafeTestBase;

/**
 * Safe version of PriceHelperTest using SafeTestBase to avoid RefreshDatabase conflicts.
 */
final class PriceHelperTestSafe extends SafeTestBase
{
    private PriceHelper $priceHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priceHelper = new PriceHelper();

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
        $this->assertEquals('$10.99', $this->priceHelper->formatPrice(10.99, 'USD'));
        $this->assertEquals('€8.50', $this->priceHelper->formatPrice(8.50, 'EUR'));
        $this->assertEquals('£7.30', $this->priceHelper->formatPrice(7.30, 'GBP'));
        $this->assertEquals('¥1100', $this->priceHelper->formatPrice(1100, 'JPY'));
    }

    public function testFormatPriceWithInvalidCurrency(): void
    {
        $this->assertEquals('$10.99', $this->priceHelper->formatPrice(10.99, 'INVALID'));
    }

    public function testConvertPrice(): void
    {
        // USD to EUR: 10 * 0.85 = 8.5
        $this->assertEquals(8.5, $this->priceHelper->convertPrice(10.0, 'USD', 'EUR'));

        // EUR to USD: 8.5 / 0.85 = 10
        $this->assertEquals(10.0, $this->priceHelper->convertPrice(8.5, 'EUR', 'USD'));

        // USD to GBP: 10 * 0.73 = 7.3
        $this->assertEquals(7.3, $this->priceHelper->convertPrice(10.0, 'USD', 'GBP'));
    }

    public function testConvertPriceWithSameCurrency(): void
    {
        $this->assertEquals(10.0, $this->priceHelper->convertPrice(10.0, 'USD', 'USD'));
    }

    public function testConvertPriceWithInvalidCurrency(): void
    {
        $this->assertEquals(10.0, $this->priceHelper->convertPrice(10.0, 'INVALID', 'USD'));
        $this->assertEquals(10.0, $this->priceHelper->convertPrice(10.0, 'USD', 'INVALID'));
    }

    public function testGetCurrencySymbol(): void
    {
        $this->assertEquals('$', $this->priceHelper->getCurrencySymbol('USD'));
        $this->assertEquals('€', $this->priceHelper->getCurrencySymbol('EUR'));
        $this->assertEquals('£', $this->priceHelper->getCurrencySymbol('GBP'));
        $this->assertEquals('¥', $this->priceHelper->getCurrencySymbol('JPY'));
    }

    public function testGetCurrencySymbolWithInvalidCurrency(): void
    {
        $this->assertEquals('$', $this->priceHelper->getCurrencySymbol('INVALID'));
    }

    public function testGetDecimalPlaces(): void
    {
        $this->assertEquals(2, $this->priceHelper->getDecimalPlaces('USD'));
        $this->assertEquals(2, $this->priceHelper->getDecimalPlaces('EUR'));
        $this->assertEquals(2, $this->priceHelper->getDecimalPlaces('GBP'));
        $this->assertEquals(0, $this->priceHelper->getDecimalPlaces('JPY'));
    }

    public function testGetDecimalPlacesWithInvalidCurrency(): void
    {
        $this->assertEquals(2, $this->priceHelper->getDecimalPlaces('INVALID'));
    }

    public function testFormatPriceWithCustomDecimalPlaces(): void
    {
        $this->assertEquals('$10.99', $this->priceHelper->formatPrice(10.99, 'USD', 2));
        $this->assertEquals('$11', $this->priceHelper->formatPrice(10.99, 'USD', 0));
        $this->assertEquals('$10.990', $this->priceHelper->formatPrice(10.99, 'USD', 3));
    }

    public function testCalculateDiscount(): void
    {
        $this->assertEquals(9.0, $this->priceHelper->calculateDiscount(10.0, 10));
        $this->assertEquals(7.5, $this->priceHelper->calculateDiscount(10.0, 25));
        $this->assertEquals(5.0, $this->priceHelper->calculateDiscount(10.0, 50));
        $this->assertEquals(0.0, $this->priceHelper->calculateDiscount(10.0, 100));
    }

    public function testCalculateDiscountWithInvalidPercentage(): void
    {
        $this->assertEquals(10.0, $this->priceHelper->calculateDiscount(10.0, -10));
        $this->assertEquals(0.0, $this->priceHelper->calculateDiscount(10.0, 110));
    }

    public function testCalculateTax(): void
    {
        $this->assertEquals(11.0, $this->priceHelper->calculateTax(10.0, 10));
        $this->assertEquals(12.5, $this->priceHelper->calculateTax(10.0, 25));
        $this->assertEquals(15.0, $this->priceHelper->calculateTax(10.0, 50));
    }

    public function testCalculateTaxWithInvalidPercentage(): void
    {
        $this->assertEquals(10.0, $this->priceHelper->calculateTax(10.0, -10));
    }

    public function testFormatPriceRange(): void
    {
        $this->assertEquals('$10.00 - $20.00', $this->priceHelper->formatPriceRange(10.0, 20.0, 'USD'));
        $this->assertEquals('€8.50 - €17.00', $this->priceHelper->formatPriceRange(8.5, 17.0, 'EUR'));
    }

    public function testFormatPriceRangeWithSamePrice(): void
    {
        $this->assertEquals('$10.00', $this->priceHelper->formatPriceRange(10.0, 10.0, 'USD'));
    }

    public function testFormatPriceRangeWithInvalidRange(): void
    {
        $this->assertEquals('$10.00 - $20.00', $this->priceHelper->formatPriceRange(20.0, 10.0, 'USD'));
    }

    public function testIsValidCurrency(): void
    {
        $this->assertTrue($this->priceHelper->isValidCurrency('USD'));
        $this->assertTrue($this->priceHelper->isValidCurrency('EUR'));
        $this->assertTrue($this->priceHelper->isValidCurrency('GBP'));
        $this->assertTrue($this->priceHelper->isValidCurrency('JPY'));
        $this->assertFalse($this->priceHelper->isValidCurrency('INVALID'));
    }

    public function testGetAvailableCurrencies(): void
    {
        $currencies = $this->priceHelper->getAvailableCurrencies();

        $this->assertIsArray($currencies);
        $this->assertCount(4, $currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('GBP', $currencies);
        $this->assertContains('JPY', $currencies);
    }

    public function testGetExchangeRate(): void
    {
        $this->assertEquals(1.0, $this->priceHelper->getExchangeRate('USD'));
        $this->assertEquals(0.85, $this->priceHelper->getExchangeRate('EUR'));
        $this->assertEquals(0.73, $this->priceHelper->getExchangeRate('GBP'));
        $this->assertEquals(110.0, $this->priceHelper->getExchangeRate('JPY'));
    }

    public function testGetExchangeRateWithInvalidCurrency(): void
    {
        $this->assertEquals(1.0, $this->priceHelper->getExchangeRate('INVALID'));
    }
}
