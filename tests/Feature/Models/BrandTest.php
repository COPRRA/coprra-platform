<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class BrandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testItCanCreateABrand(): void
    {
        // Arrange
        $attributes = [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'A test brand',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://example.com',
            'is_active' => true,
        ];

        // Act
        $brand = Brand::create($attributes);

        // Assert
        self::assertInstanceOf(Brand::class, $brand);
        self::assertSame('Test Brand', $brand->name);
        self::assertSame('test-brand', $brand->slug);
        self::assertTrue($brand->is_active);
    }

    #[Test]
    public function testItHasProductsRelationship(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        Product::factory()->create(['brand_id' => $brand->id]);

        // Act
        $brand->refresh();

        // Assert
        self::assertCount(1, $brand->products);
        self::assertInstanceOf(Product::class, $brand->products->first());
    }

    #[Test]
    public function testItCanValidateRequiredFields(): void
    {
        // Arrange
        $brand = new Brand();

        // Act
        $rules = $brand->getRules();

        // Assert
        self::assertArrayHasKey('name', $rules);
        self::assertSame('required|string|max:255', $rules['name']);
    }

    #[Test]
    public function testItCanValidateNameLength(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['name' => str_repeat('a', 256)]);

        // Assert
        self::assertFalse($brand->validate());
        self::assertArrayHasKey('name', $brand->getErrors());
    }

    #[Test]
    public function testItCanValidateWebsiteUrlFormat(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['website_url' => 'invalid-url']);

        // Assert
        self::assertFalse($brand->validate());
        self::assertArrayHasKey('website_url', $brand->getErrors());
    }

    #[Test]
    public function testItCanValidateLogoUrlFormat(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['logo_url' => 'invalid-url']);

        // Assert
        self::assertFalse($brand->validate());
        self::assertArrayHasKey('logo_url', $brand->getErrors());
    }

    #[Test]
    public function testItCanScopeActiveBrands(): void
    {
        // Arrange
        Brand::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => false]);

        // Act
        $activeBrands = Brand::active()->get();

        // Assert
        self::assertCount(1, $activeBrands);
        self::assertTrue($activeBrands->first()->is_active);
    }

    #[Test]
    public function testItCanSearchBrandsByName(): void
    {
        // Arrange
        Brand::factory()->create(['name' => 'Apple Brand']);
        Brand::factory()->create(['name' => 'Google Brand']);

        // Act
        $results = Brand::search('Apple')->get();

        // Assert
        self::assertCount(1, $results);
        self::assertSame('Apple Brand', $results->first()->name);
    }

    #[Test]
    public function testItCanGetBrandWithProductsCount(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        // Act
        $brandWithCount = Brand::withCount('products')->find($brand->id);

        // Assert
        self::assertSame(3, $brandWithCount->products_count);
    }

    #[Test]
    public function testItCanSoftDeleteBrand(): void
    {
        // Arrange
        $brand = Brand::factory()->create();

        // Act
        $brand->delete();

        // Assert
        $this->assertSoftDeleted($brand);
        self::assertNull(Brand::find($brand->id));
    }

    #[Test]
    public function testItCanRestoreSoftDeletedBrand(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        $brand->delete();

        // Act
        $brand->restore();

        // Assert
        $this->assertNotSoftDeleted($brand);
        self::assertNotNull(Brand::find($brand->id));
    }

    #[Test]
    public function testSlugGenerationOnCreate(): void
    {
        // Arrange & Act
        $brand = Brand::create(['name' => 'Test Brand Name']);

        // Assert
        self::assertSame('test-brand-name', $brand->slug);
    }
}
