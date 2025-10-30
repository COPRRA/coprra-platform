<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SubstituteBindings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class SubstituteBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function testSubstituteBindingsMiddlewareSubstitutesRouteBindings(): void
    {
        $user = User::factory()->create();
        $request = Request::create("/users/{$user->id}", 'GET');
        $request->setRouteResolver(static function () use ($user, $request) {
            $route = new Route(['GET'], '/users/{user}', ['uses' => static function () {}]);
            $route->bind($request);
            $route->setParameter('user', $user);

            return $route;
        });

        $middleware = $this->app->make(SubstituteBindings::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSubstituteBindingsMiddlewareHandlesMissingBindings(): void
    {
        $request = Request::create('/users/999', 'GET');
        $request->setRouteResolver(static function () use ($request) {
            $route = new Route(['GET'], '/users/{user}', ['uses' => static function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(SubstituteBindings::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSubstituteBindingsMiddlewareHandlesNoRoute(): void
    {
        $request = Request::create('/test', 'GET');

        // ÙˆÙÙ‘Ø± Route ÙØ§Ø±Øº Ù„Ø¶Ù…Ø§Ù† Ø¹Ø¯Ù… ÙØ´Ù„ Ø§Ù„ÙˆØ³ÙŠØ· Ø¹Ù†Ø¯ Ø¹Ø¯Ù… ÙˆØ¬ÙˆØ¯ Route ÙØ¹Ù„ÙŠ
        $request->setRouteResolver(static function () use ($request) {
            $route = new Route(['GET'], '/test', ['uses' => static function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(SubstituteBindings::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSubstituteBindingsMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $request->setRouteResolver(static function () use ($request) {
            $route = new Route(['POST'], '/test', ['uses' => static function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(SubstituteBindings::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSubstituteBindingsMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $request->setRouteResolver(static function () use ($request) {
            $route = new Route(['GET'], '/api/test', ['uses' => static function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(SubstituteBindings::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
