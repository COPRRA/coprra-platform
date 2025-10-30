<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\MockValidationTrait;
use Tests\Support\TestIsolationTrait;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuditServiceTest extends TestCase
{
    use MockValidationTrait;
    use RefreshDatabase;
    use TestIsolationTrait;

    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupGlobalState();

        // Validate service class exists and has required methods
        $this->validateMockMethods(AuditService::class, [
            'logSensitiveOperation',
            'logUpdated',
            'logCreated',
            'logDeleted',
        ]);

        $this->auditService = new AuditService();

        // Create a test request with known values
        $this->app['request']->merge([]);
        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->app['request']->server->set('HTTP_USER_AGENT', 'TestAgent/1.0');
        $this->app['request']->server->set('REQUEST_METHOD', 'GET');
        $this->app['request']->server->set('REQUEST_URI', '/test');
    }

    protected function tearDown(): void
    {
        $this->restoreGlobalState();
        $this->clearTestCaches();
        $this->closeMockery();
        $this->verifyTestIsolation();
        parent::tearDown();
    }

    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(AuditService::class, $this->auditService);
    }

    public function testLogsCreatedEvent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logCreated($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'url' => 'http://example.com/test',
            'method' => 'GET',
        ]);

        $log = AuditLog::first();
        self::assertSame($user->getAttributes(), $log->new_values);
        self::assertNull($log->old_values);
    }

    public function testLogsUpdatedEvent(): void
    {
        $user = User::factory()->create();
        $oldValues = ['name' => 'Old Name'];

        $this->actingAs($user);

        $this->auditService->logUpdated($user, $oldValues);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        self::assertSame($oldValues, $log->old_values);
        self::assertSame($user->getChanges(), $log->new_values);
    }

    public function testLogsDeletedEvent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logDeleted($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        self::assertSame($user->getAttributes(), $log->old_values);
        self::assertNull($log->new_values);
    }

    public function testLogsViewedEvent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logViewed($user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'viewed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $log = AuditLog::first();
        self::assertNull($log->old_values);
        self::assertNull($log->new_values);
    }

    public function testLogsSensitiveOperation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logSensitiveOperation('password_change', $user);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_change',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function testLogsAuthEventWithUserId(): void
    {
        $performer = User::factory()->create(['email' => 'performer@example.com']);
        $targetUser = User::factory()->create(['email' => 'target@example.com']);

        $this->actingAs($performer);

        $this->auditService->logAuthEvent('login', $targetUser->id);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'user_id' => $performer->id,
        ]);
    }

    public function testLogsAuthEventWithoutUserId(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logAuthEvent('logout');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'logout',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function testLogsApiAccess(): void
    {
        $performer = User::factory()->create(['email' => 'performer2@example.com']);
        $targetUser = User::factory()->create(['email' => 'target2@example.com']);

        $this->actingAs($performer);

        $this->auditService->logApiAccess('/api/test', 'GET', $targetUser->id, ['response_time' => 150]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'api_access',
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'user_id' => $performer->id,
        ]);

        $log = AuditLog::first();
        self::assertSame(['endpoint' => '/api/test', 'method' => 'GET', 'response_time' => 150], $log->metadata);
    }

    public function testGetsModelLogs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->auditService->logCreated($user);
        usleep(1000000); // Ensure timestamp difference for ordering
        $this->auditService->logViewed($user);

        $logs = $this->auditService->getModelLogs($user);

        self::assertCount(2, $logs);
        self::assertSame('viewed', $logs->first()->event);
        self::assertSame('created', $logs->last()->event);
    }

    public function testGetsUserLogs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->auditService->logCreated($user);

        $logs = $this->auditService->getUserLogs($user->id);

        self::assertCount(1, $logs);
        self::assertSame('created', $logs->first()->event);
    }

    public function testGetsEventLogs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->auditService->logCreated($user);

        $logs = $this->auditService->getEventLogs('created');

        self::assertCount(1, $logs);
        self::assertSame('created', $logs->first()->event);
    }

    public function testCleansOldLogs(): void
    {
        $user = User::factory()->create();

        // Create old log
        AuditLog::factory()->create([
            'created_at' => now()->subDays(100),
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        $deleted = $this->auditService->cleanOldLogs(90);

        self::assertSame(1, $deleted);
        $this->assertDatabaseMissing('audit_logs', ['auditable_id' => $user->id]);
    }
}
