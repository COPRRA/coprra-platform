<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Allow debug logs without explicit expectations
        Log::shouldReceive('debug')->zeroOrMoreTimes();
    }

    public function testSecurityHeadersMiddlewareAddsSecurityHeaders(): void
    {
        $request = Request::create('/test', 'GET');

        // Ø§Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„ÙˆØ³ÙŠØ· Ù„ÙŠØªÙ… Ø­Ù‚Ù† Ø§Ù„Ø®Ø¯Ù…Ø© ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§
        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        self::assertSame('1; mode=block', $response->headers->get('X-XSS-Protection'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        self::assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
        self::assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        self::assertSame('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));
    }

    public function testSecurityHeadersMiddlewareHandlesSensitiveRoutes(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesSettingsRoutes(): void
    {
        $request = Request::create('/settings/profile', 'GET');

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesProfileRoutes(): void
    {
        $request = Request::create('/profile/edit', 'GET');

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesBillingRoutes(): void
    {
        $request = Request::create('/billing/payment', 'GET');

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesAdminApiRoutes(): void
    {
        $request = Request::create('/api/v1/admin/users', 'GET');

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testSecurityHeadersMiddlewareLogsSuspiciousSqlInjectionAttempts(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST', [
            'query' => 'SELECT * FROM users WHERE id = 1; DROP TABLE users; --',
        ]);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testSecurityHeadersMiddlewareLogsSuspiciousXssAttempts(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST', [
            'comment' => '<script>alert("XSS")</script>',
        ]);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testSecurityHeadersMiddlewareLogsSuspiciousFileUploads(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST');

        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php echo "test"; ?>');

        // Mock file upload
        $file = new UploadedFile(
            $tempFile,
            'test.php',
            'application/x-php',
            null,
            true
        );

        $request->files->set('upload', $file);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testSecurityHeadersMiddlewareDoesNotLogNormalRequests(): void
    {
        Log::shouldReceive('warning')->never();

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testSecurityHeadersMiddlewareHandlesHttpsRedirectInProduction(): void
    {
        $this->app->instance('env', 'production');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', false);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('https://', $response->headers->get('Location'));
    }

    public function testSecurityHeadersMiddlewareDoesNotRedirectHttpsInDevelopment(): void
    {
        $this->app->instance('env', 'local');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', false);

        $middleware = $this->app->make(SecurityHeaders::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }
}
