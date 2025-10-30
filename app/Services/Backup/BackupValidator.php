<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BackupValidator
{
    /**
     * Validate backup creation request.
     *
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public function validateBackupRequest(Request $request): array
    {
        $validated = $request->validate([
            'type' => 'required|in:full,database,files',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
        ]);

        return [
            'type' => $validated['type'],
            'name' => $validated['name'] ?? 'backup_'.now()->format('Y-m-d_H-i-s'),
            'description' => $validated['description'] ?? '',
        ];
    }
}
