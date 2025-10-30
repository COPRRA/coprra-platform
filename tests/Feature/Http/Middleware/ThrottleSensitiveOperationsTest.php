<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ThrottleSensitiveOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ThrottleSensitiveOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function testThrottleSensitiveOperationsMiddlewareAllowsRequestsWithinLimit(): void
    {
        $request = Request::create('/test', 'POST', [
            'password' => 'newpassword123',
        ]);

        $middleware = $this->app->make(ThrottleSensitiveOperations::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testThrottleSensitiveOperationsMiddlewareHandlesPasswordChange(): void
    {
        $request = Request::create('/change-password', 'POST', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $middleware = $this->app->make(ThrottleSensitiveOperations::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testThrottleSensitiveOperationsMiddlewareHandlesEmailChange(): void
    {
        $request = Request::create('/change-email', 'POST', [
            'email' => 'newemail@example.com',
            'password' => 'password123',
        ]);

        $middleware = new ThrottleSensitiveOperations();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testThrottleSensitiveOperationsMiddlewareHandlesGetRequests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new ThrottleSensitiveOperations();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testThrottleSensitiveOperationsMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/sensitive-operation', 'POST', [
            'data' => 'sensitive data',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new ThrottleSensitiveOperations();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
