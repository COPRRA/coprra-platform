<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserLocaleSetting;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateAUser(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'is_admin' => true,
            'is_active' => true,
            'is_blocked' => false,
            'role' => 'admin',
        ]);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('John Doe', $user->name);
        self::assertSame('john@example.com', $user->email);
        self::assertTrue($user->is_admin);
        self::assertTrue($user->is_active);
        self::assertFalse($user->is_blocked);
        self::assertSame('admin', $user->role);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => true,
            'is_active' => true,
            'is_blocked' => false,
            'role' => 'admin',
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2023-01-01 00:00:00',
            'is_admin' => 1,
            'is_active' => 0,
            'is_blocked' => 1,
            'banned_at' => '2023-01-01 00:00:00',
            'ban_expires_at' => '2023-01-02 00:00:00',
        ]);

        self::assertInstanceOf(Carbon::class, $user->email_verified_at);
        self::assertIsBool($user->is_admin);
        self::assertIsBool($user->is_active);
        self::assertIsBool($user->is_blocked);
        self::assertInstanceOf(Carbon::class, $user->banned_at);
        self::assertInstanceOf(Carbon::class, $user->ban_expires_at);
        self::assertTrue($user->is_admin);
        self::assertFalse($user->is_active);
        self::assertTrue($user->is_blocked);
    }

    #[Test]
    public function testItHidesSensitiveAttributes(): void
    {
        $user = User::factory()->make();

        $array = $user->toArray();

        self::assertArrayNotHasKey('password', $array);
        self::assertArrayNotHasKey('remember_token', $array);
    }

    #[Test]
    public function testUserHasManyRelationships(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Create related records
        $review1 = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $review2 = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $wishlist1 = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $wishlist2 = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $alert1 = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $alert2 = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        // Assert all hasMany relationships
        self::assertCount(2, $user->reviews, 'User should have 2 reviews');
        self::assertTrue($user->reviews->contains($review1));
        self::assertTrue($user->reviews->contains($review2));

        self::assertCount(2, $user->wishlists, 'User should have 2 wishlists');
        self::assertTrue($user->wishlists->contains($wishlist1));
        self::assertTrue($user->wishlists->contains($wishlist2));

        self::assertCount(2, $user->priceAlerts, 'User should have 2 price alerts');
        self::assertTrue($user->priceAlerts->contains($alert1));
        self::assertTrue($user->priceAlerts->contains($alert2));
    }

    #[Test]
    public function testItHasOneLocaleSetting(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $localeSetting = UserLocaleSetting::factory()->create(['user_id' => $user->id]);

        self::assertInstanceOf(UserLocaleSetting::class, $user->localeSetting);
        self::assertSame($localeSetting->id, $user->localeSetting->id);
    }

    #[Test]
    public function testIsAdminMethod(): void
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        self::assertTrue($adminUser->isAdmin());
        self::assertFalse($regularUser->isAdmin());
    }

    #[Test]
    public function testIsBannedMethod(): void
    {
        $bannedUser = User::factory()->create(['is_blocked' => true]);
        $activeUser = User::factory()->create(['is_blocked' => false]);

        self::assertTrue($bannedUser->isBanned());
        self::assertFalse($activeUser->isBanned());
    }

    #[Test]
    public function testIsBanExpiredMethod(): void
    {
        $userNotBlocked = User::factory()->create(['is_blocked' => false]);
        $userBlockedNoExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => null]);
        $userBlockedFutureExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => Carbon::now()->addDay()]);
        $userBlockedPastExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => Carbon::now()->subDay()]);

        self::assertFalse($userNotBlocked->isBanExpired());
        self::assertFalse($userBlockedNoExpiry->isBanExpired());
        self::assertFalse($userBlockedFutureExpiry->isBanExpired());
        self::assertTrue($userBlockedPastExpiry->isBanExpired());
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'name',
            'email',
            'password',
            'is_admin',
            'is_active',
            'is_blocked',
            'ban_reason',
            'ban_description',
            'banned_at',
            'ban_expires_at',
            'session_id',
            'role',
            'password_confirmed_at',
        ];

        self::assertSame($fillable, (new User())->getFillable());
    }

    #[Test]
    public function testFactoryCreatesValidUser(): void
    {
        $user = User::factory()->make();

        self::assertInstanceOf(User::class, $user);
        self::assertNotEmpty($user->name);
        self::assertNotEmpty($user->email);
        self::assertNotEmpty($user->password);
        self::assertIsBool($user->is_admin);
        self::assertIsBool($user->is_active);
        self::assertIsBool($user->is_blocked);
        self::assertNotEmpty($user->role);
    }
}
