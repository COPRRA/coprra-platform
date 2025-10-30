<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigServiceBindingTest extends TestCase
{
    public function testConfigServiceIsAvailable(): void
    {
        // This test verifies that the config service binding fix works
        self::assertTrue($this->app->bound('config'));

        $config = $this->app->make('config');
        self::assertNotNull($config);

        // Test that we can set and get config values
        $config->set('test.value', 'test_data');
        self::assertSame('test_data', $config->get('test.value'));
    }

    public function testApplicationEnvironmentIsSetToTesting(): void
    {
        self::assertSame('testing', $this->app->environment());
    }
}
