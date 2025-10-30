<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PermissionSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testPermissionSecurity(): void
    {
        self::assertTrue(true);
    }

    public function testRoleSecurity(): void
    {
        self::assertTrue(true);
    }

    public function testAccessControl(): void
    {
        self::assertTrue(true);
    }
}
