<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PriceAlert;
use App\Models\Review;
use App\Models\User;
use App\Models\UserLocaleSetting;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the User model.
 *
 * @internal
 */
#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    /**
     * Test that isAdmin returns true when is_admin is true.
     */
    public function testIsAdminReturnsTrueWhenIsAdminIsTrue(): void
    {
        $user = new User(['is_admin' => true]);

        self::assertTrue($user->isAdmin());
    }

    /**
     * Test that isAdmin returns false when is_admin is false or null.
     */
    public function testIsAdminReturnsFalseWhenIsAdminIsFalse(): void
    {
        $user = new User(['is_admin' => false]);

        self::assertFalse($user->isAdmin());
    }

    /**
     * Test that isAdmin returns false when is_admin is null.
     */
    public function testIsAdminReturnsFalseWhenIsAdminIsNull(): void
    {
        $user = new User();

        self::assertFalse($user->isAdmin());
    }

    /**
     * Test that isBanned returns true when is_blocked is true.
     */
    public function testIsBannedReturnsTrueWhenIsBlockedIsTrue(): void
    {
        $user = new User(['is_blocked' => true]);

        self::assertTrue($user->isBanned());
    }

    /**
     * Test that isBanned returns false when is_blocked is false or null.
     */
    public function testIsBannedReturnsFalseWhenIsBlockedIsFalse(): void
    {
        $user = new User(['is_blocked' => false]);

        self::assertFalse($user->isBanned());
    }

    /**
     * Test that isBanned returns false when is_blocked is null.
     */
    public function testIsBannedReturnsFalseWhenIsBlockedIsNull(): void
    {
        $user = new User();

        self::assertFalse($user->isBanned());
    }

    /**
     * Test that isBanExpired returns false when user is not blocked.
     */
    public function testIsBanExpiredReturnsFalseWhenNotBlocked(): void
    {
        $user = new User(['is_blocked' => false]);

        self::assertFalse($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns false when ban_expires_at is in the future.
     */
    public function testIsBanExpiredReturnsFalseWhenBanNotExpired(): void
    {
        $user = new User([
            'is_blocked' => true,
            'ban_expires_at' => Carbon::now()->addDay(),
        ]);

        self::assertFalse($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns true when ban_expires_at is in the past.
     */
    public function testIsBanExpiredReturnsTrueWhenBanExpired(): void
    {
        $user = new User([
            'is_blocked' => true,
            'ban_expires_at' => Carbon::now()->subDay(),
        ]);

        self::assertTrue($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns false when ban_expires_at is null and user is blocked.
     */
    public function testIsBanExpiredReturnsFalseWhenBanExpiresAtIsNull(): void
    {
        $user = new User(['is_blocked' => true]);

        self::assertFalse($user->isBanExpired());
    }

    /**
     * Test that reviews relation is a HasMany instance.
     */
    public function testReviewsRelation(): void
    {
        $user = new User();

        $relation = $user->reviews();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(Review::class, $relation->getRelated()::class);
    }

    /**
     * Test that wishlists relation is a HasMany instance.
     */
    public function testWishlistsRelation(): void
    {
        $user = new User();

        $relation = $user->wishlists();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(Wishlist::class, $relation->getRelated()::class);
    }

    /**
     * Test that priceAlerts relation is a HasMany instance.
     */
    public function testPriceAlertsRelation(): void
    {
        $user = new User();

        $relation = $user->priceAlerts();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(PriceAlert::class, $relation->getRelated()::class);
    }

    /**
     * Test that localeSetting relation is a HasOne instance.
     */
    public function testLocaleSettingRelation(): void
    {
        $user = new User();

        $relation = $user->localeSetting();

        self::assertInstanceOf(HasOne::class, $relation);
        self::assertSame(UserLocaleSetting::class, $relation->getRelated()::class);
    }
}
