<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SimpleMockeryTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function testMockBasicFunctionality(): void
    {
        // Arrange
        $mock = \Mockery::mock();
        $mock->shouldReceive('getValue')->andReturn('mocked value');

        // Act
        $result = $mock->getValue();

        // Assert
        self::assertSame('mocked value', $result);
    }

    #[Test]
    public function testMockExpectedBehaviorWithParameters(): void
    {
        // Arrange
        $mock = \Mockery::mock();
        $mock->shouldReceive('processData')
            ->with('input')
            ->andReturn('processed output')
        ;

        // Act
        $result = $mock->processData('input');

        // Assert
        self::assertSame('processed output', $result);
    }

    #[Test]
    public function testMockValidationThrowsException(): void
    {
        // Arrange
        $mock = \Mockery::mock();
        $mock->shouldReceive('validate')
            ->with('invalid')
            ->andThrow(new \InvalidArgumentException('Invalid input'))
        ;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input');
        $mock->validate('invalid');
    }
}
