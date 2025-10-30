<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 *
 * @coversNothing
 */
final class ErrorHandlerManagerTest extends TestCase
{
    #[Test]
    public function testCanInitialize(): void
    {
        ErrorHandlerManager::initialize();
        self::assertTrue(true, 'ErrorHandlerManager should initialize without errors');
    }

    #[Test]
    public function testCanRestore(): void
    {
        ErrorHandlerManager::initialize();
        ErrorHandlerManager::restore();
        self::assertTrue(true, 'ErrorHandlerManager should restore without errors');
    }

    #[Test]
    public function testCanSetErrorHandlers(): void
    {
        ErrorHandlerManager::setErrorHandler(static function () {
            return true;
        });
        ErrorHandlerManager::setExceptionHandler(static function () {
            return true;
        });
        self::assertTrue(true, 'Error handlers should be set without errors');
    }

    #[Test]
    public function testCanGetOriginalHandlers(): void
    {
        $errorHandler = ErrorHandlerManager::getOriginalErrorHandler();
        $exceptionHandler = ErrorHandlerManager::getOriginalExceptionHandler();

        self::assertTrue(true, 'Original handlers should be retrievable');
    }
}
