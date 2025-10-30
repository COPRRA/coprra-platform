<?php

declare(strict_types=1);

namespace App\Services;

final class TestAnalysisService
{
    private static bool $coverageEnabled = false;

    public static function create(): self
    {
        self::$coverageEnabled = false;

        return new self();
    }
}
