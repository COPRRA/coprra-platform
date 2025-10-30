<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class BackupPolicy
{
    /**
     * Determine whether the user can view backups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create backups.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can download backups.
     */
    public function download(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete backups.
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore from backups.
     */
    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
