<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface UserBanService
{
    /**
     * Ban a user.
     */
    public function banUser(User $user, string $reason, ?string $description = null, ?Carbon $expiresAt = null): bool;

    /**
     * Unban a user.
     */
    public function unbanUser(User $user, ?string $reason = null): bool;

    /**
     * Check if user is banned.
     */
    public function isUserBanned(User $user): bool;

    /**
     * Get ban information.
     *
     * @return array<string, bool|string|* @method static \App\Models\Brand create(array<string, string|bool|null>|null
     */
    public function getBanInfo(User $user): ?array;

    /**
     * Get all banned users.
     *
     * @return Collection<int, User>
     */
    public function getBannedUsers(): Collection;

    /**
     * Get users with expired bans.
     *
     * @return Collection<int, User>
     */
    public function getUsersWithExpiredBans(): Collection;

    /**
     * Clean up expired bans.
     */
    public function cleanupExpiredBans(): int;

    /**
     * Get ban statistics.
     *
     * @return array<string, array<string, string>|int>
     */
    public function getBanStatistics(): array;

    /**
     * Get ban reasons.
     *
     * @return list<string>
     */
    public function getBanReasons(): array;

    /**
     * Check if user can be banned.
     */
    public function canBanUser(User $user): bool;

    /**
     * Check if user can be unbanned.
     */
    public function canUnbanUser(User $user): bool;

    /**
     * Get ban history for user.
     *
     * @return list<array<string, bool|string|* @method static \App\Models\Brand create(array<string, string|bool|null>>
     */
    public function getBanHistory(User $user): array;

    /**
     * Extend ban duration.
     */
    public function extendBan(User $user, Carbon $newExpiresAt, ?string $reason = null): bool;

    /**
     * Reduce ban duration.
     */
    public function reduceBan(User $user, Carbon $newExpiresAt, ?string $reason = null): bool;
}
