<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderStatusTest extends TestCase
{
    public function testEnumHasAllExpectedCases(): void
    {
        $cases = OrderStatus::cases();

        self::assertCount(6, $cases);
        self::assertContains(OrderStatus::PENDING, $cases);
        self::assertContains(OrderStatus::PROCESSING, $cases);
        self::assertContains(OrderStatus::SHIPPED, $cases);
        self::assertContains(OrderStatus::DELIVERED, $cases);
        self::assertContains(OrderStatus::CANCELLED, $cases);
        self::assertContains(OrderStatus::REFUNDED, $cases);
    }

    public function testEnumValuesAreCorrect(): void
    {
        self::assertSame('pending', OrderStatus::PENDING->value);
        self::assertSame('processing', OrderStatus::PROCESSING->value);
        self::assertSame('shipped', OrderStatus::SHIPPED->value);
        self::assertSame('delivered', OrderStatus::DELIVERED->value);
        self::assertSame('cancelled', OrderStatus::CANCELLED->value);
        self::assertSame('refunded', OrderStatus::REFUNDED->value);
    }

    public function testLabelReturnsCorrectArabicText(): void
    {
        self::assertSame('قيد الانتظار', OrderStatus::PENDING->label());
        self::assertSame('قيد المعالجة', OrderStatus::PROCESSING->label());
        self::assertSame('تم الشحن', OrderStatus::SHIPPED->label());
        self::assertSame('تم التسليم', OrderStatus::DELIVERED->label());
        self::assertSame('ملغي', OrderStatus::CANCELLED->label());
        self::assertSame('مسترد', OrderStatus::REFUNDED->label());
    }

    public function testColorReturnsCorrectValues(): void
    {
        self::assertSame('yellow', OrderStatus::PENDING->color());
        self::assertSame('blue', OrderStatus::PROCESSING->color());
        self::assertSame('purple', OrderStatus::SHIPPED->color());
        self::assertSame('green', OrderStatus::DELIVERED->color());
        self::assertSame('red', OrderStatus::CANCELLED->color());
        self::assertSame('orange', OrderStatus::REFUNDED->color());
    }

    public function testAllowedTransitionsFromPending(): void
    {
        $transitions = OrderStatus::PENDING->allowedTransitions();

        self::assertCount(2, $transitions);
        self::assertContains(OrderStatus::PROCESSING, $transitions);
        self::assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function testAllowedTransitionsFromProcessing(): void
    {
        $transitions = OrderStatus::PROCESSING->allowedTransitions();

        self::assertCount(2, $transitions);
        self::assertContains(OrderStatus::SHIPPED, $transitions);
        self::assertContains(OrderStatus::CANCELLED, $transitions);
    }

    public function testAllowedTransitionsFromShipped(): void
    {
        $transitions = OrderStatus::SHIPPED->allowedTransitions();

        self::assertCount(1, $transitions);
        self::assertContains(OrderStatus::DELIVERED, $transitions);
    }

    public function testNoTransitionsFromFinalStatuses(): void
    {
        self::assertEmpty(OrderStatus::DELIVERED->allowedTransitions());
        self::assertEmpty(OrderStatus::CANCELLED->allowedTransitions());
        self::assertEmpty(OrderStatus::REFUNDED->allowedTransitions());
    }

    public function testCanTransitionToAllowedStatus(): void
    {
        self::assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::PROCESSING));
        self::assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
        self::assertTrue(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::SHIPPED));
        self::assertTrue(OrderStatus::SHIPPED->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function testCannotTransitionToDisallowedStatus(): void
    {
        self::assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED));
        self::assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED));
        self::assertFalse(OrderStatus::PROCESSING->canTransitionTo(OrderStatus::DELIVERED));
        self::assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PENDING));
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $array = OrderStatus::toArray();

        self::assertIsArray($array);
        self::assertArrayHasKey('PENDING', $array);
        self::assertSame('pending', $array['PENDING']);
        self::assertArrayHasKey('PROCESSING', $array);
        self::assertSame('processing', $array['PROCESSING']);
    }

    public function testOptionsReturnsValueLabelPairs(): void
    {
        $options = OrderStatus::options();

        self::assertIsArray($options);
        self::assertArrayHasKey('pending', $options);
        self::assertSame('قيد الانتظار', $options['pending']);
        self::assertArrayHasKey('processing', $options);
        self::assertSame('قيد المعالجة', $options['processing']);
    }

    public function testCanCreateFromString(): void
    {
        $status = OrderStatus::from('pending');
        self::assertSame(OrderStatus::PENDING, $status);

        $status = OrderStatus::from('shipped');
        self::assertSame(OrderStatus::SHIPPED, $status);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $status = OrderStatus::tryFrom('invalid');
        self::assertNull($status);
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        OrderStatus::from('invalid');
    }
}
