<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\RateLimitException;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\RefundService;
use Stripe\StripeClient;
use Tests\TestCase;

/**
 * Edge case tests for PaymentService covering critical failure scenarios.
 *
 * @internal
 *
 * @coversNothing
 */
final class PaymentServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;
    private StripeClient $mockStripe;
    private PayPal $mockPayPal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStripe = \Mockery::mock(StripeClient::class);
        $this->mockPayPal = \Mockery::mock(PayPal::class);
        $this->paymentService = new PaymentService($this->mockStripe, $this->mockPayPal);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testProcessPaymentWithStripeNetworkFailure(): void
    {
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(new ApiConnectionException('Network connection failed'))
        ;

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );

        self::assertSame('failed', $payment->status);
        self::assertArrayHasKey('error', $payment->gateway_response);
        $this->assertStringContains('Network connection failed', $payment->gateway_response['error']);
    }

    public function testProcessPaymentWithStripeRateLimitExceeded(): void
    {
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(new RateLimitException('Rate limit exceeded'))
        ;

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );

        self::assertSame('failed', $payment->status);
        self::assertArrayHasKey('error', $payment->gateway_response);
    }

    public function testProcessPaymentWithPayPalServiceUnavailable(): void
    {
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'paypal']);

        $this->mockPayPal->shouldReceive('createOrder')
            ->once()
            ->andThrow(new \Exception('PayPal service temporarily unavailable'))
        ;

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            []
        );

        self::assertSame('failed', $payment->status);
        $this->assertStringContains('PayPal service temporarily unavailable', $payment->gateway_response['error']);
    }

    public function testProcessPaymentWithInvalidStripeResponse(): void
    {
        Log::shouldReceive('error')->once();

        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        // Mock a response that's missing required fields
        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andReturn((object) ['invalid' => 'response'])
        ;

        $mockPaymentIntents->shouldReceive('confirm')
            ->once()
            ->andThrow(new \Exception('Invalid payment intent structure'))
        ;

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );

        self::assertSame('failed', $payment->status);
    }

    public function testRefundPaymentWithMissingPaymentMethod(): void
    {
        $payment = Payment::factory()->create([
            'payment_method_id' => null,
            'amount' => 100.00,
            'status' => 'completed',
        ]);

        $result = $this->paymentService->refundPayment($payment);

        self::assertFalse($result);
    }

    public function testRefundPaymentWithStripeNetworkFailure(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_test_123'],
        ]);

        $mockRefunds = \Mockery::mock(RefundService::class);
        $this->mockStripe->refunds = $mockRefunds;

        $mockRefunds->shouldReceive('create')
            ->once()
            ->andThrow(new ApiConnectionException('Network timeout during refund'))
        ;

        $result = $this->paymentService->refundPayment($payment);

        self::assertFalse($result);
    }

    public function testRefundPaymentWithInvalidPaymentIntentId(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => ''], // Empty payment intent ID
        ]);

        $result = $this->paymentService->refundPayment($payment);

        self::assertTrue($result); // Should succeed but not call Stripe
    }

    public function testRefundPaymentWithMalformedGatewayResponse(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => 'invalid_json_string', // Not an array
        ]);

        $result = $this->paymentService->refundPayment($payment);

        self::assertTrue($result); // Should handle gracefully
    }

    public function testProcessPaymentWithZeroAmount(): void
    {
        $order = Order::factory()->create(['total_amount' => 0.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(static function ($args) {
                return 0 === $args['amount'];
            }))
            ->andThrow(new ApiErrorException('Amount must be at least $0.50'))
        ;

        Log::shouldReceive('error')->once();

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );

        self::assertSame('failed', $payment->status);
    }

    public function testProcessPaymentWithExtremelyLargeAmount(): void
    {
        $order = Order::factory()->create(['total_amount' => 999999999.99]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->with(\Mockery::on(static function ($args) {
                return 99999999999 === $args['amount']; // Amount in cents
            }))
            ->andThrow(new ApiErrorException('Amount exceeds maximum allowed'))
        ;

        Log::shouldReceive('error')->once();

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );

        self::assertSame('failed', $payment->status);
    }

    public function testConcurrentRefundAttempts(): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);
        $payment = Payment::factory()->create([
            'payment_method_id' => $paymentMethod->id,
            'amount' => 100.00,
            'status' => 'completed',
            'gateway_response' => ['id' => 'pi_test_123'],
        ]);

        $mockRefunds = \Mockery::mock(RefundService::class);
        $this->mockStripe->refunds = $mockRefunds;

        // First refund succeeds
        $mockRefunds->shouldReceive('create')
            ->once()
            ->andReturn((object) ['id' => 'ref_123', 'status' => 'succeeded'])
        ;

        // Second refund should fail due to already refunded
        $mockRefunds->shouldReceive('create')
            ->once()
            ->andThrow(new ApiErrorException('Charge has already been refunded'))
        ;

        $result1 = $this->paymentService->refundPayment($payment, 50.00);
        $result2 = $this->paymentService->refundPayment($payment, 50.00);

        self::assertTrue($result1);
        self::assertFalse($result2);
    }

    public function testProcessPaymentWithMissingPaymentData(): void
    {
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        $mockPaymentIntents = \Mockery::mock(PaymentIntentService::class);
        $this->mockStripe->paymentIntents = $mockPaymentIntents;

        $mockPaymentIntents->shouldReceive('create')
            ->once()
            ->andThrow(new ApiErrorException('Payment method is required'))
        ;

        Log::shouldReceive('error')->once();

        $payment = $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            [] // Empty payment data
        );

        self::assertSame('failed', $payment->status);
    }

    public function testProcessPaymentWithDatabaseConnectionFailure(): void
    {
        // This test would require mocking the database connection
        // For now, we'll test the scenario where Payment::create fails
        $order = Order::factory()->create(['total_amount' => 100.00]);
        $paymentMethod = PaymentMethod::factory()->create(['gateway' => 'stripe']);

        // Mock Payment model to throw exception on create
        $this->mock(Payment::class, static function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new \Exception('Database connection failed'))
            ;
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->paymentService->processPayment(
            $order,
            (string) $paymentMethod->id,
            ['payment_method' => 'pm_test_card']
        );
    }
}
