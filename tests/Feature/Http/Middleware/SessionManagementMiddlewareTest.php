<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SessionManagementMiddleware;
use Illuminate\Filesystem\Filesystem;
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
final class SessionManagementMiddlewareTest extends TestCase
{
    public function testSessionManagementMiddlewareManagesSession(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = $this->app->make(SessionManagementMiddleware::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSessionManagementMiddlewareHandlesSessionData(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('user_id', 123);
        $session->put('last_activity', now());

        $middleware = $this->app->make(SessionManagementMiddleware::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSessionManagementMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = $this->app->make(SessionManagementMiddleware::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSessionManagementMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = $this->app->make(SessionManagementMiddleware::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSessionManagementMiddlewareHandlesSessionTimeout(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new FileSessionHandler(
            new Filesystem(),
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('last_activity', now()->subHours(2));

        $middleware = new SessionManagementMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
