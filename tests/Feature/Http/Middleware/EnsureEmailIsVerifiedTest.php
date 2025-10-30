<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureEmailIsVerified;
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
final class EnsureEmailIsVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function testEnsureEmailIsVerifiedMiddlewareAllowsVerifiedUsers(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new EnsureEmailIsVerified();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testEnsureEmailIsVerifiedMiddlewareRedirectsUnverifiedUsers(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new EnsureEmailIsVerified();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testEnsureEmailIsVerifiedMiddlewareHandlesUnauthenticatedUsers(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new EnsureEmailIsVerified();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testEnsureEmailIsVerifiedMiddlewareHandlesApiRequests(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(static fn () => $user);

        $middleware = new EnsureEmailIsVerified();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(403, $response->getStatusCode());
    }

    public function testEnsureEmailIsVerifiedMiddlewareHandlesPostRequests(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->setUserResolver(static fn () => $user);

        $middleware = new EnsureEmailIsVerified();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
