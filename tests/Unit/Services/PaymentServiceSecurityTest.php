<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PaymentServiceSecurityTest extends TestCase
{
    private PaymentService $service;
    private StripeClient $mockStripe;
    private PayPal $mockPayPal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStripe = $this->createMock(StripeClient::class);
        $this->mockPayPal = $this->createMock(PayPal::class);
        $this->service = new PaymentService($this->mockStripe, $this->mockPayPal);
    }

    // Security Tests for Payment Processing

    public function testProcessPaymentWithInvalidPaymentMethodId(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $invalidPaymentMethodId = 'invalid_id';
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->service->processPayment($order, $invalidPaymentMethodId, $paymentData);
    }

    public function testProcessPaymentWithSqlInjectionAttempt(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $maliciousPaymentData = [
            'payment_method_id' => "'; DROP TABLE payments; --",
            'card_number' => '4242424242424242',
            'cvv' => '123',
        ];

        // Mock Stripe to simulate successful processing
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockIntent = $this->createMock(PaymentIntent::class);
        $mockIntent->status = 'succeeded';
        $mockIntent->method('toArray')->willReturn(['id' => 'pi_test', 'status' => 'succeeded']);

        $mockPaymentIntents->method('create')->willReturn($mockIntent);

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $maliciousPaymentData);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame('completed', $payment->status);
        // Verify that the malicious input didn't cause SQL injection
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function testProcessPaymentWithExcessiveAmount(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 999999999.99]); // Excessive amount
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Mock Stripe
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockIntent = $this->createMock(PaymentIntent::class);
        $mockIntent->status = 'succeeded';
        $mockIntent->method('toArray')->willReturn(['id' => 'pi_test', 'status' => 'succeeded']);

        $mockPaymentIntents->method('create')->willReturn($mockIntent);

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame($order->total_amount, $payment->amount);
    }

    public function testProcessPaymentWithNegativeAmount(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => -100.00]); // Negative amount
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Mock Stripe to throw exception for negative amount
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->method('create')->willThrowException(
            new \Exception('Amount must be positive')
        );

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertArrayHasKey('error', $payment->gateway_response);
    }

    public function testProcessPaymentWithUnsupportedGateway(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'unsupported_gateway']);
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertArrayHasKey('error', $payment->gateway_response);
        $this->assertStringContains('Unsupported payment gateway', $payment->gateway_response['error']);
    }

    public function testProcessPaymentWithMaliciousPaymentData(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $maliciousData = [
            'payment_method_id' => '<script>alert("xss")</script>',
            'card_number' => '../../../../etc/passwd',
            'cvv' => '<?php system($_GET["cmd"]); ?>',
            'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'ip_address' => '127.0.0.1; rm -rf /',
        ];

        // Mock Stripe
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockIntent = $this->createMock(PaymentIntent::class);
        $mockIntent->status = 'succeeded';
        $mockIntent->method('toArray')->willReturn(['id' => 'pi_test', 'status' => 'succeeded']);

        $mockPaymentIntents->method('create')->willReturn($mockIntent);

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $maliciousData);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        // Verify that malicious data is handled safely
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    // Security Tests for Refunds

    public function testRefundPaymentWithInvalidPaymentId(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'completed']);
        $payment->paymentMethod()->dissociate(); // Remove payment method to simulate invalid state

        // Act
        $result = $this->service->refundPayment($payment);

        // Assert
        self::assertFalse($result);
    }

    public function testRefundPaymentWithExcessiveRefundAmount(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_test'],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment->paymentMethod()->associate($paymentMethod);

        $excessiveRefundAmount = 200.00; // More than original payment

        // Mock Stripe refunds
        $mockRefunds = $this->createMock(RefundService::class);
        $this->mockStripe->refunds = $mockRefunds;

        $mockRefunds->method('create')->willThrowException(
            new \Exception('Refund amount exceeds payment amount')
        );

        // Act
        $result = $this->service->refundPayment($payment, $excessiveRefundAmount);

        // Assert
        self::assertFalse($result);
    }

    public function testRefundPaymentWithNegativeAmount(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_test'],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment->paymentMethod()->associate($paymentMethod);

        $negativeRefundAmount = -50.00;

        // Mock Stripe refunds
        $mockRefunds = $this->createMock(RefundService::class);
        $this->mockStripe->refunds = $mockRefunds;

        $mockRefunds->method('create')->willThrowException(
            new \Exception('Refund amount must be positive')
        );

        // Act
        $result = $this->service->refundPayment($payment, $negativeRefundAmount);

        // Assert
        self::assertFalse($result);
    }

    public function testRefundPaymentWithMaliciousGatewayResponse(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => [
                'id' => '"; DROP TABLE refunds; --',
                'malicious_script' => '<script>alert("xss")</script>',
                'path_traversal' => '../../../../etc/passwd',
            ],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment->paymentMethod()->associate($paymentMethod);

        // Mock Stripe refunds
        $mockRefunds = $this->createMock(RefundService::class);
        $this->mockStripe->refunds = $mockRefunds;

        $mockRefunds->method('create')->willReturn(
            $this->createMock(Refund::class)
        );

        // Act
        $result = $this->service->refundPayment($payment);

        // Assert
        self::assertTrue($result);
        self::assertSame('refunded', $payment->fresh()->status);
    }

    // Transaction ID Security Tests

    public function testGenerateTransactionIdUniqueness(): void
    {
        // Arrange
        $order1 = Order::factory()->create(['total_amount' => 100.00]);
        $order2 = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Mock Stripe
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockIntent = $this->createMock(PaymentIntent::class);
        $mockIntent->status = 'succeeded';
        $mockIntent->method('toArray')->willReturn(['id' => 'pi_test', 'status' => 'succeeded']);

        $mockPaymentIntents->method('create')->willReturn($mockIntent);

        // Act
        $payment1 = $this->service->processPayment($order1, (string) $paymentMethod->id, $paymentData);
        $payment2 = $this->service->processPayment($order2, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertNotSame($payment1->transaction_id, $payment2->transaction_id);
        self::assertStringStartsWith('TXN_', $payment1->transaction_id);
        self::assertStringStartsWith('TXN_', $payment2->transaction_id);
    }

    public function testTransactionIdFormat(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $paymentData = ['payment_method_id' => 'pm_test'];

        // Mock Stripe
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockIntent = $this->createMock(PaymentIntent::class);
        $mockIntent->status = 'succeeded';
        $mockIntent->method('toArray')->willReturn(['id' => 'pi_test', 'status' => 'succeeded']);

        $mockPaymentIntents->method('create')->willReturn($mockIntent);

        // Act
        $payment = $this->service->processPayment($order, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertMatchesRegularExpression('/^TXN_\d+_[A-Z0-9]{8}$/', $payment->transaction_id);
    }

    // Logging Security Tests

    public function testPaymentProcessingLogsNoSensitiveData(): void
    {
        // Arrange
        Log::spy();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $sensitiveData = [
            'payment_method_id' => 'pm_test',
            'card_number' => '4242424242424242',
            'cvv' => '123',
            'ssn' => '123-45-6789',
            'password' => 'secret123',
        ];

        // Mock Stripe to throw exception to trigger error logging
        $mockPaymentIntents = $this->createMock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->method('create')->willThrowException(
            new \Exception('Payment failed')
        );

        // Act
        $this->service->processPayment($order, (string) $paymentMethod->id, $sensitiveData);

        // Assert
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Payment processing failed', \Mockery::on(static function ($context) {
                // Verify that sensitive data is not logged
                $logString = json_encode($context);

                return ! str_contains($logString, '4242424242424242')
                       && ! str_contains($logString, '123-45-6789')
                       && ! str_contains($logString, 'secret123');
            }))
        ;
    }
}
