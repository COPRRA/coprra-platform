<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPoint;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserPointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCanCreateAUserPoint(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $userPoint = UserPoint::factory()->create([
            'user_id' => $user->id,
            'points' => 100,
            'type' => 'earned',
            'source' => 'purchase',
            'order_id' => $order->id,
            'description' => 'Points for purchase',
            'expires_at' => Carbon::now()->addYear(),
        ]);

        self::assertInstanceOf(UserPoint::class, $userPoint);
        self::assertSame($user->id, $userPoint->user_id);
        self::assertSame(100, $userPoint->points);
        self::assertSame('earned', $userPoint->type);
        self::assertSame('purchase', $userPoint->source);
        self::assertSame($order->id, $userPoint->order_id);
        self::assertSame('Points for purchase', $userPoint->description);
        self::assertInstanceOf(Carbon::class, $userPoint->expires_at);

        $this->assertDatabaseHas('user_points', [
            'user_id' => $user->id,
            'points' => 100,
            'type' => 'earned',
        ]);
    }

    #[Test]
    public function itCastsExpiresAtToDatetime(): void
    {
        $userPoint = UserPoint::factory()->create([
            'expires_at' => '2023-12-31 23:59:59',
        ]);

        self::assertInstanceOf(Carbon::class, $userPoint->expires_at);
    }

    #[Test]
    public function itBelongsToUser(): void
    {
        $user = User::factory()->create();
        $userPoint = UserPoint::factory()->create(['user_id' => $user->id]);

        self::assertInstanceOf(User::class, $userPoint->user);
        self::assertSame($user->id, $userPoint->user->id);
    }

    #[Test]
    public function itBelongsToOrder(): void
    {
        $order = Order::factory()->create();
        $userPoint = UserPoint::factory()->create(['order_id' => $order->id]);

        self::assertInstanceOf(Order::class, $userPoint->order);
        self::assertSame($order->id, $userPoint->order->id);
    }

    #[Test]
    public function itHasEarnedScope(): void
    {
        UserPoint::factory()->create(['type' => 'earned']);
        UserPoint::factory()->create(['type' => 'redeemed']);

        $earnedPoints = UserPoint::earned()->get();

        self::assertCount(1, $earnedPoints);
        self::assertSame('earned', $earnedPoints->first()->type);
    }

    #[Test]
    public function itHasRedeemedScope(): void
    {
        UserPoint::factory()->create(['type' => 'earned']);
        UserPoint::factory()->create(['type' => 'redeemed']);

        $redeemedPoints = UserPoint::redeemed()->get();

        self::assertCount(1, $redeemedPoints);
        self::assertSame('redeemed', $redeemedPoints->first()->type);
    }

    #[Test]
    public function itHasValidScope(): void
    {
        // Valid point (no expiry)
        UserPoint::factory()->create(['expires_at' => null]);

        // Valid point (future expiry)
        UserPoint::factory()->create(['expires_at' => Carbon::now()->addDay()]);

        // Expired point
        UserPoint::factory()->create(['expires_at' => Carbon::now()->subDay()]);

        $validPoints = UserPoint::valid()->get();

        self::assertCount(2, $validPoints);
    }

    #[Test]
    public function itHasFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'points',
            'type',
            'source',
            'order_id',
            'description',
            'expires_at',
        ];

        self::assertSame($fillable, (new UserPoint())->getFillable());
    }
}
