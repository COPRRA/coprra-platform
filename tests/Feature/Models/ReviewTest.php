<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateAReview(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'title' => 'Great Product',
            'content' => 'I really liked this product.',
            'rating' => 5,
            'is_verified_purchase' => true,
            'is_approved' => true,
            'helpful_count' => 10,
        ]);

        self::assertInstanceOf(Review::class, $review);
        self::assertSame('Great Product', $review->title);
        self::assertSame('I really liked this product.', $review->content);
        self::assertSame(5, $review->rating);
        self::assertTrue($review->is_verified_purchase);
        self::assertTrue($review->is_approved);
        self::assertSame(10, $review->helpful_count);
        self::assertIsArray($review->helpful_votes);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'title' => 'Great Product',
            'content' => 'I really liked this product.',
            'rating' => 5,
            'is_verified_purchase' => true,
            'is_approved' => true,
            'helpful_count' => 10,
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $review = Review::factory()->create([
            'rating' => '5',
            'is_verified_purchase' => 1,
            'is_approved' => 0,
            'helpful_count' => '10',
            'helpful_votes' => ['user1', 'user2'],
        ]);

        self::assertIsInt($review->rating);
        self::assertIsBool($review->is_verified_purchase);
        self::assertIsBool($review->is_approved);
        self::assertIsInt($review->helpful_count);
        self::assertIsArray($review->helpful_votes);
        self::assertSame(5, $review->rating);
        self::assertTrue($review->is_verified_purchase);
        self::assertFalse($review->is_approved);
        self::assertSame(10, $review->helpful_count);
        self::assertSame(['user1', 'user2'], $review->helpful_votes);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(User::class, $review->user);
        self::assertSame($user->id, $review->user->id);
    }

    #[Test]
    public function testItBelongsToProduct(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(Product::class, $review->product);
        self::assertSame($product->id, $review->product->id);
    }

    #[Test]
    public function testGetReviewTextAttribute(): void
    {
        $content = 'This is the review content.';
        $review = Review::factory()->create(['content' => $content]);

        self::assertSame($content, $review->getReviewTextAttribute());
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'title',
            'content',
            'rating',
            'is_verified_purchase',
            'is_approved',
            'helpful_votes',
            'helpful_count',
        ];

        self::assertSame($fillable, (new Review())->getFillable());
    }

    #[Test]
    public function testFactoryCreatesValidReview(): void
    {
        $review = Review::factory()->make();

        self::assertInstanceOf(Review::class, $review);
        self::assertNotEmpty($review->title);
        self::assertNotEmpty($review->content);
        self::assertIsInt($review->rating);
        self::assertIsBool($review->is_verified_purchase);
        self::assertIsBool($review->is_approved);
        self::assertIsArray($review->helpful_votes);
        self::assertIsInt($review->helpful_count);
    }
}
