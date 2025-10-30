<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Srmklive\PayPal\Services\PayPal;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Comprehensive test suite for PaymentService.
 *
 * @internal
 *
 * @coversNothing
 */
final class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    private Mockery\MockInterface $stripeMock;

    private Mockery\MockInterface $paypalMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for external payment gateways
        $this->stripeMock = \Mockery::mock(StripeClient::class);
        $this->paypalMock = \Mockery::mock(PayPal::class);

        // Inject mocks into service
        $this->paymentService = new PaymentService($this->stripeMock, $this->paypalMock);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function itProcessesStripePaymentSuccessfully(): void
    {
        // Arrange
        Log::shouldReceive('error')->never();

        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'gateway' => 'stripe',
            'is_active' => true,
        ]);

        $paymentData = ['payment_method_id' => 'pm_test_123'];

        // Mock Stripe payment intent
        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;

        $intentMock = \Mockery::mock(PaymentIntent::class);
        $intentMock->status = 'succeeded';
        $intentMock->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'id' => 'pi_test_123',
                'status' => 'succeeded',
                'amount' => 10000,
                'currency' => 'usd',
            ])
        ;

        $paymentIntentServiceMock->shouldReceive('create')
            ->once()
            ->with([
                'amount' => 10000,
                'currency' => 'USD',
                'payment_method' => 'pm_test_123',
                'confirmation_method' => 'manual',
                'confirm' => true,
            ])
            ->andReturn($intentMock)
        ;

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, $paymentData);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame($order->id, $payment->order_id);
        self::assertSame('completed', $payment->status);
        self::assertSame(100.00, $payment->amount);
        self::assertStringStartsWith('TXN_', $payment->transaction_id);
        self::assertNotNull($payment->processed_at);

        $order->refresh();
        self::assertSame('processing', $order->status);
    }

    #[Test]
    public function itHandlesStripePaymentFailure(): void
    {
        // Arrange
        Log::shouldReceive('error')->once()->withArgs(static function ($message, $context) {
            return 'Payment processing failed' === $message && isset($context['payment_id']);
        });

        $order = Order::factory()->create(['total_amount' => 50.00, 'status' => 'pending']);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;
        $paymentIntentServiceMock->shouldReceive('create')->once()->andThrow(new \Exception('Card declined'));

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, ['payment_method_id' => 'pm_declined']);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertSame('Card declined', $payment->gateway_response['error']);
        $order->refresh();
        self::assertSame('pending', $order->status);
    }

    #[Test]
    public function itProcessesPayPalPaymentSuccessfully(): void
    {
        // Arrange
        Log::shouldReceive('error')->never();

        $order = Order::factory()->create(['total_amount' => 75.50, 'currency' => 'USD', 'status' => 'pending']);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'paypal']);

        $this->paypalMock->shouldReceive('createOrder')
            ->once()
            ->with([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => ['currency_code' => 'USD', 'value' => 75.50],
                ]],
            ])
            ->andReturn(['status' => 'COMPLETED', 'id' => 'PAYPAL_ORDER_123'])
        ;

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, []);

        // Assert
        self::assertSame('completed', $payment->status);
        self::assertSame(75.50, $payment->amount);
        self::assertSame('PAYPAL_ORDER_123', $payment->gateway_response['id']);
    }

    #[Test]
    public function itHandlesPayPalPaymentFailure(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 25.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'paypal']);

        $this->paypalMock->shouldReceive('createOrder')->once()->andThrow(new \Exception('PayPal timeout'));

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, []);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertSame('PayPal timeout', $payment->gateway_response['error']);
    }

    #[Test]
    public function itThrowsExceptionForUnsupportedGateway(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'bitcoin']);

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, []);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertStringContainsString('Unsupported payment gateway', $payment->gateway_response['error']);
    }

    #[Test]
    public function itRefundsStripePaymentSuccessfully(): void
    {
        // Arrange
        Log::shouldReceive('error')->never();

        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_test_refund_123'],
        ]);

        $refundServiceMock = \Mockery::mock(RefundService::class);
        $this->stripeMock->refunds = $refundServiceMock;
        $refundServiceMock->shouldReceive('create')
            ->once()
            ->with(['payment_intent' => 'pi_test_refund_123', 'amount' => 10000])
            ->andReturn((object) ['id' => 're_test_123', 'status' => 'succeeded'])
        ;

        // Act
        $result = $this->paymentService->refundPayment($payment);

        // Assert
        self::assertTrue($result);
        $payment->refresh();
        self::assertSame('refunded', $payment->status);
    }

    #[Test]
    public function itRefundsPartialAmountForStripePayment(): void
    {
        // Arrange
        Log::shouldReceive('error')->never();

        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_partial_refund'],
        ]);

        $refundServiceMock = \Mockery::mock(RefundService::class);
        $this->stripeMock->refunds = $refundServiceMock;
        $refundServiceMock->shouldReceive('create')
            ->once()
            ->with(['payment_intent' => 'pi_partial_refund', 'amount' => 5000])
            ->andReturn((object) ['id' => 're_partial_123'])
        ;

        // Act
        $result = $this->paymentService->refundPayment($payment, 50.00);

        // Assert
        self::assertTrue($result);
        $payment->refresh();
        self::assertSame('refunded', $payment->status);
    }

    #[Test]
    public function itHandlesStripeRefundFailure(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_refund_fail'],
        ]);

        $refundServiceMock = \Mockery::mock(RefundService::class);
        $this->stripeMock->refunds = $refundServiceMock;
        $refundServiceMock->shouldReceive('create')->once()->andThrow(new \Exception('Refund already processed'));

        // Act
        $result = $this->paymentService->refundPayment($payment);

        // Assert
        self::assertFalse($result);
        $payment->refresh();
        self::assertSame('completed', $payment->status);
    }

    #[Test]
    public function itHandlesPayPalRefundPlaceholder(): void
    {
        // Arrange
        Log::shouldReceive('error')->never();

        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'paypal']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 75.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'PAYPAL_123'],
        ]);

        // Act
        $result = $this->paymentService->refundPayment($payment);

        // Assert
        self::assertTrue($result);
        $payment->refresh();
        self::assertSame('refunded', $payment->status);
    }

    #[Test]
    public function itHandlesMissingPaymentMethodDuringRefund(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $payment = Payment::factory()->create([
            'payment_method_id' => 99999,
            'amount' => 50.00,
            'status' => 'completed',
        ]);

        // Act
        $result = $this->paymentService->refundPayment($payment);

        // Assert
        self::assertFalse($result);
        $payment->refresh();
        self::assertSame('completed', $payment->status);
    }

    #[Test]
    public function itGeneratesUniqueTransactionIds(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 10.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;

        $intentMock = \Mockery::mock(PaymentIntent::class);
        $intentMock->status = 'succeeded';
        $intentMock->shouldReceive('toArray')->andReturn(['id' => 'pi_test']);

        $paymentIntentServiceMock->shouldReceive('create')->twice()->andReturn($intentMock);

        // Act
        $payment1 = $this->paymentService->processPayment($order, (string) $paymentMethod->id, ['payment_method_id' => 'pm_1']);
        $payment2 = $this->paymentService->processPayment($order, (string) $paymentMethod->id, ['payment_method_id' => 'pm_2']);

        // Assert
        self::assertNotSame($payment1->transaction_id, $payment2->transaction_id);
        self::assertStringStartsWith('TXN_', $payment1->transaction_id);
        self::assertMatchesRegularExpression('/^TXN_\d+_[A-Z0-9]{8}$/', $payment1->transaction_id);
    }

    #[Test]
    public function itHandlesMissingPaymentMethodIdInStripeData(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;

        $intentMock = \Mockery::mock(PaymentIntent::class);
        $intentMock->status = 'succeeded';
        $intentMock->shouldReceive('toArray')->andReturn(['id' => 'pi_test']);

        $paymentIntentServiceMock->shouldReceive('create')
            ->once()
            ->withArgs(static fn ($args) => '' === $args['payment_method'])
            ->andReturn($intentMock)
        ;

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, []);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
    }

    #[Test]
    public function itHandlesZeroAmountPayment(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 0.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;

        $intentMock = \Mockery::mock(PaymentIntent::class);
        $intentMock->status = 'succeeded';
        $intentMock->shouldReceive('toArray')->andReturn(['id' => 'pi_test']);

        $paymentIntentServiceMock->shouldReceive('create')
            ->once()
            ->withArgs(static fn ($args) => 0 === $args['amount'])
            ->andReturn($intentMock)
        ;

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, ['payment_method_id' => 'pm_test']);

        // Assert
        self::assertSame(0.00, $payment->amount);
    }

    #[Test]
    public function itHandlesPayPalNonArrayResponse(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 50.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'paypal']);

        $this->paypalMock->shouldReceive('createOrder')->once()->andReturn('invalid_response');

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, []);

        // Assert
        self::assertSame('failed', $payment->status);
        self::assertSame([], $payment->gateway_response);
    }

    #[Test]
    public function itHandlesNonExistentPaymentMethod(): void
    {
        // Arrange
        $order = Order::factory()->create(['total_amount' => 100.00]);

        // Act & Assert
        $this->expectException(ModelNotFoundException::class);
        $this->paymentService->processPayment($order, '99999', []);
    }

    #[Test]
    public function itCreatesPaymentRecordBeforeProcessing(): void
    {
        // Arrange
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $paymentIntentServiceMock = \Mockery::mock(PaymentIntentService::class);
        $this->stripeMock->paymentIntents = $paymentIntentServiceMock;
        $paymentIntentServiceMock->shouldReceive('create')->once()->andThrow(new \Exception('Network error'));

        // Act
        $payment = $this->paymentService->processPayment($order, (string) $paymentMethod->id, ['payment_method_id' => 'pm_test']);

        // Assert
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame('failed', $payment->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }
}
