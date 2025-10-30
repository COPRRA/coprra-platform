<?php

declare(strict_types=1);

namespace Tests\Feature;

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
final class RoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testBasicFunctionality(): void
    {
        // Test that basic routes are accessible
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    #[Test]
    public function testExpectedBehavior(): void
    {
        // Test that API routes return JSON
        $response = $this->get('/api/health');
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
        ;
    }

    #[Test]
    public function testValidation(): void
    {
        // Test that invalid routes return 404
        $response = $this->get('/non-existent-route');
        $response->assertStatus(404);
    }
}
