<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Providers\CoprraServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CoprraServiceProvider::class)]
final class CoprraServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $provider = new CoprraServiceProvider($this->app);
        $provider->register();
    }

    public function testItRegistersCoprraConfiguration(): void
    {
        self::assertNotNull(config('coprra'));
        self::assertIsArray(config('coprra'));
    }

    public function testItHasCoprraNameConfiguration(): void
    {
        $name = config('coprra.name');

        self::assertNotNull($name);
        self::assertIsString($name);
        self::assertSame('COPRRA', $name);
    }

    public function testItHasCoprraVersionConfiguration(): void
    {
        $version = config('coprra.version');

        self::assertNotNull($version);
        self::assertIsString($version);
        self::assertSame('1.0.0', $version);
    }

    public function testItHasDefaultCurrencyConfiguration(): void
    {
        $currency = config('coprra.default_currency');

        self::assertNotNull($currency);
        self::assertIsString($currency);
        self::assertSame('USD', $currency);
    }

    public function testItHasDefaultLanguageConfiguration(): void
    {
        $language = config('coprra.default_language');

        self::assertNotNull($language);
        self::assertIsString($language);
        self::assertSame('en', $language);
    }

    public function testItHasPriceComparisonSettings(): void
    {
        $settings = config('coprra.price_comparison');

        self::assertIsArray($settings);
        self::assertArrayHasKey('cache_duration', $settings);
        self::assertArrayHasKey('max_stores_per_product', $settings);
        self::assertArrayHasKey('price_update_interval', $settings);
    }

    public function testItHasSearchSettings(): void
    {
        $settings = config('coprra.search');

        self::assertIsArray($settings);
        self::assertArrayHasKey('max_results', $settings);
        self::assertArrayHasKey('min_query_length', $settings);
        self::assertArrayHasKey('enable_autocomplete', $settings);
    }

    public function testItHasExchangeRatesConfiguration(): void
    {
        $rates = config('coprra.exchange_rates');

        self::assertIsArray($rates);
        self::assertArrayHasKey('USD', $rates);
        self::assertArrayHasKey('EUR', $rates);
        self::assertArrayHasKey('SAR', $rates);
        self::assertSame(1.0, $rates['USD']);
    }

    public function testItHasPaginationSettings(): void
    {
        $settings = config('coprra.pagination');

        self::assertIsArray($settings);
        self::assertArrayHasKey('default_items_per_page', $settings);
        self::assertArrayHasKey('max_wishlist_items', $settings);
        self::assertArrayHasKey('max_price_alerts', $settings);
    }

    public function testItHasApiConfiguration(): void
    {
        $api = config('coprra.api');

        self::assertIsArray($api);
        self::assertArrayHasKey('rate_limit', $api);
        self::assertArrayHasKey('version', $api);
        self::assertArrayHasKey('enable_docs', $api);
    }

    public function testItHasMediaSettings(): void
    {
        $media = config('coprra.media');

        self::assertIsArray($media);
        self::assertArrayHasKey('max_image_size', $media);
        self::assertArrayHasKey('allowed_image_types', $media);
        self::assertArrayHasKey('default_product_image', $media);
        self::assertArrayHasKey('default_store_logo', $media);
    }

    public function testItHasAnalyticsSettings(): void
    {
        $analytics = config('coprra.analytics');

        self::assertIsArray($analytics);
        self::assertArrayHasKey('track_user_behavior', $analytics);
        self::assertArrayHasKey('track_price_clicks', $analytics);
    }

    public function testItHasSecuritySettings(): void
    {
        $security = config('coprra.security');

        self::assertIsArray($security);
        self::assertArrayHasKey('enable_2fa', $security);
        self::assertArrayHasKey('password_min_length', $security);
        self::assertArrayHasKey('session_timeout', $security);
    }

    public function testItHasPerformanceSettings(): void
    {
        $performance = config('coprra.performance');

        self::assertIsArray($performance);
        self::assertArrayHasKey('enable_query_caching', $performance);
        self::assertArrayHasKey('enable_view_caching', $performance);
        self::assertArrayHasKey('enable_compression', $performance);
    }

    public function testItSharesCoprraNameWithViews(): void
    {
        self::assertSame('COPRRA', View::shared('coprraName'));
    }

    public function testItSharesCoprraVersionWithViews(): void
    {
        self::assertSame('1.0.0', View::shared('coprraVersion'));
    }

    public function testItSharesDefaultCurrencyWithViews(): void
    {
        self::assertSame('USD', View::shared('defaultCurrency'));
    }

    public function testItSharesDefaultLanguageWithViews(): void
    {
        self::assertSame('en', View::shared('defaultLanguage'));
    }

    public function testItRegistersCurrencyBladeDirective(): void
    {
        $directives = Blade::getCustomDirectives();

        self::assertArrayHasKey('currency', $directives);
        self::assertIsCallable($directives['currency']);
    }

    public function testItRegistersPricecompareBladeDirective(): void
    {
        $directives = Blade::getCustomDirectives();

        self::assertArrayHasKey('pricecompare', $directives);
        self::assertIsCallable($directives['pricecompare']);
    }

    public function testItRegistersRtlBladeDirective(): void
    {
        $directives = Blade::getCustomDirectives();

        self::assertArrayHasKey('rtl', $directives);
        self::assertIsCallable($directives['rtl']);
    }

    public function testCurrencyDirectiveGeneratesCorrectPhpCode(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['currency'];

        $result = $directive('100.50');

        self::assertIsString($result);
        self::assertStringContainsString('number_format', $result);
        self::assertStringContainsString('100.50', $result);
    }

    public function testPricecompareDirectiveGeneratesCorrectPhpCode(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['pricecompare'];

        $result = $directive('100.50');

        self::assertIsString($result);
        self::assertStringContainsString('PriceHelper::formatPrice', $result);
        self::assertStringContainsString('100.50', $result);
    }

    public function testRtlDirectiveGeneratesCorrectPhpCode(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['rtl'];

        $result = $directive();

        self::assertIsString($result);
        self::assertStringContainsString('app()->getLocale()', $result);
        self::assertStringContainsString('rtl', $result);
        self::assertStringContainsString('ltr', $result);
    }

    public function testItValidatesPriceCacheDurationIsNumeric(): void
    {
        $duration = config('coprra.price_comparison.cache_duration');

        self::assertIsNumeric($duration);
        self::assertGreaterThan(0, $duration);
    }

    public function testItValidatesMaxStoresPerProductIsNumeric(): void
    {
        $max = config('coprra.price_comparison.max_stores_per_product');

        self::assertIsNumeric($max);
        self::assertGreaterThan(0, $max);
    }

    public function testItValidatesSearchMaxResultsIsNumeric(): void
    {
        $max = config('coprra.search.max_results');

        self::assertIsNumeric($max);
        self::assertGreaterThan(0, $max);
    }

    public function testItValidatesApiRateLimitIsNumeric(): void
    {
        $limit = config('coprra.api.rate_limit');

        self::assertIsNumeric($limit);
        self::assertGreaterThan(0, $limit);
    }
}
