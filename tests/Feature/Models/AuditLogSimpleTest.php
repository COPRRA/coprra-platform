<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuditLogSimpleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testItHasCorrectFillableAttributes(): void
    {
        $auditLog = new AuditLog();

        $expectedFillable = [
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

        self::assertSame($expectedFillable, $auditLog->getFillable());
    }

    #[Test]
    public function testItHasCorrectCasts(): void
    {
        $auditLog = new AuditLog();

        $expectedCasts = [
            'id' => 'int',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];

        self::assertSame($expectedCasts, $auditLog->getCasts());
    }

    #[Test]
    public function testItHasCorrectTableName(): void
    {
        $auditLog = new AuditLog();

        self::assertSame('audit_logs', $auditLog->getTable());
    }

    #[Test]
    public function testItUsesTimestamps(): void
    {
        $auditLog = new AuditLog();

        self::assertTrue($auditLog->usesTimestamps());
    }

    #[Test]
    public function testGetFormattedEventAttribute(): void
    {
        $auditLog = new AuditLog(['event' => 'user_created']);

        self::assertSame('User created', $auditLog->getFormattedEventAttribute());
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithChanges(): void
    {
        $auditLog = new AuditLog([
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
        $auditLog = new AuditLog([
            'old_values' => null,
            'new_values' => null,
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        self::assertSame('No changes recorded', $summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithNoOldValues(): void
    {
        $auditLog = new AuditLog([
            'old_values' => null,
            'new_values' => ['name' => 'New Name'],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        self::assertStringContainsString('No changes recorded', $summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithNoNewValues(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name'],
            'new_values' => null,
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        self::assertStringContainsString('No changes recorded', $summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithUnchangedValues(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Same Name', 'price' => 100],
            'new_values' => ['name' => 'Same Name', 'price' => 100],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        self::assertSame('', $summary);
    }

    #[Test]
    public function testGetChangesSummaryAttributeWithMixedDataTypes(): void
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name', 'active' => true, 'count' => 5],
            'new_values' => ['name' => 'New Name', 'active' => false, 'count' => 10],
        ]);

        $summary = $auditLog->getChangesSummaryAttribute();

        self::assertStringContainsString('name: Old Name → New Name', $summary);
        self::assertStringContainsString('active: 1 → 0', $summary);
        self::assertStringContainsString('count: 5 → 10', $summary);
    }

    /**
     * Test that AuditLogSimpleTest can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(self::class, $this);
    }
}
