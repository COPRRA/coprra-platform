<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    // Ignore packages that are intentionally present but not referenced by symbols
    $config->addNamedFilter(NamedFilter::fromString('blade-ui-kit/blade-heroicons'));
    $config->addNamedFilter(NamedFilter::fromString('livewire/livewire'));
    $config->addNamedFilter(NamedFilter::fromString('spatie/laravel-backup'));
    $config->addNamedFilter(NamedFilter::fromString('doctrine/dbal'));
    $config->addNamedFilter(NamedFilter::fromString('spatie/laravel-permission'));
    $config->addNamedFilter(NamedFilter::fromString('darkaonline/l5-swagger')); // Used for API documentation
    $config->addNamedFilter(NamedFilter::fromString('sentry/sentry-laravel')); // Used for error monitoring and tracking

    return $config;
};
