<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class PasswordHistoryService
{
    /**
     * التحقق من وجود كلمة المرور في التاريخ.
     */
    public function isPasswordInHistory(string $password, int $userId): bool
    {
        try {
            $history = $this->getPasswordHistory($userId);

            foreach ($history as $oldPassword) {
                if (Hash::check($password, $oldPassword)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Password history check failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * الحصول على تاريخ كلمات المرور.
     *
     * @return array<string>
     *
     * @psalm-return list<string>
     */
    private function getPasswordHistory(int $userId): array
    {
        $history = Cache::get("password_history_{$userId}", []);

        // تأكيد أن كل عنصر نصي فقط
        if (! \is_array($history)) {
            return [];
        }

        return array_values(array_filter($history, 'is_string'));
    }
}
