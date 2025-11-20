<?php

declare(strict_types=1);

namespace App\Contracts;

interface PasswordResetService
{
    /**
     * Send password reset email.
     */
    public function sendResetEmail(string $email): bool;

    /**
     * Reset password with token.
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool;

    /**
     * Check if reset token exists.
     */
    public function hasResetToken(string $email): bool;

    /**
     * Get reset token info.
     *
     * @return array<string, string|int|* @method static \App\Models\Brand create(array<string, string|bool|null>|null
     */
    public function getResetTokenInfo(string $email): ?array;

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int;

    /**
     * Get password reset statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array;
}
