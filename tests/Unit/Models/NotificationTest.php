<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Notification model.
 *
 * @internal
 */
#[CoversClass(Notification::class)]
final class NotificationTest extends TestCase
{
    /**
     * Test table name.
     */
    public function testTableName(): void
    {
        self::assertSame('custom_notifications', (new Notification())->getTable());
    }

    /**
     * Test fillable attributes.
     */
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
            'created_at',
        ];

        self::assertSame($fillable, (new Notification())->getFillable());
    }

    /**
     * Test casts.
     */
    public function testCasts(): void
    {
        $casts = [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'priority' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
        ];

        self::assertSame($casts, (new Notification())->getCasts());
    }

    /**
     * Test hidden attributes.
     */
    public function testHiddenAttributes(): void
    {
        $hidden = ['data'];

        self::assertSame($hidden, (new Notification())->getHidden());
    }

    /**
     * Test user relation is a BelongsTo instance.
     */
    public function testUserRelation(): void
    {
        $notification = new Notification();

        $relation = $notification->user();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(User::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeUnread applies correct where clause.
     */
    public function testScopeUnread(): void
    {
        $query = Notification::query()->unread();

        self::assertSame('select * from "custom_notifications" where "read_at" is null', $query->toSql());
    }

    /**
     * Test scopeRead applies correct where clause.
     */
    public function testScopeRead(): void
    {
        $query = Notification::query()->read();

        self::assertSame('select * from "custom_notifications" where "read_at" is not null', $query->toSql());
    }

    /**
     * Test scopeOfType applies correct where clause.
     */
    public function testScopeOfType(): void
    {
        $query = Notification::query()->ofType('price_drop');

        self::assertSame('select * from "custom_notifications" where "type" = ?', $query->toSql());
        self::assertSame(['price_drop'], $query->getBindings());
    }

    /**
     * Test scopeOfPriority applies correct where clause.
     */
    public function testScopeOfPriority(): void
    {
        $query = Notification::query()->ofPriority(3);

        self::assertSame('select * from "custom_notifications" where "priority" = ?', $query->toSql());
        self::assertSame([3], $query->getBindings());
    }

    /**
     * Test scopeOfStatus applies correct where clause.
     */
    public function testScopeOfStatus(): void
    {
        $query = Notification::query()->ofStatus('sent');

        self::assertSame('select * from "custom_notifications" where "status" = ?', $query->toSql());
        self::assertSame(['sent'], $query->getBindings());
    }

    /**
     * Test scopeSent applies correct where clause.
     */
    public function testScopeSent(): void
    {
        $query = Notification::query()->sent();

        self::assertSame('select * from "custom_notifications" where "sent_at" is not null', $query->toSql());
    }

    /**
     * Test scopePending applies correct where clause.
     */
    public function testScopePending(): void
    {
        $query = Notification::query()->pending();

        self::assertSame('select * from "custom_notifications" where "sent_at" is null and "status" = ?', $query->toSql());
        self::assertSame(['pending'], $query->getBindings());
    }

    /**
     * Test scopeFailed applies correct where clause.
     */
    public function testScopeFailed(): void
    {
        $query = Notification::query()->failed();

        self::assertSame('select * from "custom_notifications" where "status" = ?', $query->toSql());
        self::assertSame(['failed'], $query->getBindings());
    }

    /**
     * Test scopeForUser applies correct where clause.
     */
    public function testScopeForUser(): void
    {
        $query = Notification::query()->forUser(1);

        self::assertSame('select * from "custom_notifications" where "user_id" = ?', $query->toSql());
        self::assertSame([1], $query->getBindings());
    }

    /**
     * Test scopeAfter applies correct where clause.
     */
    public function testScopeAfter(): void
    {
        $date = Carbon::now();

        $query = Notification::query()->after($date);

        self::assertSame('select * from "custom_notifications" where "created_at" > ?', $query->toSql());
        self::assertSame([$date], $query->getBindings());
    }

    /**
     * Test scopeBefore applies correct where clause.
     */
    public function testScopeBefore(): void
    {
        $date = Carbon::now();

        $query = Notification::query()->before($date);

        self::assertSame('select * from "custom_notifications" where "created_at" < ?', $query->toSql());
        self::assertSame([$date], $query->getBindings());
    }

    /**
     * Test scopeBetween applies correct where clause.
     */
    public function testScopeBetween(): void
    {
        $start = Carbon::now()->subDay();
        $end = Carbon::now();

        $query = Notification::query()->between($start, $end);

        self::assertSame('select * from "custom_notifications" where "created_at" between ? and ?', $query->toSql());
        self::assertSame([$start, $end], $query->getBindings());
    }

    /**
     * Test isRead returns true when read_at is set.
     */
    public function testIsReadReturnsTrueWhenReadAtSet(): void
    {
        $notification = new Notification(['read_at' => Carbon::now()]);

        self::assertTrue($notification->isRead());
    }

    /**
     * Test isRead returns false when read_at is null.
     */
    public function testIsReadReturnsFalseWhenReadAtNull(): void
    {
        $notification = new Notification();

        self::assertFalse($notification->isRead());
    }

    /**
     * Test isUnread returns true when read_at is null.
     */
    public function testIsUnreadReturnsTrueWhenReadAtNull(): void
    {
        $notification = new Notification();

        self::assertTrue($notification->isUnread());
    }

    /**
     * Test isUnread returns false when read_at is set.
     */
    public function testIsUnreadReturnsFalseWhenReadAtSet(): void
    {
        $notification = new Notification(['read_at' => Carbon::now()]);

        self::assertFalse($notification->isUnread());
    }

    /**
     * Test isSent returns true when sent_at is set.
     */
    public function testIsSentReturnsTrueWhenSentAtSet(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', Carbon::now());

        self::assertTrue($notification->isSent());
    }

    /**
     * Test isSent returns false when sent_at is null.
     */
    public function testIsSentReturnsFalseWhenSentAtNull(): void
    {
        $notification = new Notification();

        self::assertFalse($notification->isSent());
    }

    /**
     * Test isPending returns true when sent_at is null and status is pending.
     */
    public function testIsPendingReturnsTrue(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'pending');

        self::assertTrue($notification->isPending());
    }

    /**
     * Test isPending returns false when sent_at is set.
     */
    public function testIsPendingReturnsFalseWhenSentAtSet(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', Carbon::now());
        $notification->setAttribute('status', 'pending');

        self::assertFalse($notification->isPending());
    }

    /**
     * Test isFailed returns true when status is failed.
     */
    public function testIsFailedReturnsTrue(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'failed');

        self::assertTrue($notification->isFailed());
    }

    /**
     * Test isFailed returns false when status is not failed.
     */
    public function testIsFailedReturnsFalse(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'sent');

        self::assertFalse($notification->isFailed());
    }

    /**
     * Test getPriorityLevel returns correct level.
     */
    public function testGetPriorityLevel(): void
    {
        $notification = new Notification();
        $notification->setAttribute('priority', 1);
        self::assertSame('low', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 2);
        self::assertSame('normal', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 3);
        self::assertSame('high', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 4);
        self::assertSame('urgent', $notification->getPriorityLevel());

        $notification->setAttribute('priority', 5);
        self::assertSame('normal', $notification->getPriorityLevel());
    }

    /**
     * Test getTypeDisplayName returns correct name.
     */
    public function testGetTypeDisplayName(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        self::assertSame('Price Drop Alert', $notification->getTypeDisplayName());

        $notification->type = 'unknown_type';
        self::assertSame('Unknown type', $notification->getTypeDisplayName());
    }

    /**
     * Test getChannelDisplayName returns correct name.
     */
    public function testGetChannelDisplayName(): void
    {
        $notification = new Notification();
        $notification->setAttribute('channel', 'email');
        self::assertSame('Email', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'unknown');
        self::assertSame('Unknown', $notification->getChannelDisplayName());
    }

    /**
     * Test getStatusDisplayName returns correct name.
     */
    public function testGetStatusDisplayName(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'pending');
        self::assertSame('Pending', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'unknown');
        self::assertSame('Unknown', $notification->getStatusDisplayName());
    }

    /**
     * Test getIcon returns correct icon.
     */
    public function testGetIcon(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        self::assertSame('💰', $notification->getIcon());

        $notification->type = 'unknown';
        self::assertSame('📢', $notification->getIcon());
    }

    /**
     * Test getColor returns correct color.
     */
    public function testGetColor(): void
    {
        $notification = new Notification();
        $notification->setAttribute('priority', 1);
        self::assertSame('gray', $notification->getColor());

        $notification->setAttribute('priority', 2);
        self::assertSame('blue', $notification->getColor());
    }

    /**
     * Test getBadgeText returns correct text.
     */
    public function testGetBadgeText(): void
    {
        $notification = new Notification();
        self::assertSame('New', $notification->getBadgeText());

        $notification->setAttribute('read_at', Carbon::now());
        self::assertSame('', $notification->getBadgeText());

        $notification->setAttribute('status', 'failed');
        self::assertSame('Failed', $notification->getBadgeText());
    }

    /**
     * Test getSummary returns truncated message.
     */
    public function testGetSummary(): void
    {
        $notification = new Notification(['message' => 'This is a long message that should be truncated']);
        self::assertSame('This is a long message that should be truncated', $notification->getSummary(100));

        self::assertSame('This is a long...', $notification->getSummary(15));
    }

    /**
     * Test hasAction returns true when url is set.
     */
    public function testHasAction(): void
    {
        $notification = new Notification(['data' => ['url' => 'http://example.com']]);
        self::assertTrue($notification->hasAction());

        $notification->data = [];
        self::assertFalse($notification->hasAction());
    }

    /**
     * Test isExpired returns true when expired.
     */
    public function testIsExpired(): void
    {
        $notification = new Notification([
            'data' => ['expiration_days' => 1],
            'created_at' => Carbon::now()->subDays(2),
        ]);
        self::assertTrue($notification->isExpired());

        $notification->data = [];
        self::assertFalse($notification->isExpired());
    }

    /**
     * Test canRetry returns true for failed notification with retries left.
     */
    public function testCanRetry(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'failed');
        $notification->data = ['retry_count' => 1];
        self::assertTrue($notification->canRetry(3));

        $notification->data = ['retry_count' => 3];
        self::assertFalse($notification->canRetry(3));
    }

    /**
     * Test hasTag returns true when tag exists.
     */
    public function testHasTag(): void
    {
        $notification = new Notification(['data' => ['tags' => ['urgent', 'important']]]);
        self::assertTrue($notification->hasTag('urgent'));
        self::assertFalse($notification->hasTag('normal'));
    }
}
