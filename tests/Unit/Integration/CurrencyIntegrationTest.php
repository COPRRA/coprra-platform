<?php

declare(strict_types=1);

namespace Tests\Unit\Integration;

use App\Helpers\PriceHelper;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CurrencyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ExchangeRateService $exchangeRateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchangeRateService = app(ExchangeRateService::class);
    }

    public function testCurrencyModelRelationships(): void
    {
        // Create currencies
        $usd = Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);

        // Test currency creation
        $this->assertDatabaseHas('currencies', ['code' => 'USD']);
        $this->assertDatabaseHas('currencies', ['code' => 'EUR']);

        // Test currency attributes
        self::assertSame('USD', $usd->code);
        self::assertSame('$', $usd->symbol);
        self::assertSame('EUR', $eur->code);
        self::assertSame('€', $eur->symbol);
    }

    public function testExchangeRateServiceConversion(): void
    {
        // Create currencies
        Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1.0]);
        Currency::factory()->create(['code' => 'EUR', 'exchange_rate' => 0.85]);

        // Create exchange rate
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'updated_at' => now(),
        ]);

        // Test currency conversion
        $convertedAmount = $this->exchangeRateService->convert(100.0, 'USD', 'EUR');
        self::assertSame(85.0, $convertedAmount);

        // Test reverse conversion
        ExchangeRate::create([
            'from_currency' => 'EUR',
            'to_currency' => 'USD',
            'rate' => 1.18,
            'updated_at' => now(),
        ]);

        $reverseAmount = $this->exchangeRateService->convert(85.0, 'EUR', 'USD');
        self::assertSame(100.3, $reverseAmount);
    }

    public function testPriceHelperCurrencyFormatting(): void
    {
        // Create currencies
        Currency::factory()->create(['code' => 'USD', 'symbol' => '$']);
        Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);

        // Test price formatting with different currencies
        $formattedUsd = PriceHelper::formatPrice(99.99, 'USD');
        self::assertSame('$99.99', $formattedUsd);

        $formattedEur = PriceHelper::formatPrice(85.50, 'EUR');
        self::assertSame('€85.50', $formattedEur);

        // Test default currency formatting
        config(['coprra.default_currency' => 'USD']);
        $defaultFormatted = PriceHelper::formatPrice(123.45);
        self::assertSame('$123.45', $defaultFormatted);
    }

    public function testPriceHelperCurrencyConversion(): void
    {
        // Create currencies with exchange rates
        Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1.0]);
        Currency::factory()->create(['code' => 'EUR', 'exchange_rate' => 0.85]);

        // Test currency conversion using PriceHelper
        $convertedPrice = PriceHelper::convertCurrency(100.0, 'USD', 'EUR');
        self::assertSame(85.0, $convertedPrice);

        // Test same currency conversion
        $samePrice = PriceHelper::convertCurrency(100.0, 'USD', 'USD');
        self::assertSame(100.0, $samePrice);
    }

    public function testExchangeRateCaching(): void
    {
        // Clear cache
        Cache::flush();

        // Create exchange rate
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'updated_at' => now(),
        ]);

        // First call should cache the rate
        $rate1 = $this->exchangeRateService->getRate('USD', 'EUR');
        self::assertSame(0.85, $rate1);

        // Second call should use cached rate
        $rate2 = $this->exchangeRateService->getRate('USD', 'EUR');
        self::assertSame(0.85, $rate2);

        // Verify cache contains the rate
        self::assertTrue(Cache::has('exchange_rate_USD_EUR'));
    }

    public function testExchangeRateApiFallback(): void
    {
        // Mock HTTP response for external API
        Http::fake([
            '*' => Http::response([
                'rates' => [
                    'EUR' => 0.85,
                    'GBP' => 0.73,
                    'JPY' => 110.0,
                ],
            ], 200),
        ]);

        // Test fetching rates from API
        $count = $this->exchangeRateService->fetchAndStoreRates();
        self::assertGreaterThan(0, $count);

        // Verify rates were stored in database
        $this->assertDatabaseHas('exchange_rates', [
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
        ]);
    }

    public function testPriceCalculationHelpers(): void
    {
        // Test price difference calculation
        $difference = PriceHelper::calculatePriceDifference(100.0, 85.0);
        self::assertSame(-15.0, $difference);

        $increase = PriceHelper::calculatePriceDifference(85.0, 100.0);
        self::assertSame(17.6, round($increase, 1));

        // Test price difference string formatting
        $decreaseString = PriceHelper::getPriceDifferenceString(100.0, 85.0);
        self::assertSame('-15.0%', $decreaseString);

        $increaseString = PriceHelper::getPriceDifferenceString(85.0, 100.0);
        self::assertSame('+17.6%', $increaseString);

        // Test good deal detection
        $prices = [100.0, 120.0, 90.0, 110.0];
        self::assertTrue(PriceHelper::isGoodDeal(85.0, $prices));
        self::assertFalse(PriceHelper::isGoodDeal(125.0, $prices));

        // Test best price selection
        $bestPrice = PriceHelper::getBestPrice($prices);
        self::assertSame(90.0, $bestPrice);
    }

    public function testCurrencyValidationAndErrorHandling(): void
    {
        // Test conversion with non-existent currencies
        $result = PriceHelper::convertCurrency(100.0, 'INVALID', 'USD');
        self::assertSame(100.0, $result); // Should return original amount

        // Test exchange rate with invalid currencies
        $rate = $this->exchangeRateService->getRate('INVALID', 'USD');
        self::assertIsFloat($rate);

        // Test price formatting with null currency
        $formatted = PriceHelper::formatPrice(99.99, null);
        self::assertStringContainsString('99.99', $formatted);

        // Test empty prices array
        $bestPrice = PriceHelper::getBestPrice([]);
        self::assertNull($bestPrice);

        $isGoodDeal = PriceHelper::isGoodDeal(100.0, []);
        self::assertFalse($isGoodDeal);
    }

    public function testMultiCurrencyIntegrationWorkflow(): void
    {
        // Create multiple currencies
        $currencies = [
            ['code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0],
            ['code' => 'EUR', 'symbol' => '€', 'exchange_rate' => 0.85],
            ['code' => 'GBP', 'symbol' => '£', 'exchange_rate' => 0.73],
            ['code' => 'SAR', 'symbol' => 'ر.س', 'exchange_rate' => 3.75],
        ];

        foreach ($currencies as $currency) {
            Currency::factory()->create($currency);
        }

        // Create exchange rates
        $exchangeRates = [
            ['from_currency' => 'USD', 'to_currency' => 'EUR', 'rate' => 0.85],
            ['from_currency' => 'USD', 'to_currency' => 'GBP', 'rate' => 0.73],
            ['from_currency' => 'USD', 'to_currency' => 'SAR', 'rate' => 3.75],
        ];

        foreach ($exchangeRates as $rate) {
            ExchangeRate::create(array_merge($rate, ['updated_at' => now()]));
        }

        // Test complete workflow: convert and format prices
        $originalPrice = 100.0;

        // Convert to different currencies
        $eurPrice = $this->exchangeRateService->convert($originalPrice, 'USD', 'EUR');
        $gbpPrice = $this->exchangeRateService->convert($originalPrice, 'USD', 'GBP');
        $sarPrice = $this->exchangeRateService->convert($originalPrice, 'USD', 'SAR');

        // Format prices
        $formattedUsd = PriceHelper::formatPrice($originalPrice, 'USD');
        $formattedEur = PriceHelper::formatPrice($eurPrice, 'EUR');
        $formattedGbp = PriceHelper::formatPrice($gbpPrice, 'GBP');
        $formattedSar = PriceHelper::formatPrice($sarPrice, 'SAR');

        // Verify conversions
        self::assertSame(85.0, $eurPrice);
        self::assertSame(73.0, $gbpPrice);
        self::assertSame(375.0, $sarPrice);

        // Verify formatting
        self::assertSame('$100.00', $formattedUsd);
        self::assertSame('€85.00', $formattedEur);
        self::assertSame('£73.00', $formattedGbp);
        self::assertSame('ر.س375.00', $formattedSar);

        // Test price comparison across currencies
        $prices = [$originalPrice, $eurPrice, $gbpPrice, $sarPrice];
        $bestPrice = PriceHelper::getBestPrice($prices);
        self::assertSame(73.0, $bestPrice); // GBP has lowest value
    }
}
