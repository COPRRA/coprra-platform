<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\User;
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
final class RedirectIfAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectIfAuthenticatedMiddlewareRedirectsAuthenticatedUsers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/login', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new RedirectIfAuthenticated();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/', $response->headers->get('Location'));
    }

    public function testRedirectIfAuthenticatedMiddlewareAllowsUnauthenticatedUsers(): void
    {
        $request = Request::create('/login', 'GET');

        $middleware = new RedirectIfAuthenticated();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testRedirectIfAuthenticatedMiddlewareHandlesApiRequests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/api/login', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(static fn () => $user);

        $middleware = new RedirectIfAuthenticated();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRedirectIfAuthenticatedMiddlewareHandlesDifferentRoutes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/register', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new RedirectIfAuthenticated();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRedirectIfAuthenticatedMiddlewareHandlesNullUser(): void
    {
        $request = Request::create('/login', 'GET');
        $request->setUserResolver(static fn () => null);

        $middleware = new RedirectIfAuthenticated();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
