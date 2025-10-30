<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class RewardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCanCreateAReward(): void
    {
        $reward = Reward::factory()->create([
            'name' => 'Discount Coupon',
            'description' => '10% off',
            'points_required' => 100,
            'type' => 'discount',
            'value' => ['percentage' => 10],
            'is_active' => true,
            'usage_limit' => 100,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        self::assertInstanceOf(Reward::class, $reward);
        self::assertSame('Discount Coupon', $reward->name);
        self::assertSame('10% off', $reward->description);
        self::assertSame(100, $reward->points_required);
        self::assertSame('discount', $reward->type);
        self::assertIsArray($reward->value);
        self::assertTrue($reward->is_active);
        self::assertSame(100, $reward->usage_limit);
        self::assertInstanceOf(Carbon::class, $reward->valid_from);
        self::assertInstanceOf(Carbon::class, $reward->valid_until);

        $this->assertDatabaseHas('rewards', [
            'name' => 'Discount Coupon',
            'points_required' => 100,
            'type' => 'discount',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function itCastsAttributesCorrectly(): void
    {
        $reward = Reward::factory()->create([
            'value' => ['amount' => 50],
            'is_active' => 1,
            'valid_from' => '2023-01-01 00:00:00',
            'valid_until' => '2023-12-31 23:59:59',
        ]);

        self::assertIsArray($reward->value);
        self::assertSame(['amount' => 50], $reward->value);
        self::assertIsBool($reward->is_active);
        self::assertTrue($reward->is_active);
        self::assertInstanceOf(Carbon::class, $reward->valid_from);
        self::assertInstanceOf(Carbon::class, $reward->valid_until);
    }

    #[Test]
    public function itHasActiveScope(): void
    {
        // Active reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        // Inactive reward
        Reward::factory()->create(['is_active' => false]);

        // Expired reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_until' => Carbon::now()->subDay(),
        ]);

        // Future reward
        Reward::factory()->create([
            'is_active' => true,
            'valid_from' => Carbon::now()->addDay(),
        ]);

        $activeRewards = Reward::active()->get();

        self::assertCount(1, $activeRewards);
        self::assertTrue($activeRewards->first()->is_active);
    }

    #[Test]
    public function itHasAvailableForPointsScope(): void
    {
        Reward::factory()->create([
            'points_required' => 50,
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        Reward::factory()->create([
            'points_required' => 200,
            'is_active' => true,
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addDay(),
        ]);

        $availableRewards = Reward::availableForPoints(100)->get();

        self::assertCount(1, $availableRewards);
        self::assertSame(50, $availableRewards->first()->points_required);
    }

    #[Test]
    public function itHasFillableAttributes(): void
    {
        $fillable = [
            'name',
            'description',
            'points_required',
            'type',
            'value',
            'is_active',
            'usage_limit',
            'valid_from',
            'valid_until',
        ];

        self::assertSame($fillable, (new Reward())->getFillable());
    }
}
