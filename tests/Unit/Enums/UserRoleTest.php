<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserRoleTest extends TestCase
{
    public function testEnumHasAllExpectedCases(): void
    {
        $cases = UserRole::cases();

        self::assertCount(4, $cases);
        self::assertContains(UserRole::ADMIN, $cases);
        self::assertContains(UserRole::USER, $cases);
        self::assertContains(UserRole::MODERATOR, $cases);
        self::assertContains(UserRole::GUEST, $cases);
    }

    public function testEnumValuesAreCorrect(): void
    {
        self::assertSame('admin', UserRole::ADMIN->value);
        self::assertSame('user', UserRole::USER->value);
        self::assertSame('moderator', UserRole::MODERATOR->value);
        self::assertSame('guest', UserRole::GUEST->value);
    }

    public function testLabelReturnsCorrectArabicText(): void
    {
        self::assertSame('مدير', UserRole::ADMIN->label());
        self::assertSame('مستخدم', UserRole::USER->label());
        self::assertSame('مشرف', UserRole::MODERATOR->label());
        self::assertSame('ضيف', UserRole::GUEST->label());
    }

    public function testAdminHasAllPermissions(): void
    {
        $permissions = UserRole::ADMIN->permissions();

        self::assertContains('users.create', $permissions);
        self::assertContains('users.read', $permissions);
        self::assertContains('users.update', $permissions);
        self::assertContains('users.delete', $permissions);
        self::assertContains('products.create', $permissions);
        self::assertContains('orders.create', $permissions);
        self::assertContains('settings.read', $permissions);
        self::assertContains('settings.update', $permissions);
    }

    public function testModeratorHasLimitedPermissions(): void
    {
        $permissions = UserRole::MODERATOR->permissions();

        self::assertContains('products.read', $permissions);
        self::assertContains('products.update', $permissions);
        self::assertContains('orders.read', $permissions);
        self::assertContains('orders.update', $permissions);
        self::assertNotContains('users.create', $permissions);
        self::assertNotContains('users.delete', $permissions);
        self::assertNotContains('settings.update', $permissions);
    }

    public function testUserHasBasicPermissions(): void
    {
        $permissions = UserRole::USER->permissions();

        self::assertContains('orders.read', $permissions);
        self::assertContains('products.read', $permissions);
        self::assertNotContains('products.create', $permissions);
        self::assertNotContains('products.update', $permissions);
        self::assertNotContains('users.create', $permissions);
    }

    public function testGuestHasMinimalPermissions(): void
    {
        $permissions = UserRole::GUEST->permissions();

        self::assertContains('products.read', $permissions);
        self::assertNotContains('orders.read', $permissions);
        self::assertNotContains('orders.create', $permissions);
        self::assertNotContains('users.read', $permissions);
    }

    public function testHasPermissionReturnsTrueForValidPermission(): void
    {
        self::assertTrue(UserRole::ADMIN->hasPermission('users.create'));
        self::assertTrue(UserRole::MODERATOR->hasPermission('products.update'));
        self::assertTrue(UserRole::USER->hasPermission('orders.read'));
        self::assertTrue(UserRole::GUEST->hasPermission('products.read'));
    }

    public function testHasPermissionReturnsFalseForInvalidPermission(): void
    {
        self::assertFalse(UserRole::USER->hasPermission('users.create'));
        self::assertFalse(UserRole::GUEST->hasPermission('orders.create'));
        self::assertFalse(UserRole::MODERATOR->hasPermission('settings.update'));
    }

    public function testIsAdminReturnsTrueOnlyForAdmin(): void
    {
        self::assertTrue(UserRole::ADMIN->isAdmin());
        self::assertFalse(UserRole::MODERATOR->isAdmin());
        self::assertFalse(UserRole::USER->isAdmin());
        self::assertFalse(UserRole::GUEST->isAdmin());
    }

    public function testIsModeratorReturnsTrueForAdminAndModerator(): void
    {
        self::assertTrue(UserRole::ADMIN->isModerator());
        self::assertTrue(UserRole::MODERATOR->isModerator());
        self::assertFalse(UserRole::USER->isModerator());
        self::assertFalse(UserRole::GUEST->isModerator());
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $array = UserRole::toArray();

        self::assertIsArray($array);
        self::assertArrayHasKey('ADMIN', $array);
        self::assertSame('admin', $array['ADMIN']);
        self::assertArrayHasKey('USER', $array);
        self::assertSame('user', $array['USER']);
    }

    public function testOptionsReturnsValueLabelPairs(): void
    {
        $options = UserRole::options();

        self::assertIsArray($options);
        self::assertArrayHasKey('admin', $options);
        self::assertSame('مدير', $options['admin']);
        self::assertArrayHasKey('user', $options);
        self::assertSame('مستخدم', $options['user']);
    }

    public function testCanCreateFromString(): void
    {
        $role = UserRole::from('admin');
        self::assertSame(UserRole::ADMIN, $role);

        $role = UserRole::from('user');
        self::assertSame(UserRole::USER, $role);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $role = UserRole::tryFrom('invalid');
        self::assertNull($role);
    }
}
