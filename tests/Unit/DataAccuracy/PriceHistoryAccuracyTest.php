<?php

declare(strict_types=1);

namespace Tests\Unit\DataAccuracy;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PriceHistoryAccuracyTest extends TestCase
{
    use RefreshDatabase;

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testPriceHistoryRecordsChanges(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $product->update(['price' => 120.00]);
        $product->update(['price' => 110.00]);

        self::assertCount(3, $product->priceHistory);
        self::assertSame(100.00, $product->priceHistory->first()->price);
        self::assertSame(110.00, $product->priceHistory->last()->price);
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testHistoricalPricesAccuracy(): void
    {
        $product = Product::factory()->create(['price' => 200.00]);

        $historicalPrices = [
            ['price' => 180.00, 'effective_date' => now()->subDays(3)],
            ['price' => 190.00, 'effective_date' => now()->subDays(1)],
        ];

        $product->priceHistory()->createMany($historicalPrices);

        self::assertSame(180.00, $product->priceHistory()->oldest()->first()->price);
        self::assertSame(190.00, $product->priceHistory()->where('price', 190.00)->exists());
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function testPriceFluctuationDetection(): void
    {
        $product = Product::factory()->create(['price' => 150.00]);

        $product->update(['price' => 135.00]); // -10%
        $product->update(['price' => 148.50]); // +10%

        self::assertTrue($product->hasSignificantPriceChange(10));
        self::assertFalse($product->hasSignificantPriceChange(15));
    }
}
