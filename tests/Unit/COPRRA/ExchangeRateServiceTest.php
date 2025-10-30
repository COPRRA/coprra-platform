<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Models\ExchangeRate;
use App\Services\ExchangeRates\RateProvider;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(ExchangeRateService::class)]
#[CoversClass(ExchangeRate::class)]
final class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExchangeRateService(new RateProvider());
        Cache::flush();
    }

    public function testItReturnsOneForSameCurrency(): void
    {
        $rate = $this->service->getRate('USD', 'USD');

        self::assertSame(1.0, $rate);
    }

    public function testItSeedsExchangeRatesFromConfig(): void
    {
        $count = $this->service->seedFromConfig();

        self::assertGreaterThan(0, $count);

        // Check if rates were stored
        $this->assertDatabaseHas('exchange_rates', [
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'source' => 'config',
        ]);
    }

    public function testItGetsRateFromDatabase(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $rate = $this->service->getRate('USD', 'EUR');

        self::assertSame(0.85, $rate);
    }

    public function testItCachesExchangeRates(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        // First call - should cache
        $rate1 = $this->service->getRate('USD', 'EUR');

        // Check cache
        $cached = Cache::get('exchange_rate_USD_EUR');
        self::assertNotNull($cached);
        self::assertSame(0.85, $cached);

        // Second call - should use cache
        $rate2 = $this->service->getRate('USD', 'EUR');

        self::assertSame($rate1, $rate2);
    }

    public function testItConvertsCurrencyCorrectly(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $converted = $this->service->convert(100.0, 'USD', 'EUR');

        self::assertSame(85.0, $converted);
    }

    public function testItIdentifiesStaleRates(): void
    {
        $freshRate = ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $staleRate = ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        self::assertFalse($freshRate->isStale());
        self::assertTrue($staleRate->isStale());
    }

    public function testItGetsFreshRatesOnly(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        $freshRates = ExchangeRate::where('fetched_at', '>', now()->subHours(24))->get();

        self::assertCount(1, $freshRates);
        self::assertSame('EUR', $freshRates->first()->to_currency);
    }

    public function testItGetsStaleRatesOnly(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        $staleRates = ExchangeRate::stale()->get();

        self::assertCount(1, $staleRates);
        self::assertSame('GBP', $staleRates->first()->to_currency);
    }

    public function testItReturnsSupportedCurrencies(): void
    {
        $currencies = $this->service->getSupportedCurrencies();

        self::assertIsArray($currencies);
        self::assertContains('USD', $currencies);
        self::assertContains('EUR', $currencies);
        self::assertContains('SAR', $currencies);
    }

    public function testItUsesFallbackRateWhenNotInDatabase(): void
    {
        // Don't seed any rates
        $rate = $this->service->getRate('USD', 'EUR');

        // Should use fallback from config
        self::assertIsFloat($rate);
        self::assertGreaterThan(0, $rate);
    }

    public function testItHandlesReverseConversion(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'EUR',
            'to_currency' => 'USD',
            'rate' => 1.176,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $usdToEur = $this->service->convert(100.0, 'USD', 'EUR');
        $eurToUsd = $this->service->convert(85.0, 'EUR', 'USD');

        self::assertSame(85.0, $usdToEur);
        self::assertEqualsWithDelta(100.0, $eurToUsd, 0.1);
    }

    public function testItUpdatesExistingRates(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now()->subDay(),
        ]);

        $this->service->seedFromConfig();

        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'EUR')
            ->first()
        ;

        self::assertNotNull($rate);
        self::assertSame('config', $rate->source);
    }
}
