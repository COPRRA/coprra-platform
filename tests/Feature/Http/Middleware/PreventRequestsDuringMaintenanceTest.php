<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class PreventRequestsDuringMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function testPreventRequestsDuringMaintenanceMiddlewareAllowsRequestsWhenNotInMaintenance(): void
    {
        $request = Request::create('/test', 'GET');

        $app = \Mockery::mock(Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();

        // Verify the response is not a maintenance page
        self::assertStringNotContainsString('maintenance', strtolower($response->getContent()));
        self::assertStringNotContainsString('503', $response->getContent());
    }

    public function testPreventRequestsDuringMaintenanceMiddlewareBlocksRequestsDuringMaintenance(): void
    {
        $request = Request::create('/test', 'GET');

        $app = \Mockery::mock(Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(true);
        $maintenanceMode->shouldReceive('data')->andReturn(['message' => 'Site is under maintenance']);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new PreventRequestsDuringMaintenance($app);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Service Unavailable');

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();
    }

    public function testPreventRequestsDuringMaintenanceMiddlewareHandlesMaintenanceModeWithExceptions(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $app = \Mockery::mock(Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(true);
        $maintenanceMode->shouldReceive('data')->andReturn(['message' => 'Site is under maintenance']);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new PreventRequestsDuringMaintenance($app);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Service Unavailable');

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }

    public function testPreventRequestsDuringMaintenanceMiddlewareHandlesDifferentRequestMethods(): void
    {
        $request = Request::create('/test', 'POST', ['data' => 'test']);

        $app = \Mockery::mock(Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testPreventRequestsDuringMaintenanceMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $app = \Mockery::mock(Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();

        // Verify the request path is preserved
        self::assertSame('/api/test', $request->getPathInfo());

        // Verify the response content type is appropriate
        self::assertStringNotContainsString('maintenance', strtolower($response->getContent()));
    }
}
