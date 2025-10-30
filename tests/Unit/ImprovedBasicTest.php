<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ImprovedBasicTest extends TestCase
{
    public function testApplicationReturnsSuccessfulResponse()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function testEnvironmentIsTesting()
    {
        self::assertSame('testing', app()->environment());
    }

    public function testConfigIsLoaded()
    {
        self::assertNotNull(config('app.name'));
        self::assertNotNull(config('app.key'));
    }
}
