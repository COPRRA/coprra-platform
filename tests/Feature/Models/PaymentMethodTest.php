<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCanCreateAPaymentMethod(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'Credit Card',
            'gateway' => 'stripe',
            'type' => 'card',
            'config' => ['key' => 'value'],
            'is_active' => true,
            'is_default' => false,
        ]);

        self::assertInstanceOf(PaymentMethod::class, $paymentMethod);
        self::assertSame('Credit Card', $paymentMethod->name);
        self::assertSame('stripe', $paymentMethod->gateway);
        self::assertSame('card', $paymentMethod->type);
        self::assertIsArray($paymentMethod->config);
        self::assertTrue($paymentMethod->is_active);
        self::assertFalse($paymentMethod->is_default);

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'Credit Card',
            'gateway' => 'stripe',
            'type' => 'card',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    #[Test]
    public function itCastsAttributesCorrectly(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'config' => ['api_key' => 'secret'],
            'is_active' => 1,
            'is_default' => 0,
        ]);

        self::assertIsArray($paymentMethod->config);
        self::assertSame(['api_key' => 'secret'], $paymentMethod->config);
        self::assertIsBool($paymentMethod->is_active);
        self::assertIsBool($paymentMethod->is_default);
        self::assertTrue($paymentMethod->is_active);
        self::assertFalse($paymentMethod->is_default);
    }

    #[Test]
    public function itHasManyPayments(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $payment1 = Payment::factory()->create(['payment_method_id' => $paymentMethod->id]);
        $payment2 = Payment::factory()->create(['payment_method_id' => $paymentMethod->id]);

        self::assertCount(2, $paymentMethod->payments);
        self::assertTrue($paymentMethod->payments->contains($payment1));
        self::assertTrue($paymentMethod->payments->contains($payment2));
    }

    #[Test]
    public function itHasActiveScope(): void
    {
        PaymentMethod::factory()->create(['is_active' => true]);
        PaymentMethod::factory()->create(['is_active' => false]);

        $activeMethods = PaymentMethod::active()->get();

        self::assertCount(1, $activeMethods);
        self::assertTrue($activeMethods->first()->is_active);
    }

    #[Test]
    public function itHasDefaultScope(): void
    {
        PaymentMethod::factory()->create(['is_default' => true]);
        PaymentMethod::factory()->create(['is_default' => false]);

        $defaultMethods = PaymentMethod::default()->get();

        self::assertCount(1, $defaultMethods);
        self::assertTrue($defaultMethods->first()->is_default);
    }

    #[Test]
    public function itHasFillableAttributes(): void
    {
        $fillable = [
            'name',
            'gateway',
            'type',
            'config',
            'is_active',
            'is_default',
        ];

        self::assertSame($fillable, (new PaymentMethod())->getFillable());
    }
}
