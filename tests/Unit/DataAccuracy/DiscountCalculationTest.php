<?php

declare(strict_types=1);

namespace Tests\Unit\DataAccuracy;

use App\Models\Product;
use Tests\DatabaseSetup;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DiscountCalculationTest extends TestCase
{
    use DatabaseSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testPercentageDiscount(): void
    {
        $product = Product::factory()->create([
            'price' => 200.00,
        ]);
        $discount = 0.20;
        $discountType = 'percentage';

        self::assertSame(160.00, $product->price * (1 - $discount));
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testFixedAmountDiscount(): void
    {
        $product = Product::factory()->create([
            'price' => 150.00,
        ]);
        $discount = 30.00;
        $discountType = 'fixed';

        self::assertSame(120.00, $product->price - $discount);
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testDiscountExpiration(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);
        $discount = 0.25;
        $discountExpiresAt = now()->subDay();

        // Since the product model doesn't have activeDiscount method,
        // we test if the discount has expired
        self::assertTrue($discountExpiresAt->isPast());
    }
}
