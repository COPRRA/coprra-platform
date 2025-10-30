<?php

declare(strict_types=1);

namespace Tests\Unit\DataQuality;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DataAccuracyTest extends TestCase
{
    use RefreshDatabase;

    // \PHPUnit\Framework\Attributes\Test
    public function testPriceCalculationAccuracy()
    {
        // Ø§Ø®ØªØ¨Ø§Ø± Ø³ÙŠÙ†Ø§Ø±ÙŠÙˆ Ø¶Ø±ÙŠØ¨Ø© 20%
        $product1 = Product::factory()->create([
            'price' => 99.99,
        ]);
        $taxRate1 = 0.20;

        // Ø§Ø®ØªØ¨Ø§Ø± Ø³ÙŠÙ†Ø§Ø±ÙŠÙˆ Ø¨Ø¯ÙˆÙ† Ø¶Ø±ÙŠØ¨Ø©
        $product2 = Product::factory()->create([
            'price' => 200.00,
        ]);
        $taxRate2 = 0.00;

        self::assertSame(119.99, round($product1->price * (1 + $taxRate1), 2));
        self::assertSame(200.00, round($product2->price * (1 + $taxRate2), 2));
    }

    // \PHPUnit\Framework\Attributes\Test
    public function testCurrencyConversionEdgeCases()
    {
        // Ø§Ø®ØªØ¨Ø§Ø± ØªØ­ÙˆÙŠÙ„ Ø¹Ù…Ù„Ø© Ø¨Ø¯Ù‚Ø© Ø¹Ø§Ù„ÙŠØ©
        $currency1 = Currency::factory()->create(['exchange_rate' => 1.2345]);
        self::assertSame(123.45, round(100 * $currency1->exchange_rate, 2));

        // Ø§Ø®ØªØ¨Ø§Ø± ØªÙ‚Ø±ÙŠØ¨ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª Ø§Ù„Ø­Ø³Ø§Ø¨ÙŠØ©
        $currency2 = Currency::factory()->create(['exchange_rate' => 1.1964]);
        self::assertSame(107.68, round(90 * $currency2->exchange_rate, 2));
    }

    // \PHPUnit\Framework\Attributes\Test
    public function testComplexOrderScenarios()
    {
        $order = Order::factory()->create();

        // Ø¥Ø¶Ø§ÙØ© Ù…Ù†ØªØ¬Ø§Øª Ø¨ÙƒÙ…ÙŠØ§Øª ÙˆØ£Ø³Ø¹Ø§Ø± Ù…Ø®ØªÙ„ÙØ©
        Product::factory()->create(['price' => 75.99])
            ->each(static fn ($product) => $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => $product->price,
            ]))
        ;

        Product::factory()->create(['price' => 149.50])
            ->each(static fn ($product) => $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => $product->price,
            ]))
        ;

        // Ø§Ù„ØªØ­Ù‚Ù‚ Ù…Ù† Ø§Ù„Ù…Ø¬Ù…ÙˆØ¹ Ø§Ù„ÙƒÙ„ÙŠ
        $expectedTotal = (75.99 * 3) + (149.50 * 2);
        self::assertSame($expectedTotal, round($order->fresh()->total, 2));
    }

    // \PHPUnit\Framework\Attributes\Test
    public function testNegativeValuesHandling()
    {
        // Ø§Ø®ØªØ¨Ø§Ø± Ù…Ø¹Ø§Ù„Ø¬Ø© Ø§Ù„Ù‚ÙŠÙ… Ø§Ù„Ø³Ø§Ù„Ø¨Ø© (Ø®ØµÙ…)
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);
        $discount = 0.15;

        self::assertSame(85.00, round($product->price * (1 - $discount), 2));
    }
}
