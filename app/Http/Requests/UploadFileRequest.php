<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function sanitizeFilename(string $original, string $extension): string
    {
        $base = pathinfo($original, \PATHINFO_FILENAME);
        $sanitized = str($base)->slug('-')->toString();
        $uuid = bin2hex(random_bytes(16));
        $ext = strtolower($extension);

        return $sanitized.'-'.$uuid.'.'.$ext;
    }
}
