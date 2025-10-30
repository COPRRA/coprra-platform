<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Category;
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
final class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testItCanCreateACategory(): void
    {
        // Arrange
        $attributes = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category',
            'level' => 0,
            'is_active' => true,
        ];

        // Act
        $category = Category::create($attributes);

        // Assert
        self::assertInstanceOf(Category::class, $category);
        self::assertSame('Test Category', $category->name);
        self::assertSame('test-category', $category->slug);
        self::assertSame(0, $category->level);
        self::assertTrue($category->is_active);
    }

    #[Test]
    public function testItHasProductsRelationship(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // Act
        $category->refresh();

        // Assert
        self::assertCount(1, $category->products);
        self::assertInstanceOf(Product::class, $category->products->first());
    }

    #[Test]
    public function testItCanValidateRequiredFields(): void
    {
        // Arrange
        $category = new Category();

        // Act
        $rules = $category->getRules();

        // Assert
        self::assertArrayHasKey('name', $rules);
        self::assertSame('required|string|max:255', $rules['name']);
    }

    #[Test]
    public function testItCanValidateNameLength(): void
    {
        // Arrange & Act
        $category = Category::factory()->make(['name' => str_repeat('a', 256)]);

        // Assert
        self::assertFalse($category->validate());
        self::assertArrayHasKey('name', $category->getErrors());
    }

    #[Test]
    public function testItCanScopeActiveCategories(): void
    {
        // Arrange
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        // Act
        $activeCategories = Category::active()->get();

        // Assert
        self::assertCount(1, $activeCategories);
        self::assertTrue($activeCategories->first()->is_active);
    }

    #[Test]
    public function testItCanSearchCategoriesByName(): void
    {
        // Arrange
        Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Clothing']);

        // Act
        $results = Category::search('Electro')->get();

        // Assert
        self::assertCount(1, $results);
        self::assertSame('Electronics', $results->first()->name);
    }

    #[Test]
    public function testItCanGetCategoryWithProductsCount(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        // Act
        $categoryWithCount = Category::withCount('products')->find($category->id);

        // Assert
        self::assertSame(2, $categoryWithCount->products_count);
    }

    #[Test]
    public function testItCanSoftDeleteCategory(): void
    {
        // Arrange
        $category = Category::factory()->create();

        // Act
        $category->delete();

        // Assert
        $this->assertSoftDeleted($category);
        self::assertNull(Category::find($category->id));
    }

    #[Test]
    public function testItCanRestoreSoftDeletedCategory(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $category->delete();

        // Act
        $category->restore();

        // Assert
        $this->assertNotSoftDeleted($category);
        self::assertNotNull(Category::find($category->id));
    }

    #[Test]
    public function testItAutoGeneratesSlugFromName(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => 'Test Category Name']);

        // Assert
        self::assertSame('test-category-name', $category->slug);
    }

    #[Test]
    public function testItUpdatesSlugWhenNameChanges(): void
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'Old Name']);

        // Act
        $category->update(['name' => 'New Name']);

        // Assert
        self::assertSame('new-name', $category->slug);
    }

    #[Test]
    public function testCategoryHierarchyParentChild(): void
    {
        // Arrange
        $parent = Category::factory()->create(['level' => 0]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Act
        $child->refresh();

        // Assert
        self::assertSame(1, $child->level);
        self::assertSame($parent->id, $child->parent_id);
        self::assertInstanceOf(Category::class, $child->parent);
        self::assertSame($parent->id, $child->parent->id);
        self::assertCount(1, $parent->children);
        self::assertSame($child->id, $parent->children->first()->id);
    }

    #[Test]
    public function testLevelCalculationOnParentChange(): void
    {
        // Arrange
        $parent = Category::factory()->create(['level' => 0]);
        $child = Category::factory()->create(['level' => 1, 'parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['level' => 2, 'parent_id' => $child->id]);

        // Act
        $grandchild->update(['parent_id' => $parent->id]);

        // Assert
        self::assertSame(1, $grandchild->level);
    }
}
