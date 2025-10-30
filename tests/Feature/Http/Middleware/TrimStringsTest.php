<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\TrimStrings;
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
final class TrimStringsTest extends TestCase
{
    use RefreshDatabase;

    public function testTrimStringsMiddlewareTrimsStringInput(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'description' => '  This is a test description  ',
        ]);

        $middleware = new TrimStrings();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('John Doe', $request->input('name'));
        self::assertSame('john@example.com', $request->input('email'));
        self::assertSame('This is a test description', $request->input('description'));
    }

    public function testTrimStringsMiddlewareDoesNotTrimNonStringInput(): void
    {
        $request = Request::create('/test', 'POST', [
            'age' => 25,
            'is_active' => true,
            'tags' => ['tag1', 'tag2', 'tag3'],
        ]);

        $middleware = new TrimStrings();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(25, $request->input('age'));
        self::assertTrue($request->input('is_active'));
        self::assertSame(['tag1', 'tag2', 'tag3'], $request->input('tags'));
    }

    public function testTrimStringsMiddlewareHandlesNestedArrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '  John Doe  ',
                'email' => '  john@example.com  ',
            ],
            'address' => [
                'street' => '  123 Main St  ',
                'city' => '  New York  ',
            ],
        ]);

        $middleware = new TrimStrings();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('John Doe', $request->input('user.name'));
        self::assertSame('john@example.com', $request->input('user.email'));
        self::assertSame('123 Main St', $request->input('address.street'));
        self::assertSame('New York', $request->input('address.city'));
    }

    public function testTrimStringsMiddlewareHandlesEmptyStrings(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => '   ',
            'description' => null,
        ]);

        $middleware = new TrimStrings();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $request->input('name'));
        self::assertSame('', $request->input('email'));
        self::assertNull($request->input('description'));
    }

    public function testTrimStringsMiddlewarePassesRequestToNextMiddleware(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '  John Doe  ',
        ]);

        $middleware = new TrimStrings();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
