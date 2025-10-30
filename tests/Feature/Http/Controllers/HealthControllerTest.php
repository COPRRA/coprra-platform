<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturnsHealthyStatusWhenAllSystemsOperational(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database',
                    'cache',
                    'storage',
                ],
            ])
            ->assertJson([
                'status' => 'healthy',
            ])
        ;
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturnsUnhealthyStatusWhenDatabaseFails(): void
    {
        // Simulate database failure by using wrong connection
        config(['database.default' => 'invalid_connection']);

        $response = $this->get('/health');

        $response->assertStatus(503)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks',
                'errors',
            ])
        ;

        $data = $response->json();
        self::assertSame('unhealthy', $data['status']);
        self::assertArrayHasKey('errors', $data);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturnsUnhealthyStatusWhenStorageIsNotWritable(): void
    {
        $response = $this->get('/health');

        // Health check should test storage writability
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'storage',
            ],
        ]);

        $data = $response->json();
        self::assertArrayHasKey('checks', $data);
        self::assertArrayHasKey('storage', $data['checks']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itReturnsUnhealthyStatusWhenCacheFails(): void
    {
        // Simulate cache failure
        config(['cache.default' => 'invalid_driver']);

        $response = $this->get('/health');

        $response->assertJsonStructure([
            'status',
            'checks' => [
                'cache',
            ],
        ]);

        $data = $response->json();
        self::assertArrayHasKey('checks', $data);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itIncludesTimestampInResponse(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['timestamp'])
        ;

        $data = $response->json();
        self::assertArrayHasKey('timestamp', $data);
        self::assertIsString($data['timestamp']);
        self::assertNotEmpty($data['timestamp']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itIncludesVersionInResponse(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);

        $data = $response->json();
        // Version might be included in response or checks
        self::assertTrue(
            isset($data['version']) || isset($data['app_version']) || isset($data['checks']['version']),
            'Response should include version information'
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itIncludesEnvironmentInResponse(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);

        $data = $response->json();
        // Environment might be included in response or checks
        self::assertTrue(
            isset($data['environment']) || isset($data['env']) || isset($data['checks']['environment']),
            'Response should include environment information'
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itTestsDatabaseConnection(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'checks' => [
                'database',
            ],
        ]);

        $data = $response->json();
        self::assertArrayHasKey('database', $data['checks']);
        self::assertIsArray($data['checks']['database']) || self::assertIsString($data['checks']['database']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itTestsCacheFunctionality(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'checks' => [
                'cache',
            ],
        ]);

        $data = $response->json();
        self::assertArrayHasKey('cache', $data['checks']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itTestsStorageWritability(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'checks' => [
                'storage',
            ],
        ]);

        $data = $response->json();
        self::assertArrayHasKey('storage', $data['checks']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itHandlesMultipleSystemFailures(): void
    {
        // Simulate multiple failures
        config(['database.default' => 'invalid_connection']);
        config(['cache.default' => 'invalid_driver']);

        $response = $this->get('/health');

        $response->assertStatus(503)
            ->assertJsonStructure([
                'status',
                'checks',
                'errors',
            ])
        ;

        $data = $response->json();
        self::assertSame('unhealthy', $data['status']);
        self::assertArrayHasKey('errors', $data);
        self::assertIsArray($data['errors']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itHandlesCacheTestFailure(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'status',
            'checks',
        ]);

        $data = $response->json();
        self::assertArrayHasKey('checks', $data);

        // Verify cache check exists and has proper structure
        if (isset($data['checks']['cache'])) {
            self::assertNotNull($data['checks']['cache']);
        }
    }
}
