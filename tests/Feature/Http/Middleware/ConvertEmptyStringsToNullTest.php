<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ConvertEmptyStringsToNull;
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
final class ConvertEmptyStringsToNullTest extends TestCase
{
    use RefreshDatabase;

    public function testConvertEmptyStringsMiddlewareConvertsEmptyStringsToNull(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'test@example.com',
            'description' => '',
            'age' => 25,
        ]);

        $middleware = new ConvertEmptyStringsToNull();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($request->input('name'));
        self::assertSame('test@example.com', $request->input('email'));
        self::assertNull($request->input('description'));
        self::assertSame(25, $request->input('age'));
    }

    public function testConvertEmptyStringsMiddlewareHandlesNestedArrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '',
                'email' => 'test@example.com',
            ],
            'address' => [
                'street' => '',
                'city' => 'New York',
            ],
        ]);

        $middleware = new ConvertEmptyStringsToNull();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertNull($request->input('user.name'));
        self::assertSame('test@example.com', $request->input('user.email'));
        self::assertNull($request->input('address.street'));
        self::assertSame('New York', $request->input('address.city'));
    }

    public function testConvertEmptyStringsMiddlewareDoesNotConvertNonStringValues(): void
    {
        $request = Request::create('/test', 'POST', [
            'age' => 0,
            'is_active' => false,
            'tags' => [],
            'name' => '',
        ]);

        $middleware = new ConvertEmptyStringsToNull();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $request->input('age'));
        self::assertFalse($request->input('is_active'));
        self::assertSame([], $request->input('tags'));
        self::assertNull($request->input('name'));
    }

    public function testConvertEmptyStringsMiddlewarePassesRequestSuccessfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new ConvertEmptyStringsToNull();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
