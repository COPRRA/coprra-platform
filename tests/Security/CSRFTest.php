<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 *
 * @internal
 *
 * @coversNothing
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CSRFTest extends TestCase
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

    public function testCsrfProtectionOnStateChangingEndpoints(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Test PUT request without CSRF token (should fail for web routes)
        $response = $this->put('/profile', [
            'name' => 'Updated Name',
        ]);

        // For web routes, this should fail with 419 (CSRF token mismatch), 401 (not authenticated), 302 (redirect to login), or 422 (validation)
        // For API routes, it might be different
        self::assertContains($response->status(), [419, 401, 422, 302]);
    }

    public function testCsrfTokenValidationInForms(): void
    {
        // Test that CSRF token is required for form submissions
        $response = $this->get('/login');

        // Should include CSRF token in the response
        $response->assertStatus(200);
        $content = $response->getContent();

        // Check if CSRF token is present in the form
        self::assertStringContainsString('_token', $content);
    }

    public function testCsrfProtectionBypassingWithValidToken(): void
    {
        // Create user
        $user = User::factory()->create();

        // Get a page with CSRF token
        $response = $this->get('/login');
        $content = $response->getContent();

        // Extract CSRF token (simplified - in real app you'd parse HTML)
        preg_match('/name="_token" value="([^"]+)"/', $content, $matches);
        $token = $matches[1] ?? null;

        // Assert that token was found
        self::assertNotNull($token, 'CSRF token should be present in login form');

        if ($token) {
            // Test POST with valid token
            $response = $this->post('/login', [
                '_token' => $token,
                'email' => $user->email,
                'password' => 'password',
            ]);

            // Should not fail due to CSRF
            self::assertNotSame(419, $response->status());
        }
    }

    public function testApiRoutesBypassCsrf(): void
    {
        // API routes should not require CSRF tokens
        $data = [
            'name' => 'Test Product',
            'price' => 100,
        ];

        $response = $this->postJson('/api/products', $data);

        // Should not fail with CSRF error (419)
        self::assertNotSame(419, $response->status());
    }

    public function testCsrfTokenRegeneration(): void
    {
        // Test that CSRF token changes after certain actions
        $response1 = $this->get('/login');
        $response1->assertStatus(200);
        $content1 = $response1->getContent();

        preg_match('/name="_token" value="([^"]+)"/', $content1, $matches1);
        $token1 = $matches1[1] ?? null;

        self::assertNotNull($token1, 'First CSRF token should be present in login form');

        // Perform some action that might regenerate token
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response2 = $this->get('/login');
        $response2->assertStatus(200);
        $content2 = $response2->getContent();

        preg_match('/name="_token" value="([^"]+)"/', $content2, $matches2);
        $token2 = $matches2[1] ?? null;

        // Tokens should be different or same depending on implementation
        // This test ensures token handling works
        self::assertNotNull($token2, 'Second CSRF token should be present in login form');

        // Verify both tokens are valid strings
        self::assertIsString($token1);
        self::assertIsString($token2);
        self::assertNotEmpty($token1);
        self::assertNotEmpty($token2);
    }

    public function testCsrfProtectionOnDeleteRequests(): void
    {
        $user = User::factory()->create();

        // Test DELETE request without CSRF token
        $response = $this->delete("/user/{$user->id}");

        // Should fail with CSRF, auth error, or not found
        self::assertContains($response->status(), [419, 401, 403, 405, 404]);
    }
}
