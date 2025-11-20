<?php

declare(strict_types=1);

namespace App\Traits;

trait HasPermissionUtilities
{
    /**
     * Get permissions for the role.
     *
     * @return array<string, string>
     */
    abstract public function permissions(): array;

    /**
     * Check if role has a specific permission.
     */
    abstract public function hasPermission(string $permission): bool;

    /**
     * Check if role is admin.
     */
    abstract public function isAdmin(): bool;
}
