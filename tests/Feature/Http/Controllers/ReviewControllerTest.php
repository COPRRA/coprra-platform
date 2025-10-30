<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function basicFunctionality(): void
    {
        // Test basic review listing
        $response = $this->get('/reviews');

        $response->assertStatus(200);
        $response->assertViewIs('reviews.index');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function expectedBehavior(): void
    {
        // Test authenticated user can create review
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post('/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great product!',
            'content' => 'This product exceeded my expectations.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great product!',
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function validation(): void
    {
        $user = User::factory()->create();

        // Test validation for missing required fields
        $response = $this->actingAs($user)->post('/reviews', []);

        $response->assertSessionHasErrors(['product_id', 'rating', 'title', 'content']);

        // Test validation for invalid rating
        $response = $this->actingAs($user)->post('/reviews', [
            'product_id' => 1,
            'rating' => 6, // Invalid rating (should be 1-5)
            'title' => 'Test',
            'content' => 'Test content',
        ]);

        $response->assertSessionHasErrors(['rating']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function storeReviewValidationSuccess(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $reviewData = [
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Good product',
            'content' => 'This is a detailed review of the product.',
        ];

        $response = $this->actingAs($user)->post('/reviews', $reviewData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', array_merge($reviewData, [
            'user_id' => $user->id,
        ]));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function guestCannotCreateReview(): void
    {
        $product = Product::factory()->create();

        $response = $this->post('/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great product!',
            'content' => 'This product exceeded my expectations.',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function userCanUpdateOwnReview(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'rating' => 3,
            'title' => 'Updated title',
            'content' => 'Updated content',
        ];

        $response = $this->actingAs($user)->put("/reviews/{$review->id}", $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', array_merge($updateData, [
            'id' => $review->id,
        ]));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function userCannotUpdateOthersReview(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->put("/reviews/{$review->id}", [
            'rating' => 1,
            'title' => 'Bad review',
            'content' => 'Trying to update someone else\'s review',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function userCanDeleteOwnReview(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/reviews/{$review->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
}
