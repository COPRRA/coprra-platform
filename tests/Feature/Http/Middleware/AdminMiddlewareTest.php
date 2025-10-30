<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\AdminMiddleware;
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
final class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareAllowsAuthenticatedAdminUser(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareRedirectsUnauthenticatedUser(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/login', $response->headers->get('Location'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareReturnsJsonForApiRequestsWhenUnauthenticated(): void
    {
        $request = Request::create('/api/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(401, $response->getStatusCode());
        self::assertJson($response->getContent());
        self::assertStringContainsString('Unauthenticated', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareRedirectsNonAdminUser(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/', $response->headers->get('Location'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareReturnsJsonForApiRequestsWhenNonAdmin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $request = Request::create('/api/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(static fn () => $user);

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(403, $response->getStatusCode());
        self::assertJson($response->getContent());
        self::assertStringContainsString('Forbidden', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAdminMiddlewareHandlesNullUser(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(static fn () => null);

        $middleware = new AdminMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/login', $response->headers->get('Location'));
    }
}
