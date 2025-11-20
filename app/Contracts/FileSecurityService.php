<?php

declare(strict_types=1);

namespace App\Contracts;

interface FileSecurityService
{
    /**
     * Get file security statistics.
     *
     * @return array<string, array<int, string>|float|int>
     */
    public function getStatistics(): array;
}
