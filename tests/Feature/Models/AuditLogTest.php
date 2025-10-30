<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
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
final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testItCanCreateAnAuditLog(): void
    {
        $auditLog = AuditLog::factory()->create([
            'event' => 'created',
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
            'metadata' => ['source' => 'web'],
            'url' => '/products/1',
            'method' => 'POST',
        ]);

        self::assertInstanceOf(AuditLog::class, $auditLog);
        self::assertSame('created', $auditLog->event);
        self::assertSame(Product::class, $auditLog->auditable_type);
        self::assertSame(1, $auditLog->auditable_id);
        self::assertSame(1, $auditLog->user_id);
        self::assertSame('127.0.0.1', $auditLog->ip_address);
        self::assertSame('Mozilla/5.0', $auditLog->user_agent);
        self::assertSame(['name' => 'Old Name'], $auditLog->old_values);
        self::assertSame(['name' => 'New Name'], $auditLog->new_values);
        self::assertSame(['source' => 'web'], $auditLog->metadata);
        self::assertSame('/products/1', $auditLog->url);
        self::assertSame('POST', $auditLog->method);

        // Assert that the audit log was actually saved to the database
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_type' => Product::class,
            'auditable_id' => 1,
            'user_id' => 1,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'url' => '/products/1',
            'method' => 'POST',
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'price' => 100],
            'new_values' => ['name' => 'New Name', 'price' => 150],
            'metadata' => ['source' => 'web', 'timestamp' => '2023-01-01'],
        ]);

        self::assertIsArray($auditLog->old_values);
        self::assertIsArray($auditLog->new_values);
        self::assertIsArray($auditLog->metadata);
        self::assertSame(['name' => 'Old Name', 'price' => 100], $auditLog->old_values);
        self::assertSame(['name' => 'New Name', 'price' => 150], $auditLog->new_values);
        self::assertSame(['source' => 'web', 'timestamp' => '2023-01-01'], $auditLog->metadata);

        // Assert that the JSON attributes were properly cast and saved
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
        ]);

        // Verify the JSON data was properly stored
        $savedAuditLog = AuditLog::find($auditLog->id);
        self::assertIsArray($savedAuditLog->old_values);
        self::assertIsArray($savedAuditLog->new_values);
        self::assertIsArray($savedAuditLog->metadata);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

        self::assertInstanceOf(User::class, $auditLog->user);
        self::assertSame($user->id, $auditLog->user->id);

        // Assert that the audit log was saved with the correct user_id
        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function testItHasMorphToAuditable(): void
    {
        $product = Product::factory()->create();
        $auditLog = AuditLog::factory()->create([
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
        ]);

        self::assertInstanceOf(Product::class, $auditLog->auditable);
        self::assertSame($product->id, $auditLog->auditable->id);
    }

    #[Test]
    public function testScopeEventFiltersByEvent(): void
    {
        AuditLog::factory()->create(['event' => 'created']);
        AuditLog::factory()->create(['event' => 'updated']);
        AuditLog::factory()->create(['event' => 'deleted']);

        $createdLogs = AuditLog::event('created')->get();
        $updatedLogs = AuditLog::event('updated')->get();
        $deletedLogs = AuditLog::event('deleted')->get();

        self::assertCount(1, $createdLogs);
        self::assertCount(1, $updatedLogs);
        self::assertCount(1, $deletedLogs);
        self::assertSame('created', $createdLogs->first()->event);
        self::assertSame('updated', $updatedLogs->first()->event);
        self::assertSame('deleted', $deletedLogs->first()->event);
    }

    #[Test]
    public function testScopeForUserFiltersByUserId(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        AuditLog::factory()->create(['user_id' => $user1->id]);
        AuditLog::factory()->create(['user_id' => $user2->id]);
        AuditLog::factory()->create(['user_id' => null]);

        $user1Logs = AuditLog::forUser($user1->id)->get();
        $user2Logs = AuditLog::forUser($user2->id)->get();

        self::assertCount(1, $user1Logs);
        self::assertCount(1, $user2Logs);
        self::assertSame($user1->id, $user1Logs->first()->user_id);
        self::assertSame($user2->id, $user2Logs->first()->user_id);
    }

    #[Test]
    public function testScopeForModelFiltersByAuditableType(): void
    {
        AuditLog::factory()->create(['auditable_type' => Product::class]);
        AuditLog::factory()->create(['auditable_type' => User::class]);
        AuditLog::factory()->create(['auditable_type' => Product::class]);

        $productLogs = AuditLog::forModel(Product::class)->get();
        $userLogs = AuditLog::forModel(User::class)->get();

        self::assertCount(2, $productLogs);
        self::assertCount(1, $userLogs);
        self::assertTrue($productLogs->every(static fn ($log) => Product::class === $log->auditable_type));
        self::assertTrue($userLogs->every(static fn ($log) => User::class === $log->auditable_type));
    }

    #[Test]
    public function testScopeDateRangeFiltersByCreatedAt(): void
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $tomorrow = $now->copy()->addDay();

        AuditLog::factory()->create(['created_at' => $yesterday]);
        AuditLog::factory()->create(['created_at' => $now]);
        AuditLog::factory()->create(['created_at' => $tomorrow]);

        $logsInRange = AuditLog::dateRange($yesterday, $tomorrow)->get();
        $logsBefore = AuditLog::dateRange($yesterday->copy()->subDay(), $yesterday)->get();
        $logsAfter = AuditLog::dateRange($tomorrow, $tomorrow->copy()->addDay())->get();

        self::assertCount(3, $logsInRange);
        self::assertCount(1, $logsBefore);
        self::assertCount(1, $logsAfter);
    }

    #[Test]
    public function testGetFormattedEventAttribute(): void
    {
        $auditLog = AuditLog::factory()->create(['event' => 'user_created']);

        self::assertSame('User created', $auditLog->formatted_event);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithChanges(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'price' => 100],
            'new_values' => ['name' => 'New Name', 'price' => 150],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();
        self::assertStringContainsString('name: Old Name → New Name', $summary);
        self::assertStringContainsString('price: 100 → 150', $summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithoutChanges(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => null,
            'new_values' => null,
        ]);

        self::assertSame('No changes recorded', $auditLog->changes_summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithNoOldValues(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => null,
            'new_values' => ['name' => 'New Name'],
        ]);

        self::assertSame('No changes recorded', $auditLog->changes_summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithNoNewValues(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name'],
            'new_values' => null,
        ]);

        self::assertSame('No changes recorded', $auditLog->changes_summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithUnchangedValues(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Same Name', 'price' => 100],
            'new_values' => ['name' => 'Same Name', 'price' => 100],
        ]);

        self::assertSame('', $auditLog->changes_summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithMixedDataTypes(): void
    {
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old Name', 'active' => true, 'count' => 5],
            'new_values' => ['name' => 'New Name', 'active' => false, 'count' => 10],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();
        self::assertStringContainsString('name: Old Name → New Name', $summary);
        self::assertStringContainsString('active: 1 → 0', $summary);
        self::assertStringContainsString('count: 5 → 10', $summary);
    }

    #[Test]
    public function testFactoryCreatesValidAuditLog(): void
    {
        $auditLog = AuditLog::factory()->make();

        self::assertInstanceOf(AuditLog::class, $auditLog);
        self::assertNotEmpty($auditLog->event);
        self::assertNotEmpty($auditLog->auditable_type);
        self::assertNotNull($auditLog->auditable_id);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'event',
            'auditable_type',
            'auditable_id',
            'user_id',
            'ip_address',
            'user_agent',
            'old_values',
            'new_values',
            'metadata',
            'url',
            'method',
        ];

        self::assertSame($fillable, (new AuditLog())->getFillable());
    }
}
