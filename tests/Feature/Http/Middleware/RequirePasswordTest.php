<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequirePassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class RequirePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function testRequirePasswordMiddlewareAllowsRequestsWithValidPassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($user);

        // Manually set the password_confirmed_at attribute
        $user->password_confirmed_at = now();
        $user->save();

        $request = Request::create('/test', 'POST', [
            'password' => 'password123',
        ]);
        $request->setUserResolver(static fn () => $user);

        $middleware = new RequirePassword();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testRequirePasswordMiddlewareBlocksRequestsWithInvalidPassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'password' => 'wrongpassword',
        ]);
        $request->setUserResolver(static fn () => $user);

        $middleware = new RequirePassword();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRequirePasswordMiddlewareBlocksRequestsWithoutPassword(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', []);
        $request->setUserResolver(static fn () => $user);

        $middleware = new RequirePassword();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRequirePasswordMiddlewareHandlesUnauthenticatedUsers(): void
    {
        $request = Request::create('/test', 'POST', [
            'password' => 'password123',
        ]);

        $middleware = new RequirePassword();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(302, $response->getStatusCode());
    }

    public function testRequirePasswordMiddlewareHandlesGetRequests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Manually set the password_confirmed_at attribute
        $user->password_confirmed_at = now();
        $user->save();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new RequirePassword();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
