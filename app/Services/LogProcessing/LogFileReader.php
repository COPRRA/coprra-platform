<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

class LogFileReader
{
    /**
     * Read file content.
     */
    public function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);

        return false !== $content ? $content : '';
    }
}
