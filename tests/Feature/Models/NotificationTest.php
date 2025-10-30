<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateANotification(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'price_drop',
            'title' => 'Price Drop Alert',
            'message' => 'The price has dropped!',
            'data' => ['product_id' => 1, 'old_price' => 100, 'new_price' => 80],
            'priority' => 3,
            'channel' => 'email',
            'status' => 'pending',
            'metadata' => ['source' => 'system'],
            'tags' => ['price', 'alert'],
        ]);

        self::assertInstanceOf(Notification::class, $notification);
        self::assertNotNull($notification->user_id);
        self::assertSame('price_drop', $notification->type);
        self::assertSame('Price Drop Alert', $notification->title);
        self::assertSame('The price has dropped!', $notification->message);
        self::assertSame(['product_id' => 1, 'old_price' => 100, 'new_price' => 80], $notification->data);
        self::assertSame(3, $notification->priority);
        self::assertSame('email', $notification->channel);
        self::assertSame('pending', $notification->status);
        self::assertSame(['source' => 'system'], $notification->metadata);
        self::assertSame(['price', 'alert'], $notification->tags);

        // Assert that the notification was actually saved to the database
        $this->assertDatabaseHas('custom_notifications', [
            'user_id' => $user->id,
            'type' => 'price_drop',
            'title' => 'Price Drop Alert',
            'message' => 'The price has dropped!',
            'priority' => 3,
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'data' => ['key' => 'value'],
            'read_at' => now(),
            'sent_at' => now(),
            'priority' => 2,
            'metadata' => ['source' => 'web'],
            'tags' => ['urgent', 'system'],
        ]);

        self::assertIsArray($notification->data);
        self::assertInstanceOf(Carbon::class, $notification->read_at);
        self::assertInstanceOf(Carbon::class, $notification->sent_at);
        self::assertIsInt($notification->priority);
        self::assertIsArray($notification->metadata);
        self::assertIsArray($notification->tags);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        self::assertInstanceOf(User::class, $notification->user);
        self::assertSame($user->id, $notification->user->id);
    }

    #[Test]
    public function testScopeUnreadFiltersUnreadNotifications(): void
    {
        Notification::factory()->create(['read_at' => null]);
        Notification::factory()->create(['read_at' => now()]);
        Notification::factory()->create(['read_at' => null]);

        $unreadNotifications = Notification::unread()->get();

        self::assertCount(2, $unreadNotifications);
        self::assertTrue($unreadNotifications->every(static fn ($n) => null === $n->read_at));
    }

    #[Test]
    public function testScopeReadFiltersReadNotifications(): void
    {
        Notification::factory()->create(['read_at' => null]);
        Notification::factory()->create(['read_at' => now()]);
        Notification::factory()->create(['read_at' => now()->subHour()]);

        $readNotifications = Notification::read()->get();

        self::assertCount(2, $readNotifications);
        self::assertTrue($readNotifications->every(static fn ($n) => null !== $n->read_at));
    }

    #[Test]
    public function testScopeOfTypeFiltersByType(): void
    {
        Notification::factory()->create(['type' => 'price_drop']);
        Notification::factory()->create(['type' => 'new_product']);
        Notification::factory()->create(['type' => 'price_drop']);

        $priceDropNotifications = Notification::ofType('price_drop')->get();
        $newProductNotifications = Notification::ofType('new_product')->get();

        self::assertCount(2, $priceDropNotifications);
        self::assertCount(1, $newProductNotifications);
        self::assertTrue($priceDropNotifications->every(static fn ($n) => 'price_drop' === $n->type));
        self::assertTrue($newProductNotifications->every(static fn ($n) => 'new_product' === $n->type));
    }

    #[Test]
    public function testScopeOfPriorityFiltersByPriority(): void
    {
        Notification::factory()->create(['priority' => 1]);
        Notification::factory()->create(['priority' => 2]);
        Notification::factory()->create(['priority' => 1]);

        $lowPriorityNotifications = Notification::ofPriority(1)->get();
        $normalPriorityNotifications = Notification::ofPriority(2)->get();

        self::assertCount(2, $lowPriorityNotifications);
        self::assertCount(1, $normalPriorityNotifications);
        self::assertTrue($lowPriorityNotifications->every(static fn ($n) => 1 === $n->priority));
        self::assertTrue($normalPriorityNotifications->every(static fn ($n) => 2 === $n->priority));
    }

    #[Test]
    public function testScopeOfStatusFiltersByStatus(): void
    {
        Notification::factory()->create(['status' => 'pending']);
        Notification::factory()->create(['status' => 'sent']);
        Notification::factory()->create(['status' => 'pending']);

        $pendingNotifications = Notification::ofStatus('pending')->get();
        $sentNotifications = Notification::ofStatus('sent')->get();

        self::assertCount(2, $pendingNotifications);
        self::assertCount(1, $sentNotifications);
        self::assertTrue($pendingNotifications->every(static fn ($n) => 'pending' === $n->status));
        self::assertTrue($sentNotifications->every(static fn ($n) => 'sent' === $n->status));
    }

    #[Test]
    public function testScopeSentFiltersSentNotifications(): void
    {
        Notification::factory()->create(['sent_at' => now()]);
        Notification::factory()->create(['sent_at' => null]);
        Notification::factory()->create(['sent_at' => now()->subHour()]);

        $sentNotifications = Notification::sent()->get();

        self::assertCount(2, $sentNotifications);
        self::assertTrue($sentNotifications->every(static fn ($n) => null !== $n->sent_at));
    }

    #[Test]
    public function testScopePendingFiltersPendingNotifications(): void
    {
        Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);
        Notification::factory()->create(['sent_at' => now(), 'status' => 'sent']);
        Notification::factory()->create(['sent_at' => null, 'status' => 'failed']);

        $pendingNotifications = Notification::pending()->get();

        self::assertCount(1, $pendingNotifications);
        self::assertTrue($pendingNotifications->every(static fn ($n) => null === $n->sent_at && 'pending' === $n->status));
    }

    #[Test]
    public function testScopeFailedFiltersFailedNotifications(): void
    {
        Notification::factory()->create(['status' => 'failed']);
        Notification::factory()->create(['status' => 'sent']);
        Notification::factory()->create(['status' => 'failed']);

        $failedNotifications = Notification::failed()->get();

        self::assertCount(2, $failedNotifications);
        self::assertTrue($failedNotifications->every(static fn ($n) => 'failed' === $n->status));
    }

    #[Test]
    public function testScopeForUserFiltersByUserId(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        Notification::factory()->create(['user_id' => $user1->id]);
        Notification::factory()->create(['user_id' => $user2->id]);
        Notification::factory()->create(['user_id' => $user1->id]);

        $user1Notifications = Notification::forUser($user1->id)->get();
        $user2Notifications = Notification::forUser($user2->id)->get();

        self::assertCount(2, $user1Notifications);
        self::assertCount(1, $user2Notifications);
        self::assertTrue($user1Notifications->every(static fn ($n) => $n->user_id === $user1->id));
        self::assertTrue($user2Notifications->every(static fn ($n) => $n->user_id === $user2->id));
    }

    #[Test]
    public function testScopeAfterFiltersNotificationsAfterDate(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        Notification::factory()->create(['created_at' => $yesterday]);
        Notification::factory()->create(['created_at' => $now]);
        Notification::factory()->create(['created_at' => $tomorrow]);

        $notificationsAfter = Notification::after($yesterday)->get();

        self::assertCount(2, $notificationsAfter);
        self::assertTrue($notificationsAfter->every(static fn ($n) => $n->created_at->gt($yesterday)));
    }

    #[Test]
    public function testScopeBeforeFiltersNotificationsBeforeDate(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        Notification::factory()->create(['created_at' => $yesterday]);
        Notification::factory()->create(['created_at' => $now]);
        Notification::factory()->create(['created_at' => $tomorrow]);

        $notificationsBefore = Notification::before($tomorrow)->get();

        self::assertCount(2, $notificationsBefore);
        self::assertTrue($notificationsBefore->every(static fn ($n) => $n->created_at->lt($tomorrow)));
    }

    #[Test]
    public function testScopeBetweenFiltersNotificationsBetweenDates(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();
        $dayAfterTomorrow = $now->copy()->addDays(2);

        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $yesterday]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $now]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $tomorrow]);
        Notification::factory()->create(['user_id' => $user->id, 'created_at' => $dayAfterTomorrow]);

        $notificationsBetween = Notification::between($yesterday, $tomorrow)->get();

        self::assertCount(3, $notificationsBetween);
        // Check that we have the expected notifications
        self::assertTrue($notificationsBetween->count() >= 3);
    }

    #[Test]
    public function testMarkAsRead(): void
    {
        $notification = Notification::factory()->create(['read_at' => null]);

        $result = $notification->markAsRead();

        self::assertTrue($result);
        self::assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function testMarkAsUnread(): void
    {
        $notification = Notification::factory()->create(['read_at' => now()]);

        $result = $notification->markAsUnread();

        self::assertTrue($result);
        self::assertNull($notification->fresh()->read_at);
    }

    #[Test]
    public function testMarkAsSent(): void
    {
        $notification = Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);

        $result = $notification->markAsSent();

        self::assertTrue($result);
        self::assertNotNull($notification->fresh()->sent_at);
        self::assertSame('sent', $notification->fresh()->status);
    }

    #[Test]
    public function testMarkAsFailed(): void
    {
        $notification = Notification::factory()->create(['status' => 'pending']);

        $result = $notification->markAsFailed('Connection timeout');

        self::assertTrue($result);
        self::assertSame('failed', $notification->fresh()->status);
        self::assertSame('Connection timeout', $notification->fresh()->getData('failure_reason'));
    }

    #[Test]
    public function testIsRead(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        self::assertTrue($readNotification->isRead());
        self::assertFalse($unreadNotification->isRead());
    }

    #[Test]
    public function testIsUnread(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        self::assertFalse($readNotification->isUnread());
        self::assertTrue($unreadNotification->isUnread());
    }

    #[Test]
    public function testIsSent(): void
    {
        $sentNotification = Notification::factory()->create(['sent_at' => now()]);
        $unsentNotification = Notification::factory()->create(['sent_at' => null]);

        self::assertTrue($sentNotification->isSent());
        self::assertFalse($unsentNotification->isSent());
    }

    #[Test]
    public function testIsPending(): void
    {
        $pendingNotification = Notification::factory()->create(['sent_at' => null, 'status' => 'pending']);
        $sentNotification = Notification::factory()->create(['sent_at' => now(), 'status' => 'sent']);
        $failedNotification = Notification::factory()->create(['sent_at' => null, 'status' => 'failed']);

        self::assertTrue($pendingNotification->isPending());
        self::assertFalse($sentNotification->isPending());
        self::assertFalse($failedNotification->isPending());
    }

    #[Test]
    public function testIsFailed(): void
    {
        $failedNotification = Notification::factory()->create(['status' => 'failed']);
        $sentNotification = Notification::factory()->create(['status' => 'sent']);

        self::assertTrue($failedNotification->isFailed());
        self::assertFalse($sentNotification->isFailed());
    }

    #[Test]
    public function testGetPriorityLevel(): void
    {
        $lowPriority = Notification::factory()->create(['priority' => 1]);
        $normalPriority = Notification::factory()->create(['priority' => 2]);
        $highPriority = Notification::factory()->create(['priority' => 3]);
        $urgentPriority = Notification::factory()->create(['priority' => 4]);
        $unknownPriority = Notification::factory()->create(['priority' => 5]);

        self::assertSame('low', $lowPriority->getPriorityLevel());
        self::assertSame('normal', $normalPriority->getPriorityLevel());
        self::assertSame('high', $highPriority->getPriorityLevel());
        self::assertSame('urgent', $urgentPriority->getPriorityLevel());
        self::assertSame('normal', $unknownPriority->getPriorityLevel());
    }

    #[Test]
    public function testGetIconByType(): void
    {
        $priceDrop = Notification::factory()->create(['type' => 'price_drop']);
        $newProduct = Notification::factory()->create(['type' => 'new_product']);
        $system = Notification::factory()->create(['type' => 'system']);
        $custom = Notification::factory()->create(['type' => 'custom_type']);

        self::assertSame('💰', $priceDrop->getIcon());
        self::assertSame('🆕', $newProduct->getIcon());
        self::assertSame('⚙️', $system->getIcon());
        self::assertSame('📢', $custom->getIcon());
    }

    #[Test]
    public function testGetTypeDisplayName(): void
    {
        $priceDrop = Notification::factory()->create(['type' => 'price_drop']);
        $newProduct = Notification::factory()->create(['type' => 'new_product']);
        $system = Notification::factory()->create(['type' => 'system']);
        $custom = Notification::factory()->create(['type' => 'custom_type']);

        self::assertSame('Price Drop Alert', $priceDrop->getTypeDisplayName());
        self::assertSame('New Product', $newProduct->getTypeDisplayName());
        self::assertSame('System Notification', $system->getTypeDisplayName());
        self::assertSame('Custom type', $custom->getTypeDisplayName());
    }

    #[Test]
    public function testGetChannelDisplayName(): void
    {
        $email = Notification::factory()->create(['channel' => 'email']);
        $sms = Notification::factory()->create(['channel' => 'sms']);
        $push = Notification::factory()->create(['channel' => 'push']);
        $custom = Notification::factory()->create(['channel' => 'custom_channel']);

        self::assertSame('Email', $email->getChannelDisplayName());
        self::assertSame('SMS', $sms->getChannelDisplayName());
        self::assertSame('Push Notification', $push->getChannelDisplayName());
        self::assertSame('Custom_channel', $custom->getChannelDisplayName());
    }

    #[Test]
    public function testGetStatusDisplayName(): void
    {
        $pending = Notification::factory()->create(['status' => 'pending']);
        $sent = Notification::factory()->create(['status' => 'sent']);
        $failed = Notification::factory()->create(['status' => 'failed']);
        $custom = Notification::factory()->create(['status' => 'custom_status']);

        self::assertSame('Pending', $pending->getStatusDisplayName());
        self::assertSame('Sent', $sent->getStatusDisplayName());
        self::assertSame('Failed', $failed->getStatusDisplayName());
        self::assertSame('Custom_status', $custom->getStatusDisplayName());
    }

    #[Test]
    public function testGetTimeAgo(): void
    {
        $notification = Notification::factory()->create(['created_at' => now()->subHour()]);

        self::assertStringContainsString('1 hour ago', $notification->getTimeAgo());
    }

    #[Test]
    public function testGetReadTimeAgo(): void
    {
        $readNotification = Notification::factory()->create(['read_at' => now()->subMinutes(30)]);
        $unreadNotification = Notification::factory()->create(['read_at' => null]);

        self::assertStringContainsString('30 minutes ago', $readNotification->getReadTimeAgo());
        self::assertNull($unreadNotification->getReadTimeAgo());
    }

    #[Test]
    public function testGetSentTimeAgo(): void
    {
        $sentNotification = Notification::factory()->create(['sent_at' => now()->subMinutes(15)]);
        $unsentNotification = Notification::factory()->create(['sent_at' => null]);

        self::assertStringContainsString('15 minutes ago', $sentNotification->getSentTimeAgo());
        self::assertNull($unsentNotification->getSentTimeAgo());
    }

    #[Test]
    public function testGetData(): void
    {
        $notification = Notification::factory()->create([
            'data' => ['product_id' => 1, 'price' => 100, 'nested' => ['key' => 'value']],
        ]);

        self::assertSame(['product_id' => 1, 'price' => 100, 'nested' => ['key' => 'value']], $notification->getData());
        self::assertSame(1, $notification->getData('product_id'));
        self::assertSame(100, $notification->getData('price'));
        self::assertSame('value', $notification->getData('nested.key'));
        self::assertSame('default', $notification->getData('nonexistent', 'default'));
    }

    #[Test]
    public function testSetData(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setData('new_key', 'new_value');

        self::assertTrue($result);
        self::assertSame('new_value', $notification->fresh()->getData('new_key'));
    }

    #[Test]
    public function testGetIcon(): void
    {
        $priceDrop = Notification::factory()->create(['type' => 'price_drop']);
        $newProduct = Notification::factory()->create(['type' => 'new_product']);
        $system = Notification::factory()->create(['type' => 'system']);
        $custom = Notification::factory()->create(['type' => 'custom_type']);

        self::assertSame('💰', $priceDrop->getIcon());
        self::assertSame('🆕', $newProduct->getIcon());
        self::assertSame('⚙️', $system->getIcon());
        self::assertSame('📢', $custom->getIcon());
    }

    #[Test]
    public function testGetColor(): void
    {
        $lowPriority = Notification::factory()->create(['priority' => 1]);
        $normalPriority = Notification::factory()->create(['priority' => 2]);
        $highPriority = Notification::factory()->create(['priority' => 3]);
        $urgentPriority = Notification::factory()->create(['priority' => 4]);

        self::assertSame('gray', $lowPriority->getColor());
        self::assertSame('blue', $normalPriority->getColor());
        self::assertSame('orange', $highPriority->getColor());
        self::assertSame('red', $urgentPriority->getColor());
    }

    #[Test]
    public function testGetBadgeText(): void
    {
        $unread = Notification::factory()->create(['read_at' => null]);
        $readFailed = Notification::factory()->create(['read_at' => now(), 'status' => 'failed']);
        $readPending = Notification::factory()->create(['read_at' => now(), 'sent_at' => null, 'status' => 'pending']);
        $readSent = Notification::factory()->create(['read_at' => now(), 'sent_at' => now(), 'status' => 'sent']);

        self::assertSame('New', $unread->getBadgeText());
        self::assertSame('Failed', $readFailed->getBadgeText());
        self::assertSame('Pending', $readPending->getBadgeText());
        self::assertSame('', $readSent->getBadgeText());
    }

    #[Test]
    public function testGetSummary(): void
    {
        $longMessage = str_repeat('This is a very long message that should be truncated. ', 10);
        $notification = Notification::factory()->create(['message' => $longMessage]);

        $summary = $notification->getSummary(50);
        self::assertLessThanOrEqual(53, \strlen($summary)); // 50 + '...'
        self::assertStringEndsWith('...', $summary);
    }

    #[Test]
    public function testGetUrl(): void
    {
        $notificationWithUrl = Notification::factory()->create(['data' => ['url' => 'https://example.com']]);
        $notificationWithoutUrl = Notification::factory()->create(['data' => []]);

        self::assertSame('https://example.com', $notificationWithUrl->getUrl());
        self::assertNull($notificationWithoutUrl->getUrl());
    }

    #[Test]
    public function testGetActionText(): void
    {
        $notificationWithAction = Notification::factory()->create(['data' => ['action_text' => 'View Product']]);
        $notificationWithoutAction = Notification::factory()->create(['data' => []]);

        self::assertSame('View Product', $notificationWithAction->getActionText());
        self::assertSame('View Details', $notificationWithoutAction->getActionText());
    }

    #[Test]
    public function testHasAction(): void
    {
        $notificationWithAction = Notification::factory()->create(['data' => ['url' => 'https://example.com']]);
        $notificationWithoutAction = Notification::factory()->create(['data' => []]);

        self::assertTrue($notificationWithAction->hasAction());
        self::assertFalse($notificationWithoutAction->hasAction());
    }

    #[Test]
    public function testGetExpirationDate(): void
    {
        $notificationWithExpiration = Notification::factory()->create([
            'data' => ['expiration_days' => 7],
            'created_at' => now(),
        ]);
        $notificationWithoutExpiration = Notification::factory()->create(['data' => []]);

        self::assertInstanceOf(Carbon::class, $notificationWithExpiration->getExpirationDate());
        self::assertNull($notificationWithoutExpiration->getExpirationDate());
    }

    #[Test]
    public function testIsExpired(): void
    {
        $expiredNotification = Notification::factory()->create([
            'data' => ['expiration_days' => 1],
            'created_at' => now()->subDays(2),
        ]);
        $validNotification = Notification::factory()->create([
            'data' => ['expiration_days' => 7],
            'created_at' => now(),
        ]);

        self::assertTrue($expiredNotification->isExpired());
        self::assertFalse($validNotification->isExpired());
    }

    #[Test]
    public function testGetRetryCount(): void
    {
        $notificationWithRetries = Notification::factory()->create(['data' => ['retry_count' => 3]]);
        $notificationWithoutRetries = Notification::factory()->create(['data' => []]);

        self::assertSame(3, $notificationWithRetries->getRetryCount());
        self::assertSame(0, $notificationWithoutRetries->getRetryCount());
    }

    #[Test]
    public function testIncrementRetryCount(): void
    {
        $notification = Notification::factory()->create(['data' => ['retry_count' => 2]]);

        $result = $notification->incrementRetryCount();

        self::assertTrue($result);
        self::assertSame(3, $notification->fresh()->getRetryCount());
    }

    #[Test]
    public function testCanRetry(): void
    {
        $failedNotification = Notification::factory()->create(['status' => 'failed', 'data' => ['retry_count' => 2]]);
        $maxRetriesReached = Notification::factory()->create(['status' => 'failed', 'data' => ['retry_count' => 3]]);
        $sentNotification = Notification::factory()->create(['status' => 'sent']);

        self::assertTrue($failedNotification->canRetry(3));
        self::assertFalse($maxRetriesReached->canRetry(3));
        self::assertFalse($sentNotification->canRetry(3));
    }

    #[Test]
    public function testGetFailureReason(): void
    {
        $notificationWithReason = Notification::factory()->create(['data' => ['failure_reason' => 'Connection timeout']]);
        $notificationWithoutReason = Notification::factory()->create(['data' => []]);

        self::assertSame('Connection timeout', $notificationWithReason->getFailureReason());
        self::assertNull($notificationWithoutReason->getFailureReason());
    }

    #[Test]
    public function testGetMetadata(): void
    {
        $notification = Notification::factory()->create(['data' => ['metadata' => ['source' => 'web', 'version' => '1.0']]]);

        self::assertSame(['source' => 'web', 'version' => '1.0'], $notification->getMetadata());
    }

    #[Test]
    public function testSetMetadata(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setMetadata(['source' => 'api', 'version' => '2.0']);

        self::assertTrue($result);
        self::assertSame(['source' => 'api', 'version' => '2.0'], $notification->fresh()->getMetadata());
    }

    #[Test]
    public function testGetTags(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system', 'price']]]);

        self::assertSame(['urgent', 'system', 'price'], $notification->getTags());
    }

    #[Test]
    public function testSetTags(): void
    {
        $notification = Notification::factory()->create(['data' => []]);

        $result = $notification->setTags(['urgent', 'system']);

        self::assertTrue($result);
        self::assertSame(['urgent', 'system'], $notification->fresh()->getTags());
    }

    #[Test]
    public function testAddTag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent']]]);

        $result = $notification->addTag('system');

        self::assertTrue($result);
        self::assertSame(['urgent', 'system'], $notification->fresh()->getTags());
    }

    #[Test]
    public function testAddExistingTag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent']]]);

        $result = $notification->addTag('urgent');

        self::assertTrue($result);
        self::assertSame(['urgent'], $notification->fresh()->getTags());
    }

    #[Test]
    public function testRemoveTag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system']]]);

        $result = $notification->removeTag('urgent');

        self::assertTrue($result);
        self::assertSame(['system'], $notification->fresh()->getTags());
    }

    #[Test]
    public function testHasTag(): void
    {
        $notification = Notification::factory()->create(['data' => ['tags' => ['urgent', 'system']]]);

        self::assertTrue($notification->hasTag('urgent'));
        self::assertTrue($notification->hasTag('system'));
        self::assertFalse($notification->hasTag('price'));
    }

    #[Test]
    public function testFactoryCreatesValidNotification(): void
    {
        $notification = Notification::factory()->make();

        self::assertInstanceOf(Notification::class, $notification);
        self::assertNotEmpty($notification->type);
        self::assertNotEmpty($notification->title);
        self::assertNotEmpty($notification->message);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'type',
            'title',
            'message',
            'data',
            'read_at',
            'sent_at',
            'priority',
            'channel',
            'status',
            'metadata',
            'tags',
        ];

        self::assertSame($fillable, (new Notification())->getFillable());
    }

    #[Test]
    public function testHiddenAttributes(): void
    {
        $hidden = ['data'];

        self::assertSame($hidden, (new Notification())->getHidden());
    }
}
