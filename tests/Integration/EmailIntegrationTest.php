<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\PriceDropNotification;
use App\Notifications\ProductAddedNotification;
use App\Notifications\ReviewNotification;
use App\Notifications\SystemNotification;
use App\Services\NotificationService;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private PasswordResetService $passwordResetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = app(NotificationService::class);
        $this->passwordResetService = app(PasswordResetService::class);

        // Fake mail and notifications for testing
        Mail::fake();
        Notification::fake();
    }

    public function testItSendsPriceDropNotificationSuccessfully(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create(['price' => 100.00]);
        $priceAlert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 80.00,
            'is_active' => true,
        ]);

        // Act
        $this->notificationService->sendPriceDropNotification($product, 100.00, 75.00);

        // Assert
        Notification::assertSentTo(
            $user,
            PriceDropNotification::class,
            static function ($notification) use ($product) {
                $data = $notification->toArray();

                return $data['product_id'] === $product->id
                    && 100.00 === $data['old_price']
                    && 75.00 === $data['new_price']
                    && 80.00 === $data['target_price'];
            }
        );
    }

    public function testItDoesNotSendPriceDropNotificationForInactiveAlerts(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create(['price' => 100.00]);
        PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 80.00,
            'is_active' => false, // Inactive alert
        ]);

        // Act
        $this->notificationService->sendPriceDropNotification($product, 100.00, 75.00);

        // Assert
        Notification::assertNotSentTo($user, PriceDropNotification::class);
    }

    public function testItSendsProductAddedNotificationToAdmins(): void
    {
        // Arrange
        $admin1 = User::factory()->create(['is_admin' => true, 'email' => 'admin1@example.com']);
        $admin2 = User::factory()->create(['is_admin' => true, 'email' => 'admin2@example.com']);
        $regularUser = User::factory()->create(['is_admin' => false, 'email' => 'user@example.com']);
        $product = Product::factory()->create();

        // Act
        $this->notificationService->sendProductAddedNotification($product);

        // Assert
        Notification::assertSentTo($admin1, ProductAddedNotification::class);
        Notification::assertSentTo($admin2, ProductAddedNotification::class);
        Notification::assertNotSentTo($regularUser, ProductAddedNotification::class);
    }

    public function testItSendsReviewNotificationToStoreAndAdmins(): void
    {
        // Arrange
        $store = Store::factory()->create(['contact_email' => 'store@example.com']);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $reviewer = User::factory()->create(['email' => 'reviewer@example.com']);
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        $rating = 5;

        // Act
        $this->notificationService->sendReviewNotification($product, $reviewer, $rating);

        // Assert
        Mail::assertSent(ReviewNotification::class, static function ($mail) use ($store) {
            return $mail->hasTo($store->contact_email);
        });

        Notification::assertSentTo($admin, ReviewNotification::class);
    }

    public function testItSendsSystemNotificationToAllUsers(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $title = 'System Maintenance';
        $message = 'The system will be down for maintenance.';

        // Act
        $this->notificationService->sendSystemNotification($title, $message);

        // Assert
        Notification::assertSentTo($user1, SystemNotification::class);
        Notification::assertSentTo($user2, SystemNotification::class);
    }

    public function testItSendsSystemNotificationToSpecificUsers(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $user3 = User::factory()->create(['email' => 'user3@example.com']);
        $title = 'Important Update';
        $message = 'Your account requires attention.';

        // Act
        $this->notificationService->sendSystemNotification($title, $message, [$user1->id, $user2->id]);

        // Assert
        Notification::assertSentTo($user1, SystemNotification::class);
        Notification::assertSentTo($user2, SystemNotification::class);
        Notification::assertNotSentTo($user3, SystemNotification::class);
    }

    public function testItSendsPasswordResetEmailSuccessfully(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com', 'is_blocked' => false]);

        // Act
        $result = $this->passwordResetService->sendResetEmail($user->email);

        // Assert
        self::assertTrue($result);
        Mail::assertSent(Mailable::class, static function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        self::assertTrue($this->passwordResetService->hasResetToken($user->email));
    }

    public function testItDoesNotSendPasswordResetForNonExistentEmail(): void
    {
        // Act
        $result = $this->passwordResetService->sendResetEmail('nonexistent@example.com');

        // Assert
        self::assertFalse($result);
        Mail::assertNothingSent();
        self::assertFalse($this->passwordResetService->hasResetToken('nonexistent@example.com'));
    }

    public function testItDoesNotSendPasswordResetForBlockedUser(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'blocked@example.com', 'is_blocked' => true]);

        // Act
        $result = $this->passwordResetService->sendResetEmail($user->email);

        // Assert
        self::assertFalse($result);
        Mail::assertNothingSent();
        self::assertFalse($this->passwordResetService->hasResetToken($user->email));
    }

    public function testItResetsPasswordWithValidToken(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->passwordResetService->sendResetEmail($user->email);

        // Get the token from cache
        $tokenInfo = $this->passwordResetService->getResetTokenInfo($user->email);
        self::assertNotNull($tokenInfo);

        // Simulate getting the token (in real scenario, it would be from email)
        $cacheKey = 'password_reset:'.hash('sha256', $user->email);
        $tokenData = Cache::get($cacheKey);
        $token = $tokenData['token'];

        $newPassword = 'new-secure-password';

        // Act
        $result = $this->passwordResetService->resetPassword($user->email, $token, $newPassword);

        // Assert
        self::assertTrue($result);
        self::assertFalse($this->passwordResetService->hasResetToken($user->email));

        // Verify password was changed
        $user->refresh();
        self::assertTrue(\Hash::check($newPassword, $user->password));
    }

    public function testItFailsPasswordResetWithInvalidToken(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->passwordResetService->sendResetEmail($user->email);
        $invalidToken = 'invalid-token';
        $newPassword = 'new-secure-password';

        // Act
        $result = $this->passwordResetService->resetPassword($user->email, $invalidToken, $newPassword);

        // Assert
        self::assertFalse($result);
        self::assertTrue($this->passwordResetService->hasResetToken($user->email));
    }

    public function testItProvidesResetTokenInformation(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->passwordResetService->sendResetEmail($user->email);

        // Act
        $tokenInfo = $this->passwordResetService->getResetTokenInfo($user->email);

        // Assert
        self::assertNotNull($tokenInfo);
        self::assertArrayHasKey('created_at', $tokenInfo);
        self::assertArrayHasKey('expires_at', $tokenInfo);
        self::assertArrayHasKey('attempts', $tokenInfo);
        self::assertArrayHasKey('remaining_attempts', $tokenInfo);
        self::assertSame(0, $tokenInfo['attempts']);
        self::assertSame(3, $tokenInfo['remaining_attempts']);
    }

    public function testItBlocksPasswordResetAfterMaxAttempts(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->passwordResetService->sendResetEmail($user->email);
        $invalidToken = 'invalid-token';

        // Act - Make 3 failed attempts
        for ($i = 0; $i < 3; ++$i) {
            $this->passwordResetService->resetPassword($user->email, $invalidToken, 'password');
        }

        // Assert
        self::assertFalse($this->passwordResetService->hasResetToken($user->email));
    }

    public function testItMarksNotificationAsRead(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->notify(new SystemNotification('Test', 'Test message'));
        $notification = $user->notifications()->first();

        // Act
        $result = $this->notificationService->markAsRead($notification->id, $user);

        // Assert
        self::assertTrue($result);
        $notification->refresh();
        self::assertNotNull($notification->read_at);
    }

    public function testItMarksAllNotificationsAsRead(): void
    {
        // Arrange
        $user = User::factory()->create();
        $user->notify(new SystemNotification('Test 1', 'Test message 1'));
        $user->notify(new SystemNotification('Test 2', 'Test message 2'));
        $user->notify(new SystemNotification('Test 3', 'Test message 3'));

        self::assertSame(3, $user->unreadNotifications()->count());

        // Act
        $count = $this->notificationService->markAllAsRead($user);

        // Assert
        self::assertSame(3, $count);
        self::assertSame(0, $user->unreadNotifications()->count());
    }

    public function testItHandlesEmailSendingFailuresGracefully(): void
    {
        // Arrange
        Mail::shouldReceive('send')->andThrow(new \Exception('SMTP connection failed'));
        $user = User::factory()->create(['email' => 'user@example.com']);

        // Act
        $result = $this->passwordResetService->sendResetEmail($user->email);

        // Assert
        self::assertFalse($result);
    }

    public function testItProvidesPasswordResetStatistics(): void
    {
        // Act
        $stats = $this->passwordResetService->getStatistics();

        // Assert
        self::assertArrayHasKey('token_expiry_minutes', $stats);
        self::assertArrayHasKey('max_attempts', $stats);
        self::assertArrayHasKey('expired_tokens_cleaned', $stats);
        self::assertSame(60, $stats['token_expiry_minutes']);
        self::assertSame(3, $stats['max_attempts']);
        self::assertIsInt($stats['expired_tokens_cleaned']);
    }

    public function testItHandlesMultiplePriceAlertsForSameProduct(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product = Product::factory()->create(['price' => 100.00]);

        PriceAlert::factory()->create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'target_price' => 80.00,
            'is_active' => true,
        ]);

        PriceAlert::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'target_price' => 85.00,
            'is_active' => true,
        ]);

        // Act
        $this->notificationService->sendPriceDropNotification($product, 100.00, 75.00);

        // Assert
        Notification::assertSentTo($user1, PriceDropNotification::class);
        Notification::assertSentTo($user2, PriceDropNotification::class);
    }

    public function testItHandlesComprehensiveEmailWorkflow(): void
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);
        $store = Store::factory()->create(['contact_email' => 'store@example.com']);
        $product = Product::factory()->create(['store_id' => $store->id, 'price' => 100.00]);

        PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 80.00,
            'is_active' => true,
        ]);

        // Act & Assert - Complete workflow

        // 1. Product added notification
        $this->notificationService->sendProductAddedNotification($product);
        Notification::assertSentTo($admin, ProductAddedNotification::class);

        // 2. Price drop notification
        $this->notificationService->sendPriceDropNotification($product, 100.00, 75.00);
        Notification::assertSentTo($user, PriceDropNotification::class);

        // 3. Review notification
        $this->notificationService->sendReviewNotification($product, $user, 5);
        Mail::assertSent(ReviewNotification::class);
        Notification::assertSentTo($admin, ReviewNotification::class);

        // 4. System notification
        $this->notificationService->sendSystemNotification('Maintenance', 'System will be down');
        Notification::assertSentTo($admin, SystemNotification::class);
        Notification::assertSentTo($user, SystemNotification::class);

        // 5. Password reset
        $result = $this->passwordResetService->sendResetEmail($user->email);
        self::assertTrue($result);
        Mail::assertSent(Mailable::class);

        // 6. Mark notifications as read
        $count = $this->notificationService->markAllAsRead($user);
        self::assertGreaterThan(0, $count);
    }
}
