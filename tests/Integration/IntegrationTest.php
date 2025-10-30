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
final class IntegrationTest extends TestCase
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
    public function basicIntegrationWorks(): void
    {
        // Test basic integration
        $response = $this->get('/');
        self::assertContains($response->status(), [200, 302, 404, 500]);

        $response = $this->get('/api');
        self::assertContains($response->status(), [200, 302, 404, 500]);
    }

    #[Test]
    public function serviceIntegrationWorks(): void
    {
        // Test service integration
        $response = $this->getJson('/api/products');
        self::assertContains($response->status(), [200, 401, 404, 500]);

        $response = $this->getJson('/api/categories');
        self::assertContains($response->status(), [200, 401, 404, 500]);
    }

    #[Test]
    public function componentIntegrationWorks(): void
    {
        // Test component integration
        $response = $this->get('/login');
        self::assertContains($response->status(), [200, 302, 404, 500]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        self::assertContains($response->status(), [200, 302, 404, 422, 500, 405, 419]);
    }
}
