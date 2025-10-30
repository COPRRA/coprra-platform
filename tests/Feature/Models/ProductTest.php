<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceAlert;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateAProduct(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'price' => 99.99,
            'image' => 'test.jpg',
            'is_active' => true,
            'stock_quantity' => 100,
        ]);

        self::assertInstanceOf(Product::class, $product);
        self::assertSame('Test Product', $product->name);
        self::assertSame('test-product', $product->slug);
        self::assertSame('Test Description', $product->description);
        self::assertSame(99.99, $product->price);
        self::assertSame('test.jpg', $product->image);
        self::assertTrue($product->is_active);
        self::assertSame(100, $product->stock_quantity);

        // Assert that the product was actually saved to the database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'price' => 99.99,
            'image' => 'test.jpg',
            'is_active' => true,
            'stock_quantity' => 100,
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $product = Product::factory()->create([
            'price' => '99.99',
            'is_active' => 1,
            'stock_quantity' => '100',
        ]);

        self::assertIsString($product->price);
        self::assertIsBool($product->is_active);
        self::assertIsInt($product->stock_quantity);
        self::assertSame('99.99', $product->price);
        self::assertTrue($product->is_active);
        self::assertSame(100, $product->stock_quantity);
    }

    #[Test]
    public function testItBelongsToBrand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        self::assertInstanceOf(Brand::class, $product->brand);
        self::assertSame($brand->id, $product->brand->id);
    }

    #[Test]
    public function testItBelongsToCategory(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        self::assertInstanceOf(Category::class, $product->category);
        self::assertSame($category->id, $product->category->id);
    }

    #[Test]
    public function testItBelongsToStore(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        self::assertInstanceOf(Store::class, $product->store);
        self::assertSame($store->id, $product->store->id);
    }

    #[Test]
    public function testProductHasManyRelationships(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $store1 = Store::factory()->create(['name' => 'Store 1']);
        $store2 = Store::factory()->create(['name' => 'Store 2']);

        // Create related records
        $priceAlert1 = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $priceAlert2 = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $review1 = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $review2 = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $wishlist1 = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $wishlist2 = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);

        $priceOffer1 = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store1->id]);
        $priceOffer2 = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store2->id]);

        // Assert all hasMany relationships
        self::assertCount(2, $product->priceAlerts, 'Product should have 2 price alerts');
        self::assertTrue($product->priceAlerts->contains($priceAlert1));
        self::assertTrue($product->priceAlerts->contains($priceAlert2));

        self::assertCount(2, $product->reviews, 'Product should have 2 reviews');
        self::assertTrue($product->reviews->contains($review1));
        self::assertTrue($product->reviews->contains($review2));

        self::assertCount(2, $product->wishlists, 'Product should have 2 wishlists');
        self::assertTrue($product->wishlists->contains($wishlist1));
        self::assertTrue($product->wishlists->contains($wishlist2));

        self::assertCount(2, $product->priceOffers, 'Product should have 2 price offers');
        self::assertTrue($product->priceOffers->contains($priceOffer1));
        self::assertTrue($product->priceOffers->contains($priceOffer2));
    }

    #[Test]
    public function testScopeActiveFiltersActiveProducts(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        Product::factory()->create(['is_active' => true]);

        $activeProducts = Product::active()->get();

        self::assertCount(2, $activeProducts);
        self::assertTrue($activeProducts->every(static fn ($product) => true === $product->is_active));
    }

    #[Test]
    public function testScopeSearchFiltersByName(): void
    {
        Product::factory()->create(['name' => 'iPhone 15']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'iPhone 14']);

        $iphoneProducts = Product::search('iPhone')->get();
        $samsungProducts = Product::search('Samsung')->get();

        self::assertCount(2, $iphoneProducts);
        self::assertCount(1, $samsungProducts);
        self::assertTrue($iphoneProducts->every(static fn ($product) => str_contains($product->name, 'iPhone')));
        self::assertTrue($samsungProducts->every(static fn ($product) => str_contains($product->name, 'Samsung')));
    }

    #[Test]
    public function testScopeWithReviewsCountAddsReviewsCount(): void
    {
        $product = Product::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);

        $productWithCount = Product::withReviewsCount()->find($product->id);

        self::assertSame(3, $productWithCount->reviews_count);
    }

    #[Test]
    public function testGetAverageRating(): void
    {
        $product = Product::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id, 'rating' => 4]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id, 'rating' => 5]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id, 'rating' => 3]);

        $averageRating = $product->getAverageRating();

        self::assertSame(4.0, $averageRating);
    }

    #[Test]
    public function testGetAverageRatingReturnsZeroWhenNoReviews(): void
    {
        $product = Product::factory()->create();

        $averageRating = $product->getAverageRating();

        self::assertSame(0.0, $averageRating);
    }

    #[Test]
    public function testGetTotalReviews(): void
    {
        $product = Product::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);
        $user4 = User::factory()->create(['email' => 'user4@example.com']);
        $user5 = User::factory()->create(['email' => 'user5@example.com']);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user4->id]);
        Review::factory()->create(['product_id' => $product->id, 'user_id' => $user5->id]);

        $totalReviews = $product->getTotalReviews();

        self::assertSame(5, $totalReviews);
    }

    #[Test]
    public function testIsInWishlist(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create(['email' => 'user@example.com']);
        Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user->id]);

        self::assertTrue($product->isInWishlist($user->id));
        self::assertFalse($product->isInWishlist(999));
    }

    #[Test]
    public function testGetCurrentPriceWithActiveOffer(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);
        PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 80.00,
            'is_available' => true,
            'created_at' => now(),
        ]);

        $currentPrice = $product->getCurrentPrice();

        self::assertSame(80.00, $currentPrice);
    }

    #[Test]
    public function testGetCurrentPriceWithoutActiveOffer(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $currentPrice = $product->getCurrentPrice();

        self::assertSame(100.00, $currentPrice);
    }

    #[Test]
    public function testGetPriceHistory(): void
    {
        $product = Product::factory()->create();
        $offer1 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 100.00,
            'created_at' => now()->subDay(),
        ]);
        $offer2 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 90.00,
            'created_at' => now(),
        ]);

        $priceHistory = $product->getPriceHistory();

        self::assertCount(2, $priceHistory);
        self::assertSame($offer2->id, $priceHistory->first()->id); // Most recent first
        self::assertSame($offer1->id, $priceHistory->last()->id);
    }

    #[Test]
    public function testValidationPassesWithValidData(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Valid Product',
            'price' => 99.99,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        self::assertTrue($product->validate());
        self::assertEmpty($product->getErrors());
    }

    #[Test]
    public function testValidationFailsWithMissingRequiredFields(): void
    {
        $product = new Product();

        self::assertFalse($product->validate());
        $errors = $product->getErrors();
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('price', $errors);
        self::assertArrayHasKey('brand_id', $errors);
        self::assertArrayHasKey('category_id', $errors);
    }

    #[Test]
    public function testValidationPassesWithStringPrice(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = new Product([
            'name' => 'Test Product',
            'price' => '99.99',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        self::assertTrue($product->validate());
        $errors = $product->getErrors();
        self::assertEmpty($errors);
    }

    #[Test]
    public function testValidationFailsWithNegativePrice(): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $product = new Product([
            'name' => 'Test Product',
            'price' => -10.00,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        self::assertFalse($product->validate());
        $errors = $product->getErrors();
        self::assertArrayHasKey('price', $errors);
    }

    #[Test]
    public function testSoftDeletes(): void
    {
        $product = Product::factory()->create();

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function testBootedDeletesRelatedRecords(): void
    {
        $product = Product::factory()->create();
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);
        $priceAlert = PriceAlert::factory()->create(['product_id' => $product->id, 'user_id' => $user1->id]);
        $review = Review::factory()->create(['product_id' => $product->id, 'user_id' => $user2->id]);
        $wishlist = Wishlist::factory()->create(['product_id' => $product->id, 'user_id' => $user3->id]);
        $store = Store::factory()->create(['name' => 'Test Store']);
        $priceOffer = PriceOffer::factory()->create(['product_id' => $product->id, 'store_id' => $store->id]);

        $product->forceDelete();

        $this->assertDatabaseMissing('price_alerts', ['id' => $priceAlert->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
        $this->assertDatabaseMissing('price_offers', ['id' => $priceOffer->id]);
    }

    #[Test]
    public function testFactoryCreatesValidProduct(): void
    {
        $product = Product::factory()->make();

        self::assertInstanceOf(Product::class, $product);
        self::assertNotEmpty($product->name);
        self::assertNotEmpty($product->slug);
        self::assertNotEmpty($product->description);
        self::assertIsString($product->price);
        self::assertIsBool($product->is_active);
        self::assertIsInt($product->stock_quantity ?? 0);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'name',
            'slug',
            'description',
            'price',
            'image',
            'is_active',
            'stock_quantity',
            'category_id',
            'brand_id',
            'store_id',
        ];

        self::assertSame($fillable, (new Product())->getFillable());
    }
}
