<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Product;
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
final class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateAWishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Great product!',
        ]);

        self::assertInstanceOf(Wishlist::class, $wishlist);
        self::assertSame($user->id, $wishlist->user_id);
        self::assertSame($product->id, $wishlist->product_id);
        self::assertSame('Great product!', $wishlist->notes);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Great product!',
        ]);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(User::class, $wishlist->user);
        self::assertSame($user->id, $wishlist->user->id);
    }

    #[Test]
    public function testItBelongsToProduct(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(Product::class, $wishlist->product);
        self::assertSame($product->id, $wishlist->product->id);
    }

    #[Test]
    public function testScopeForUser(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);

        $user1Wishlists = Wishlist::forUser($user1->id)->get();

        self::assertCount(2, $user1Wishlists);
        self::assertTrue($user1Wishlists->every(static fn ($w) => $w->user_id === $user1->id));
    }

    #[Test]
    public function testScopeForProduct(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);

        $product1Wishlists = Wishlist::forProduct($product1->id)->get();

        self::assertCount(2, $product1Wishlists);
        self::assertTrue($product1Wishlists->every(static fn ($w) => $w->product_id === $product1->id));
    }

    #[Test]
    public function testIsProductInWishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);

        self::assertTrue(Wishlist::isProductInWishlist($user->id, $product1->id));
        self::assertFalse(Wishlist::isProductInWishlist($user->id, $product2->id));
    }

    #[Test]
    public function testAddToWishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = Wishlist::addToWishlist($user->id, $product->id, 'Nice product');

        self::assertInstanceOf(Wishlist::class, $wishlist);
        self::assertSame($user->id, $wishlist->user_id);
        self::assertSame($product->id, $wishlist->product_id);
        self::assertSame('Nice product', $wishlist->notes);
    }

    #[Test]
    public function testRemoveFromWishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $removed = Wishlist::removeFromWishlist($user->id, $product->id);

        self::assertTrue($removed);
        self::assertFalse(Wishlist::isProductInWishlist($user->id, $product->id));
    }

    #[Test]
    public function testGetWishlistCount(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);

        self::assertSame(2, Wishlist::getWishlistCount($user1->id));
        self::assertSame(1, Wishlist::getWishlistCount($user2->id));
    }

    #[Test]
    public function testValidationPassesWithValidData(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = new Wishlist([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Test notes',
        ]);

        self::assertTrue($wishlist->validate());
        self::assertEmpty($wishlist->getErrors());
    }

    #[Test]
    public function testValidationFailsWithMissingRequiredFields(): void
    {
        $wishlist = new Wishlist();

        self::assertFalse($wishlist->validate());
        $errors = $wishlist->getErrors();
        self::assertArrayHasKey('user_id', $errors);
        self::assertArrayHasKey('product_id', $errors);
    }

    #[Test]
    public function testSoftDeletes(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $wishlist->delete();

        $this->assertSoftDeleted('wishlists', ['id' => $wishlist->id]);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'notes',
        ];

        self::assertSame($fillable, (new Wishlist())->getFillable());
    }

    #[Test]
    public function testFactoryCreatesValidWishlist(): void
    {
        $wishlist = Wishlist::factory()->make();

        self::assertInstanceOf(Wishlist::class, $wishlist);
        self::assertIsInt($wishlist->user_id);
        self::assertIsInt($wishlist->product_id);
        self::assertIsString($wishlist->notes);
    }
}
