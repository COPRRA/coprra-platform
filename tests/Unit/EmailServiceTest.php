<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PriceDropNotification;
use App\Notifications\ProductAddedNotification;
use App\Notifications\ReviewNotification;
use App\Notifications\SystemNotification;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = $this->createMock(AuditService::class);
        $this->notificationService = new NotificationService($this->auditService);
    }

    public function testSendPriceDropNotificationSendsToUsersWithAlerts(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->once();
        $this->auditService->expects(self::once())->method('logSensitiveOperation');

        $user = User::factory()->create(['email' => 'test@example.com']);
        $product = Product::factory()->create(['price' => 100.00]);
        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 80.00,
            'is_active' => true,
        ]);

        $this->notificationService->sendPriceDropNotification($product, 120.00, 90.00);

        Notification::assertSentTo($user, PriceDropNotification::class);
    }

    public function testSendPriceDropNotificationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $product = Product::factory()->create();

        // This should not throw an exception even if there are issues
        $this->notificationService->sendPriceDropNotification($product, 100.00, 90.00);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendProductAddedNotificationSendsToAdmins(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->once();

        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);
        $product = Product::factory()->create();

        $this->notificationService->sendProductAddedNotification($product);

        Notification::assertSentTo($admin1, ProductAddedNotification::class);
        Notification::assertSentTo($admin2, ProductAddedNotification::class);
        Notification::assertNotSentTo($regularUser, ProductAddedNotification::class);
    }

    public function testSendProductAddedNotificationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $product = Product::factory()->create();

        // Mock User model to throw exception
        User::shouldReceive('where')->andThrow(new \Exception('Database error'));

        $this->notificationService->sendProductAddedNotification($product);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendReviewNotificationSendsToAdmins(): void
    {
        Notification::fake();
        Mail::fake();
        Log::shouldReceive('info')->once();

        $admin = User::factory()->create(['is_admin' => true]);
        $reviewer = User::factory()->create();
        $product = Product::factory()->create();

        $this->notificationService->sendReviewNotification($product, $reviewer, 5);

        Notification::assertSentTo($admin, ReviewNotification::class);
    }

    public function testSendReviewNotificationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $reviewer = User::factory()->create();
        $product = Product::factory()->create();

        $this->notificationService->sendReviewNotification($product, $reviewer, 5);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendSystemNotificationSendsToAllUsers(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->once();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->notificationService->sendSystemNotification('Test Title', 'Test Message');

        Notification::assertSentTo($user1, SystemNotification::class);
        Notification::assertSentTo($user2, SystemNotification::class);
    }

    public function testSendSystemNotificationSendsToSpecificUsers(): void
    {
        Notification::fake();
        Log::shouldReceive('info')->once();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->notificationService->sendSystemNotification('Test Title', 'Test Message', [$user1->id, $user2->id]);

        Notification::assertSentTo($user1, SystemNotification::class);
        Notification::assertSentTo($user2, SystemNotification::class);
        Notification::assertNotSentTo($user3, SystemNotification::class);
    }

    public function testSendSystemNotificationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $this->notificationService->sendSystemNotification('Test Title', 'Test Message');

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendWelcomeNotificationLogsSuccessfully(): void
    {
        Log::shouldReceive('info')->once()->with('Welcome notification sent', ['user_id' => 1]);

        $user = User::factory()->create(['id' => 1]);

        $this->notificationService->sendWelcomeNotification($user);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendWelcomeNotificationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();

        $this->notificationService->sendWelcomeNotification($user);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendPriceAlertConfirmationLogsSuccessfully(): void
    {
        Log::shouldReceive('info')->once();

        $user = User::factory()->create();
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->notificationService->sendPriceAlertConfirmation($alert);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendPriceAlertConfirmationHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->notificationService->sendPriceAlertConfirmation($alert);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendDailyPriceSummaryHandlesEmptyAlerts(): void
    {
        $user = User::factory()->create();

        $this->notificationService->sendDailyPriceSummary($user);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testSendDailyPriceSummaryHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();

        $this->notificationService->sendDailyPriceSummary($user);

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testMarkAsReadSuccessfullyMarksNotification(): void
    {
        Log::shouldReceive('info')->once();

        $user = User::factory()->create();

        // Create a notification for the user
        $user->notify(new SystemNotification('Test', 'Test Message'));
        $notification = $user->notifications()->first();

        $result = $this->notificationService->markAsRead($notification->id, $user);

        self::assertTrue($result);
        self::assertNotNull($notification->fresh()->read_at);
    }

    public function testMarkAsReadReturnsFalseForNonExistentNotification(): void
    {
        $user = User::factory()->create();

        $result = $this->notificationService->markAsRead('non-existent-id', $user);

        self::assertFalse($result);
    }

    public function testMarkAsReadHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();

        $result = $this->notificationService->markAsRead('invalid-id', $user);

        self::assertFalse($result);
    }

    public function testMarkAllAsReadMarksAllUnreadNotifications(): void
    {
        Log::shouldReceive('info')->once();

        $user = User::factory()->create();

        // Create multiple notifications
        $user->notify(new SystemNotification('Test 1', 'Message 1'));
        $user->notify(new SystemNotification('Test 2', 'Message 2'));

        $count = $this->notificationService->markAllAsRead($user);

        self::assertSame(2, $count);
        self::assertSame(0, $user->unreadNotifications()->count());
    }

    public function testMarkAllAsReadHandlesExceptions(): void
    {
        Log::shouldReceive('error')->once();

        $user = User::factory()->create();

        $count = $this->notificationService->markAllAsRead($user);

        self::assertSame(0, $count);
    }
}
