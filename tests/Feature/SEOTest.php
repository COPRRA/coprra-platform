<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\SEOAudit;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\SEOService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(SEOService::class)]
#[CoversClass(SEOAudit::class)]
#[CoversClass(GenerateSitemap::class)]
final class SEOTest extends TestCase
{
    use RefreshDatabase;

    protected SEOService $seoService;

    protected function setUp(): void
    {
        parent::setUp();
        // Ø§Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø®Ø¯Ù…Ø© Ù…Ø¹ Ø­Ù‚Ù† Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ÙŠØ§Øª ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§
        $this->seoService = $this->app->make(SEOService::class);
    }

    public function testGeneratesMetaDataForProduct(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'This is a test product description for SEO testing purposes.',
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        self::assertIsArray($metaData);
        self::assertArrayHasKey('title', $metaData);
        self::assertArrayHasKey('description', $metaData);
        self::assertArrayHasKey('keywords', $metaData);
        self::assertArrayHasKey('og_title', $metaData);
        self::assertArrayHasKey('og_description', $metaData);
        self::assertArrayHasKey('og_image', $metaData);
        self::assertArrayHasKey('canonical', $metaData);

        self::assertStringContainsString('Test Product', $metaData['title']);
        self::assertStringContainsString('test product description', $metaData['description']);
    }

    public function testGeneratesMetaDataForCategory(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'description' => 'Browse our electronics category for the best deals.',
        ]);

        $metaData = $this->seoService->generateMetaData($category, 'Category');

        self::assertIsArray($metaData);
        self::assertStringContainsString('Electronics', $metaData['title']);
        self::assertStringContainsString('electronics', strtolower($metaData['description']));
    }

    public function testGeneratesMetaDataForStore(): void
    {
        $store = Store::factory()->create([
            'name' => 'Amazon',
            'description' => 'Shop at Amazon for great prices.',
        ]);

        $metaData = $this->seoService->generateMetaData($store, 'Store');

        self::assertIsArray($metaData);
        self::assertStringContainsString('Amazon', $metaData['title']);
        self::assertStringContainsString('Amazon', $metaData['description']);
    }

    public function testGeneratesTitleWithCorrectLength(): void
    {
        $product = Product::factory()->create([
            'name' => 'Very Long Product Name That Should Be Truncated For SEO Purposes Because It Exceeds Maximum Length',
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        self::assertLessThanOrEqual(60, \strlen($metaData['title']));
    }

    public function testGeneratesDescriptionWithCorrectLength(): void
    {
        $product = Product::factory()->create([
            'description' => str_repeat('This is a very long description. ', 20),
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        self::assertLessThanOrEqual(160, \strlen($metaData['description']));
    }

    public function testValidatesMetaDataCorrectly(): void
    {
        $validMetaData = [
            'title' => 'This is a valid title for SEO testing',
            'description' => 'This is a valid description that is long enough for SEO purposes and contains relevant information.',
            'keywords' => 'test, seo, validation',
            'canonical' => 'https://example.com/test',
        ];

        $issues = $this->seoService->validateMetaData($validMetaData);

        self::assertEmpty($issues);
    }

    public function testDetectsMissingTitle(): void
    {
        $invalidMetaData = [
            'title' => '',
            'description' => 'Valid description here',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        self::assertNotEmpty($issues);
        self::assertStringContainsString('Title', implode(' ', $issues));
    }

    public function testDetectsShortTitle(): void
    {
        $invalidMetaData = [
            'title' => 'Short',
            'description' => 'Valid description that is long enough for SEO purposes',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        self::assertNotEmpty($issues);
        self::assertStringContainsString('too short', implode(' ', $issues));
    }

    public function testDetectsLongTitle(): void
    {
        $invalidMetaData = [
            'title' => str_repeat('Very long title ', 10),
            'description' => 'Valid description that is long enough for SEO purposes',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        self::assertNotEmpty($issues);
        self::assertStringContainsString('too long', implode(' ', $issues));
    }

    public function testDetectsMissingDescription(): void
    {
        $invalidMetaData = [
            'title' => 'Valid title for testing purposes',
            'description' => '',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        self::assertNotEmpty($issues);
        self::assertStringContainsString('Description', implode(' ', $issues));
    }

    public function testGeneratesStructuredDataForProduct(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100.00,
        ]);

        $structuredData = $this->seoService->generateStructuredData($product);

        self::assertIsArray($structuredData);
        self::assertSame('https://schema.org/', $structuredData['@context']);
        self::assertSame('Product', $structuredData['@type']);
        self::assertSame('Test Product', $structuredData['name']);
        self::assertArrayHasKey('offers', $structuredData);
    }

    public function testGeneratesBreadcrumbStructuredData(): void
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'Electronics', 'url' => 'https://example.com/electronics'],
            ['name' => 'Laptops', 'url' => 'https://example.com/electronics/laptops'],
        ];

        $structuredData = $this->seoService->generateBreadcrumbData($breadcrumbs);

        self::assertIsArray($structuredData);
        self::assertSame('https://schema.org/', $structuredData['@context']);
        self::assertSame('BreadcrumbList', $structuredData['@type']);
        self::assertCount(3, $structuredData['itemListElement']);
    }

    public function testGeneratesSitemapSuccessfully(): void
    {
        // Create test data
        Product::factory()->count(5)->create(['is_active' => true]);
        Category::factory()->count(3)->create(['is_active' => true]);
        Store::factory()->count(2)->create(['is_active' => true]);

        // Generate sitemap
        Artisan::call('sitemap:generate');

        // Check if sitemap file exists
        self::assertTrue(File::exists(public_path('sitemap.xml')));

        // Check sitemap content
        $content = File::get(public_path('sitemap.xml'));
        self::assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        self::assertStringContainsString('<urlset', $content);
        self::assertStringContainsString('</urlset>', $content);

        // Clean up
        File::delete(public_path('sitemap.xml'));
    }

    public function testSitemapIncludesProducts(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
        ]);

        Artisan::call('sitemap:generate');

        $content = File::get(public_path('sitemap.xml'));
        self::assertStringContainsString('test-product', $content);

        File::delete(public_path('sitemap.xml'));
    }

    public function testSeoAuditCommandRunsSuccessfully(): void
    {
        Product::factory()->count(3)->create();

        $exitCode = Artisan::call('seo:audit');

        self::assertSame(0, $exitCode);
    }

    public function testSeoAuditCanFixIssues(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'meta_title' => null,
            'meta_description' => null,
        ]);

        Artisan::call('seo:audit --fix');

        $product->refresh();

        // Check if meta fields were populated
        self::assertNotNull($product->meta_title);
        self::assertNotNull($product->meta_description);
    }

    public function testRobotsTxtFileExists(): void
    {
        self::assertTrue(File::exists(public_path('robots.txt')));
    }

    public function testRobotsTxtHasCorrectContent(): void
    {
        $content = File::get(public_path('robots.txt'));

        self::assertStringContainsString('User-agent:', $content);
        self::assertStringContainsString('Disallow: /admin', $content);
        self::assertStringContainsString('Sitemap:', $content);
    }
}
