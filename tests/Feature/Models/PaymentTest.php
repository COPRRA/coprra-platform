<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testItCanCreateAPayment(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $attributes = [
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'transaction_id' => 'TXN-12345',
            'status' => 'completed',
            'amount' => 100.00,
            'currency' => 'USD',
            'gateway_response' => ['status' => 'success', 'reference' => 'ref123'],
            'processed_at' => now(),
        ];

        // Act
        $payment = Payment::create($attributes);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame('TXN-12345', $payment->transaction_id);
        self::assertSame('completed', $payment->status);
        self::assertSame(100.00, $payment->amount);
        self::assertSame('USD', $payment->currency);
        self::assertIsArray($payment->gateway_response);
        self::assertInstanceOf(Carbon::class, $payment->processed_at);
    }

    #[Test]
    public function testPaymentRelationships(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        // Act
        $payment->refresh();

        // Assert
        self::assertInstanceOf(Order::class, $payment->order);
        self::assertSame($order->id, $payment->order->id);
        self::assertInstanceOf(PaymentMethod::class, $payment->paymentMethod);
        self::assertSame($paymentMethod->id, $payment->paymentMethod->id);
    }

    #[Test]
    public function testPaymentCastsAttributesCorrectly(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'gateway_response' => ['code' => 200, 'message' => 'OK'],
            'processed_at' => '2023-01-01 12:00:00',
        ]);

        // Act & Assert
        self::assertIsArray($payment->gateway_response);
        self::assertSame(200, $payment->gateway_response['code']);
        self::assertInstanceOf(Carbon::class, $payment->processed_at);
    }

    #[Test]
    public function testScopeByStatus(): void
    {
        // Arrange
        Payment::factory()->create(['status' => 'pending']);
        Payment::factory()->create(['status' => 'completed']);
        Payment::factory()->create(['status' => 'failed']);
        Payment::factory()->create(['status' => 'pending']);

        // Act
        $pendingPayments = Payment::byStatus('pending')->get();
        $completedPayments = Payment::byStatus('completed')->get();

        // Assert
        self::assertCount(2, $pendingPayments);
        self::assertCount(1, $completedPayments);
        $pendingPayments->each(function ($payment) {
            $this->assertSame('pending', $payment->status);
        });
    }

    #[Test]
    public function testPaymentFillableAttributes(): void
    {
        // Arrange
        $fillable = [
            'order_id',
            'payment_method_id',
            'transaction_id',
            'status',
            'amount',
            'currency',
            'gateway_response',
            'processed_at',
        ];

        // Act
        $payment = new Payment();

        // Assert
        self::assertSame($fillable, $payment->getFillable());
    }

    #[Test]
    public function testPaymentStatusTransitions(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'pending']);

        // Act
        $payment->update(['status' => 'processing']);
        $payment->update(['status' => 'completed', 'processed_at' => now()]);

        // Assert
        self::assertSame('completed', $payment->status);
        self::assertNotNull($payment->processed_at);
    }

    #[Test]
    public function testPaymentAmountAndCurrency(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 250.75,
            'currency' => 'EUR',
        ]);

        // Act & Assert
        self::assertSame(250.75, $payment->amount);
        self::assertSame('EUR', $payment->currency);
    }

    #[Test]
    public function testPaymentGatewayResponseStorage(): void
    {
        // Arrange
        $response = [
            'transaction_id' => 'ext-123',
            'authorization_code' => 'auth-456',
            'response_code' => '00',
            'response_message' => 'Approved',
        ];

        // Act
        $payment = Payment::factory()->create(['gateway_response' => $response]);

        // Assert
        self::assertSame($response, $payment->gateway_response);
        self::assertSame('ext-123', $payment->gateway_response['transaction_id']);
    }
}
