<?php

declare(strict_types=1);

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext, PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.SelfOutsideClassScopeFound

namespace App\Enums;

use App\Traits\HasPermissionUtilities;
use App\Traits\HasStatusUtilities;

enum UserRole: string
{
    use HasPermissionUtilities;
    use HasStatusUtilities;

    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
    case GUEST = 'guest';

    /**
     * Get the display name for the role.
     */
    #[\Override]
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'مدير',
            self::USER => 'مستخدم',
            self::MODERATOR => 'مشرف',
            self::GUEST => 'ضيف',
        };
    }

    /**
     * Get the color for the role (for UI).
     */
    #[\Override]
    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::MODERATOR => 'blue',
            self::USER => 'green',
            self::GUEST => 'gray',
        };
    }

    /**
     * Get allowed role transitions.
     *
     * @return array<int, UserRole>
     */
    #[\Override]
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::GUEST => [self::USER],
            self::USER => [self::MODERATOR],
            self::MODERATOR => [self::ADMIN],
            self::ADMIN => [],
        };
    }

    /**
     * Check if role can transition to target role.
     */
    #[\Override]
    public function canTransitionTo(self $targetRole): bool
    {
        return \in_array($targetRole, $this->allowedTransitions(), true);
    }

    /**
     * Get permissions for the role.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'users.create',
                'users.read',
                'users.update',
                'users.delete',
                'orders.create',
                'orders.read',
                'orders.update',
                'orders.delete',
                'products.create',
                'products.read',
                'products.update',
                'products.delete',
                'settings.read',
                'settings.update',
            ],
            self::MODERATOR => [
                'users.read',
                'users.update',
                'orders.read',
                'orders.update',
                'products.read',
                'products.update',
            ],
            self::USER => [
                'orders.read',
                'products.read',
            ],
            self::GUEST => [
                'products.read',
            ],
        };
    }

    /**
     * Check if role has a specific permission.
     */
    #[\Override]
    public function hasPermission(string $permission): bool
    {
        return \in_array($permission, $this->permissions(), true);
    }

    /**
     * Check if role is admin.
     */
    #[\Override]
    public function isAdmin(): bool
    {
        return self::ADMIN === $this;
    }

    /**
     * Check if role is moderator or higher.
     */
    public function isModerator(): bool
    {
        return \in_array($this, [self::ADMIN, self::MODERATOR], true);
    }

    /**
     * Get options as value-label pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
// phpcs:enable
