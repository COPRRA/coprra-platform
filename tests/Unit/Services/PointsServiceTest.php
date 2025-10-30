<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\UserPoint;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PointsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PointsService();
    }

    public function testAddPointsCreatesUserPoint(): void
    {
        // Arrange
        $user = User::factory()->create();
        $points = 100;
        $type = 'earned';
        $source = 'purchase';

        // Act
        $userPoint = $this->service->addPoints($user, $points, $type, $source);

        // Assert
        self::assertInstanceOf(UserPoint::class, $userPoint);
        self::assertSame($user->id, $userPoint->user_id);
        self::assertSame($points, $userPoint->points);
        self::assertSame($type, $userPoint->type);
    }

    public function testGetAvailablePointsReturnsSum(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 50, 'type' => 'earned']);
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => -20, 'type' => 'redeemed']);

        // Act
        $available = $this->service->getAvailablePoints($user->id);

        // Assert
        self::assertSame(30, $available);
    }

    public function testRedeemPointsSucceedsWithSufficientPoints(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 100, 'type' => 'earned']);

        // Act
        $result = $this->service->redeemPoints($user, 50, 'Test redemption');

        // Assert
        self::assertTrue($result);
        self::assertSame(50, $this->service->getAvailablePoints($user->id));
    }

    public function testRedeemPointsFailsWithInsufficientPoints(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->create(['user_id' => $user->id, 'points' => 30, 'type' => 'earned']);

        // Act
        $result = $this->service->redeemPoints($user, 50, 'Test redemption');

        // Assert
        self::assertFalse($result);
        self::assertSame(30, $this->service->getAvailablePoints($user->id));
    }

    public function testAwardPurchasePointsAddsPoints(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 100.00]);

        // Act
        $this->service->awardPurchasePoints($order);

        // Assert
        self::assertSame(1, $this->service->getAvailablePoints($user->id)); // 100 * 0.01 = 1
    }

    public function testGetPointsHistoryReturnsPaginated(): void
    {
        // Arrange
        $user = User::factory()->create();
        UserPoint::factory()->count(5)->create(['user_id' => $user->id]);

        // Act
        $history = $this->service->getPointsHistory($user->id, 3);

        // Assert
        self::assertCount(3, $history);
    }
}
