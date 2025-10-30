<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Notification;
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
final class NotificationSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock config to prevent "Target class [config] does not exist" error
        $config = \Mockery::mock();
        $config->shouldReceive('get')->with('app.timezone', \Mockery::any())->andReturn('UTC');
        $config->shouldReceive('get')->with(\Mockery::any())->andReturn(null);
        $this->app->instance('config', $config);
    }

    #[Test]
    public function testItHasCorrectFillableAttributes(): void
    {
        $notification = new Notification();

        $expectedFillable = [
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

        self::assertSame($expectedFillable, $notification->getFillable());
    }

    #[Test]
    public function testItHasCorrectCasts(): void
    {
        $notification = new Notification();

        $expectedCasts = [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
            'priority' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
            'id' => 'int',
        ];

        self::assertSame($expectedCasts, $notification->getCasts());
    }

    #[Test]
    public function testItHasCorrectTableName(): void
    {
        $notification = new Notification();

        self::assertSame('custom_notifications', $notification->getTable());
    }

    #[Test]
    public function testItUsesTimestamps(): void
    {
        $notification = new Notification();

        self::assertTrue($notification->usesTimestamps());
    }

    #[Test]
    public function testItHasCorrectHiddenAttributes(): void
    {
        $notification = new Notification();

        $expectedHidden = ['data'];

        self::assertSame($expectedHidden, $notification->getHidden());
    }

    #[Test]
    public function testIsReadReturnsTrueWhenReadAtIsSet(): void
    {
        $notification = new Notification(['read_at' => now()]);

        self::assertTrue($notification->isRead());
    }

    #[Test]
    public function testIsReadReturnsFalseWhenReadAtIsNull(): void
    {
        $notification = new Notification(['read_at' => null]);

        self::assertFalse($notification->isRead());
    }

    #[Test]
    public function testIsUnreadReturnsTrueWhenReadAtIsNull(): void
    {
        $notification = new Notification(['read_at' => null]);

        self::assertTrue($notification->isUnread());
    }

    #[Test]
    public function testIsUnreadReturnsFalseWhenReadAtIsSet(): void
    {
        $notification = new Notification(['read_at' => now()]);

        self::assertFalse($notification->isUnread());
    }

    #[Test]
    public function testIsSentReturnsTrueWhenSentAtIsSet(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', now());

        self::assertTrue($notification->isSent());
    }

    #[Test]
    public function testIsSentReturnsFalseWhenSentAtIsNull(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', null);

        self::assertFalse($notification->isSent());
    }

    #[Test]
    public function testIsPendingReturnsTrueWhenSentAtIsNullAndStatusIsPending(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', null);
        $notification->setAttribute('status', 'pending');

        self::assertTrue($notification->isPending());
    }

    #[Test]
    public function testIsPendingReturnsFalseWhenSentAtIsSet(): void
    {
        $notification = new Notification();
        $notification->setAttribute('sent_at', now());
        $notification->setAttribute('status', 'pending');

        self::assertFalse($notification->isPending());
    }

    #[Test]
    public function testIsFailedReturnsTrueWhenStatusIsFailed(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'failed');

        self::assertTrue($notification->isFailed());
    }

    #[Test]
    public function testIsFailedReturnsFalseWhenStatusIsNotFailed(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'pending');

        self::assertFalse($notification->isFailed());
    }

    #[Test]
    public function testGetPriorityLevelReturnsCorrectLevels(): void
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

    #[Test]
    public function testGetTypeDisplayNameReturnsCorrectNames(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        self::assertSame('Price Drop Alert', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'new_product']);
        self::assertSame('New Product', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'system']);
        self::assertSame('System Notification', $notification->getTypeDisplayName());

        $notification = new Notification(['type' => 'unknown_type']);
        self::assertSame('Unknown type', $notification->getTypeDisplayName());
    }

    #[Test]
    public function testGetChannelDisplayNameReturnsCorrectNames(): void
    {
        $notification = new Notification();

        $notification->setAttribute('channel', 'email');
        self::assertSame('Email', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'sms');
        self::assertSame('SMS', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'push');
        self::assertSame('Push Notification', $notification->getChannelDisplayName());

        $notification->setAttribute('channel', 'database');
        self::assertSame('Database', $notification->getChannelDisplayName());
    }

    #[Test]
    public function testGetStatusDisplayNameReturnsCorrectNames(): void
    {
        $notification = new Notification();

        $notification->setAttribute('status', 'pending');
        self::assertSame('Pending', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'sent');
        self::assertSame('Sent', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'failed');
        self::assertSame('Failed', $notification->getStatusDisplayName());

        $notification->setAttribute('status', 'cancelled');
        self::assertSame('Cancelled', $notification->getStatusDisplayName());
    }

    #[Test]
    public function testGetIconReturnsCorrectIcons(): void
    {
        $notification = new Notification(['type' => 'price_drop']);
        self::assertSame('💰', $notification->getIcon());

        $notification = new Notification(['type' => 'new_product']);
        self::assertSame('🆕', $notification->getIcon());

        $notification = new Notification(['type' => 'system']);
        self::assertSame('⚙️', $notification->getIcon());

        $notification = new Notification(['type' => 'unknown_type']);
        self::assertSame('📢', $notification->getIcon());
    }

    #[Test]
    public function testGetColorReturnsCorrectColors(): void
    {
        $notification = new Notification();

        $notification->setAttribute('priority', 1);
        self::assertSame('gray', $notification->getColor());

        $notification->setAttribute('priority', 2);
        self::assertSame('blue', $notification->getColor());

        $notification->setAttribute('priority', 3);
        self::assertSame('orange', $notification->getColor());

        $notification->setAttribute('priority', 4);
        self::assertSame('red', $notification->getColor());
    }

    #[Test]
    public function testGetBadgeTextReturnsCorrectText(): void
    {
        $notification = new Notification(['read_at' => null]);
        self::assertSame('New', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('status', 'failed');
        self::assertSame('Failed', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('sent_at', null);
        $notification->setAttribute('status', 'pending');
        self::assertSame('Pending', $notification->getBadgeText());

        $notification = new Notification(['read_at' => now()]);
        $notification->setAttribute('status', 'sent');
        self::assertSame('', $notification->getBadgeText());
    }

    #[Test]
    public function testGetSummaryTruncatesLongMessages(): void
    {
        $longMessage = str_repeat('This is a very long message. ', 10);
        $notification = new Notification(['message' => $longMessage]);

        $summary = $notification->getSummary(50);

        self::assertLessThanOrEqual(53, \strlen($summary)); // 50 + "..."
        self::assertStringEndsWith('...', $summary);
    }

    #[Test]
    public function testGetSummaryReturnsFullMessageWhenShort(): void
    {
        $shortMessage = 'Short message';
        $notification = new Notification(['message' => $shortMessage]);

        $summary = $notification->getSummary(50);

        self::assertSame($shortMessage, $summary);
    }

    #[Test]
    public function testGetDataReturnsDataArray(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = new Notification(['data' => $data]);

        self::assertSame($data, $notification->getData());
    }

    #[Test]
    public function testGetDataReturnsSpecificKey(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = new Notification(['data' => $data]);

        self::assertSame('value', $notification->getData('key'));
        self::assertSame(123, $notification->getData('number'));
        self::assertSame('default', $notification->getData('nonexistent', 'default'));
    }

    #[Test]
    public function testGetMetadataReturnsMetadataArray(): void
    {
        $metadata = ['source' => 'system', 'version' => '1.0'];
        $notification = new Notification(['data' => ['metadata' => $metadata]]);

        self::assertSame($metadata, $notification->getMetadata());
    }

    #[Test]
    public function testGetTagsReturnsTagsArray(): void
    {
        $tags = ['urgent', 'system', 'maintenance'];
        $notification = new Notification(['data' => ['tags' => $tags]]);

        self::assertSame($tags, $notification->getTags());
    }

    #[Test]
    public function testHasTagReturnsCorrectBoolean(): void
    {
        $tags = ['urgent', 'system'];
        $notification = new Notification(['data' => ['tags' => $tags]]);

        self::assertTrue($notification->hasTag('urgent'));
        self::assertTrue($notification->hasTag('system'));
        self::assertFalse($notification->hasTag('maintenance'));
    }

    #[Test]
    public function testGetRetryCountReturnsCorrectCount(): void
    {
        $notification = new Notification(['data' => ['retry_count' => 3]]);

        self::assertSame(3, $notification->getRetryCount());
    }

    #[Test]
    public function testGetRetryCountReturnsZeroWhenNotSet(): void
    {
        $notification = new Notification();

        self::assertSame(0, $notification->getRetryCount());
    }

    #[Test]
    public function testCanRetryReturnsCorrectBoolean(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'failed');
        $notification->setAttribute('data', ['retry_count' => 2]);

        self::assertTrue($notification->canRetry(3));
        self::assertFalse($notification->canRetry(2));
    }

    #[Test]
    public function testCanRetryReturnsFalseWhenNotFailed(): void
    {
        $notification = new Notification();
        $notification->setAttribute('status', 'pending');
        $notification->setAttribute('data', ['retry_count' => 1]);

        self::assertFalse($notification->canRetry(3));
    }
}
