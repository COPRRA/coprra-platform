<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Simple test to check if PHPUnit is working.
 *
 * @internal
 *
 * @coversNothing
 */
final class SimpleTest extends TestCase
{
    /**
     * Test that basic assertions work.
     */
    public function testBasicAssertion(): void
    {
        self::assertTrue(true);
        self::assertSame(1, 1);
        self::assertSame('hello', 'hello');
    }

    /**
     * Test that array operations work.
     */
    public function testArrayOperations(): void
    {
        $array = [1, 2, 3];
        self::assertCount(3, $array);
        self::assertContains(2, $array);
    }

    /**
     * Test that string operations work.
     */
    public function testStringOperations(): void
    {
        self::assertStringContainsString('world', 'hello world');
        self::assertStringStartsWith('hello', 'hello world');
    }
}
