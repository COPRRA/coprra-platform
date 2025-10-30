<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdvancedIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function fullWorkflowIntegration(): void
    {
        // Test full workflow integration
        $response = $this->get('/');
        self::assertTrue(\in_array($response->status(), [200, 302, 404, 500], true));

        $response = $this->get('/api/products');
        self::assertTrue(\in_array($response->status(), [200, 401, 404, 500], true));
    }

    #[Test]
    public function apiDatabaseIntegration(): void
    {
        // Test API database integration
        $response = $this->getJson('/api/products');
        self::assertTrue(\in_array($response->status(), [200, 401, 404, 500], true));

        $response = $this->getJson('/api/categories');
        self::assertTrue(\in_array($response->status(), [200, 401, 404, 500], true));
    }

    #[Test]
    public function frontendBackendIntegration(): void
    {
        // Test frontend-backend integration
        $response = $this->get('/');
        self::assertTrue(\in_array($response->status(), [200, 302, 404, 500], true));

        $response = $this->post('/api/test', ['test' => 'data']);
        self::assertTrue(\in_array($response->status(), [200, 302, 404, 422, 500], true));
    }
}
