<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Review model.
 *
 * @internal
 */
#[CoversClass(Review::class)]
final class ReviewTest extends TestCase
{
    /**
     * Test that user relation is a BelongsTo instance.
     */
    public function testUserRelation(): void
    {
        $review = new Review();

        $relation = $review->user();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(User::class, $relation->getRelated()::class);
    }

    /**
     * Test that product relation is a BelongsTo instance.
     */
    public function testProductRelation(): void
    {
        $review = new Review();

        $relation = $review->product();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test getReviewTextAttribute returns content.
     */
    public function testGetReviewTextAttribute(): void
    {
        $content = 'This is a review';
        $review = new Review(['content' => $content]);

        self::assertSame($content, $review->getReviewTextAttribute());
    }
}
