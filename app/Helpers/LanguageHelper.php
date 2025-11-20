<?php

declare(strict_types=1);

if (! function_exists('getLanguageFlag')) {
    function getLanguageFlag($code)
    {
        $flags = [
            'ar' => '🇸🇦',
            'en' => '🇺🇸',
            'es' => '🇪🇸',
            'fr' => '🇫🇷',
            'de' => '🇩🇪',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'pt' => '🇧🇷',
            'ru' => '🇷🇺',
            'it' => '🇮🇹',
            'tr' => '🇹🇷',
            'nl' => '🇳🇱',
            'pl' => '🇵🇱',
            'ko' => '🇰🇷',
        ];

        return $flags[$code] ?? '🇮🇳';
    }
}
