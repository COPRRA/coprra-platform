<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\AuthenticateSession;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\FileSessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class AuthenticateSessionTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateSessionMiddlewareAllowsAuthenticatedUsers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $request->setUserResolver(static fn () => $user);

        $middleware = new AuthenticateSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateSessionMiddlewareHandlesUnauthenticatedUsers(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new AuthenticateSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateSessionMiddlewareHandlesSessionData(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('user_id', $user->id);
        $request->setUserResolver(static fn () => $user);

        $middleware = new AuthenticateSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateSessionMiddlewareHandlesPostRequests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $request->setUserResolver(static fn () => $user);

        $middleware = new AuthenticateSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateSessionMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new AuthenticateSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
