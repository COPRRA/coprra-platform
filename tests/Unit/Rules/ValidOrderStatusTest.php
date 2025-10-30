<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\ValidOrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValidOrderStatusTest extends TestCase
{
    private ValidOrderStatus $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidOrderStatus();
    }

    public function testPassesWithValidStatus(): void
    {
        $failCalled = false;
        $fail = static function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 'pending', $fail);

        self::assertFalse($failCalled);
    }

    public function testFailsWithInvalidStatus(): void
    {
        $failCalled = false;
        $fail = static function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 'invalid_status', $fail);

        self::assertTrue($failCalled);
    }

    public function testFailsWithNonStringValue(): void
    {
        $failCalled = false;
        $fail = static function () use (&$failCalled) {
            $failCalled = true;
        };

        $this->rule->validate('status', 123, $fail);

        self::assertTrue($failCalled);
    }

    public function testPassesWithAllValidStatuses(): void
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

        foreach ($validStatuses as $status) {
            $failCalled = false;
            $fail = static function () use (&$failCalled) {
                $failCalled = true;
            };

            $this->rule->validate('status', $status, $fail);

            self::assertFalse($failCalled, "Status '{$status}' should be valid");
        }
    }
}
