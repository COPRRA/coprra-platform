<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PriceDropNotification;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\Support\MockValidationTrait;
use Tests\Support\TestIsolationTrait;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class NotificationServiceTest extends TestCase
{
    use MockValidationTrait;
    use RefreshDatabase;
    use TestIsolationTrait;

    private NotificationService $service;
    private MockInterface $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupGlobalState();

        $this->auditService = \Mockery::mock(AuditService::class);

        // Validate mock interface and methods
        $this->assertImplementsInterface(AuditService::class, $this->auditService);
        $this->validateMockMethods(AuditService::class, ['logSensitiveOperation']);

        // Validate service dependencies
        $this->validateServiceMock(NotificationService::class, [AuditService::class]);

        $this->service = new NotificationService($this->auditService);

        Notification::fake();
    }

    protected function tearDown(): void
    {
        $this->restoreGlobalState();
        $this->clearTestCaches();
        $this->closeMockery();
        $this->verifyTestIsolation();
        parent::tearDown();
    }

    public function testSendsPriceDropNotificationToActiveAlerts(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $user = User::factory()->create(['id' => 1, 'email' => 'test@example.com']);
        $oldPrice = 100.0;
        $newPrice = 80.0;
        $targetPrice = 90.0;

        $alert = PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'target_price' => $targetPrice,
            'is_active' => true,
        ]);

        $this->auditService->shouldReceive('logSensitiveOperation')
            ->once()
            ->with('price_drop_notification', \Mockery::on(static function ($passedUser) use ($user) {
                return $passedUser instanceof User && $passedUser->id === $user->id;
            }), \Mockery::type('array'))
        ;

        // Act
        try {
            $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);
        } catch (\Exception $e) {
            self::fail('Service threw exception: '.$e->getMessage());
        }

        // Assert
        Notification::assertSentTo($user, PriceDropNotification::class);
    }

    public function testDoesNotSendNotificationWhenNoActiveAlerts(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $oldPrice = 100.0;
        $newPrice = 80.0;

        // No alerts created - should not send notification

        // Act
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert
        Notification::assertNothingSent();
    }

    public function testSkipsNotificationForUserWithoutEmail(): void
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Test Product']);

        // Create user with null email (if database allows) or skip this test
        try {
            $userWithoutEmail = User::factory()->create(['email' => null]);
        } catch (\Exception $e) {
            self::markTestSkipped('Database does not allow null email addresses');

            return;
        }

        PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $userWithoutEmail->id,
            'target_price' => 90.0,
            'is_active' => true,
        ]);

        // Ensure audit logging is not called for users without email
        $this->auditService->shouldNotReceive('logSensitiveOperation');

        // Act
        $this->service->sendPriceDropNotification($product, 100.0, 80.0);

        // Assert
        Notification::assertNothingSent();
    }

    public function testHandlesExceptionDuringNotificationSending(): void
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Test Product']);
        $user = User::factory()->create(['email' => 'test@example.com']);

        PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'target_price' => 90.0,
            'is_active' => true,
        ]);

        $oldPrice = 100.0;
        $newPrice = 80.0;

        // Mock Notification facade to throw exception
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Notification service unavailable'))
        ;

        // Mock Log facade to verify error logging
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send price drop notifications', \Mockery::type('array'))
        ;

        // Act - should not throw exception, should handle gracefully
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert - verify no notifications were actually sent due to exception
        Notification::assertNothingSent();
    }

    public function testLogsAuditTrailForNotification(): void
    {
        // Arrange
        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $user = User::factory()->create(['id' => 1, 'email' => 'test@example.com']);
        $oldPrice = 100.0;
        $newPrice = 80.0;
        $targetPrice = 90.0;

        $alert = PriceAlert::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'target_price' => $targetPrice,
            'is_active' => true,
        ]);

        $this->auditService->shouldReceive('logSensitiveOperation')
            ->once()
            ->with('price_drop_notification', \Mockery::on(static function ($passedUser) use ($user) {
                return $passedUser instanceof User && $passedUser->id === $user->id;
            }), \Mockery::on(static function ($data) use ($product, $oldPrice, $newPrice, $targetPrice) {
                return \is_array($data)
                    && $data['product_id'] === $product->id
                    && $data['old_price'] === $oldPrice
                    && $data['new_price'] === $newPrice
                    && $data['target_price'] === $targetPrice;
            }))
        ;

        // Act
        $this->service->sendPriceDropNotification($product, $oldPrice, $newPrice);

        // Assert
        Notification::assertSentTo($user, PriceDropNotification::class);
    }
}
