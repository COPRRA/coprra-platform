<?php

declare(strict_types=1);

use Icanhazstring\Composer\Unused\Configuration\Configuration;
use Icanhazstring\Composer\Unused\Configuration\Exclusion\PackageExclusion;
use Icanhazstring\Composer\Unused\Configuration\Exclusion\NamespaceExclusion;
use Icanhazstring\Composer\Unused\Configuration\Exclusion\PatternExclusion;
use Icanhazstring\Composer\Unused\Configuration\AdditionalFiles;

return Configuration::create()
    ->withAdditionalFiles(
        AdditionalFiles::create()
            ->withDirectories([
                // Application directories
                'app',
                'app/Console',
                'app/Events',
                'app/Exceptions',
                'app/Http',
                'app/Jobs',
                'app/Listeners',
                'app/Mail',
                'app/Models',
                'app/Notifications',
                'app/Policies',
                'app/Providers',
                'app/Rules',
                'app/Services',
                
                // Configuration and infrastructure
                'config',
                'bootstrap',
                'routes',
                
                // Resources
                'resources/views',
                'resources/js',
                'resources/css',
                'resources/lang',
                
                // Database
                'database/migrations',
                'database/seeders',
                'database/factories',
                
                // Tests
                'tests',
                'tests/Feature',
                'tests/Unit',
                'tests/Browser',
                'tests/Integration',
                'tests/Performance',
                'tests/Security',
                'tests/AI',
                'tests/API',
            ])
            ->withFiles([
                // Additional files that might use dependencies
                'artisan',
                'server.php',
                'webpack.mix.js',
                'vite.config.js',
                'tailwind.config.js',
                'postcss.config.js',
                '.php-cs-fixer.dist.php',
                'rector.php',
                'phpstan.neon',
                'psalm.xml',
                'phpinsights.php',
                'infection.json.dist',
                'deptrac.yaml',
                'phpmd.xml',
            ])
    )
    ->withExclusions(
        PackageExclusion::fromList([
            // Laravel core packages that might not be directly referenced
            'laravel/framework',
            'laravel/sanctum',
            'laravel/tinker',
            'laravel/sail',
            'laravel/breeze',
            'laravel/jetstream',
            'laravel/fortify',
            'laravel/cashier',
            'laravel/horizon',
            'laravel/nova',
            'laravel/passport',
            'laravel/scout',
            'laravel/socialite',
            'laravel/telescope',
            'laravel/vapor-core',
            'laravel/vapor-cli',
            
            // Livewire and related packages
            'livewire/livewire',
            'livewire/volt',
            'blade-ui-kit/blade-heroicons',
            'blade-ui-kit/blade-icons',
            'blade-ui-kit/blade-ui-kit',
            
            // Spatie packages
            'spatie/laravel-backup',
            'spatie/laravel-permission',
            'spatie/laravel-activitylog',
            'spatie/laravel-medialibrary',
            'spatie/laravel-query-builder',
            'spatie/laravel-data',
            'spatie/laravel-settings',
            'spatie/laravel-model-states',
            'spatie/laravel-event-sourcing',
            'spatie/laravel-multitenancy',
            'spatie/laravel-translatable',
            'spatie/laravel-sluggable',
            'spatie/laravel-tags',
            'spatie/laravel-searchable',
            'spatie/laravel-fractal',
            'spatie/laravel-cors',
            'spatie/laravel-cookie-consent',
            'spatie/laravel-honeypot',
            'spatie/laravel-csp',
            'spatie/laravel-sitemap',
            'spatie/laravel-feed',
            'spatie/laravel-analytics',
            'spatie/laravel-google-calendar',
            'spatie/laravel-newsletter',
            'spatie/laravel-uptime-monitor',
            'spatie/laravel-server-monitor',
            'spatie/laravel-schedule-monitor',
            'spatie/laravel-failed-job-monitor',
            'spatie/laravel-model-cleanup',
            'spatie/laravel-db-snapshots',
            'spatie/laravel-collection-macros',
            'spatie/laravel-enum',
            'spatie/laravel-validation-rules',
            'spatie/laravel-view-models',
            'spatie/laravel-webhook-client',
            'spatie/laravel-webhook-server',
            'spatie/laravel-image-optimizer',
            'spatie/laravel-pdf',
            'spatie/laravel-excel',
            'spatie/laravel-blade-x',
            'spatie/laravel-mix-purgecss',
            'spatie/laravel-mix-preload',
            
            // Database and ORM
            'doctrine/dbal',
            'doctrine/orm',
            'doctrine/migrations',
            'doctrine/cache',
            'doctrine/collections',
            'doctrine/common',
            'doctrine/event-manager',
            'doctrine/inflector',
            'doctrine/instantiator',
            'doctrine/lexer',
            
            // Payment gateways
            'stripe/stripe-php',
            'paypal/paypal-checkout-sdk',
            'paypal/paypalhttp',
            'square/square',
            'razorpay/razorpay',
            'mollie/laravel-mollie',
            'omnipay/omnipay',
            
            // Monitoring and logging
            'sentry/sentry-laravel',
            'bugsnag/bugsnag-laravel',
            'rollbar/rollbar-laravel',
            'monolog/monolog',
            'psr/log',
            
            // Testing packages
            'phpunit/phpunit',
            'mockery/mockery',
            'fakerphp/faker',
            'laravel/dusk',
            'orchestra/testbench',
            'orchestra/testbench-dusk',
            'pestphp/pest',
            'pestphp/pest-plugin-laravel',
            'pestphp/pest-plugin-livewire',
            'pestphp/pest-plugin-parallel',
            'codeception/codeception',
            'behat/behat',
            'phpspec/phpspec',
            
            // Development tools
            'barryvdh/laravel-debugbar',
            'barryvdh/laravel-ide-helper',
            'beyondcode/laravel-dump-server',
            'nunomaduro/collision',
            'nunomaduro/larastan',
            'nunomaduro/phpinsights',
            'friendsofphp/php-cs-fixer',
            'squizlabs/php_codesniffer',
            'phpstan/phpstan',
            'psalm/plugin-laravel',
            'vimeo/psalm',
            'phpmd/phpmd',
            'sebastian/phpcpd',
            'povils/phpmnd',
            'roave/security-advisories',
            'enlightn/security-checker',
            'local-php-security-checker/local-php-security-checker',
            'infection/infection',
            'qossmic/deptrac-shim',
            'rector/rector',
            'captainhook/captainhook',
            'captainhook/plugin-composer',
            'brianium/paratest',
            'spatie/laravel-ignition',
            'filp/whoops',
            
            // Frontend and asset management
            'laravel/ui',
            'laravel/mix',
            'inertiajs/inertia-laravel',
            'tightenco/ziggy',
            
            // API and serialization
            'league/fractal',
            'spatie/laravel-fractal',
            'jms/serializer',
            'symfony/serializer',
            
            // Utilities and helpers
            'nesbot/carbon',
            'ramsey/uuid',
            'league/flysystem',
            'league/csv',
            'league/commonmark',
            'erusev/parsedown',
            'michelf/php-markdown',
            'guzzlehttp/guzzle',
            'intervention/image',
            'maatwebsite/excel',
            'barryvdh/laravel-dompdf',
            'dompdf/dompdf',
            'mpdf/mpdf',
            'tecnickcom/tcpdf',
            'phpoffice/phpspreadsheet',
            'phpoffice/phpword',
            'phpoffice/phppresentation',
            
            // Caching and session
            'predis/predis',
            'illuminate/redis',
            'symfony/cache',
            
            // Queue and job processing
            'laravel/horizon',
            'pusher/pusher-php-server',
            'aws/aws-sdk-php',
            'google/cloud',
            
            // Internationalization
            'symfony/translation',
            'laravel/lang',
            
            // Security
            'paragonie/random_compat',
            'paragonie/sodium_compat',
            'firebase/php-jwt',
            'lcobucci/jwt',
            'tymon/jwt-auth',
            
            // HTTP and networking
            'symfony/http-foundation',
            'symfony/http-kernel',
            'psr/http-message',
            'psr/http-client',
            'psr/http-factory',
            'psr/http-server-handler',
            'psr/http-server-middleware',
            
            // Console and CLI
            'symfony/console',
            'symfony/process',
            
            // Validation and forms
            'symfony/validator',
            'respect/validation',
            
            // Event and messaging
            'symfony/event-dispatcher',
            'league/event',
            'pusher/pusher-php-server',
            
            // Template engines
            'twig/twig',
            'smarty/smarty',
            
            // Configuration and environment
            'vlucas/phpdotenv',
            'symfony/dotenv',
            
            // Dependency injection
            'illuminate/container',
            'symfony/dependency-injection',
            'psr/container',
            
            // Routing
            'symfony/routing',
            'nikic/fast-route',
            
            // Middleware
            'psr/http-server-middleware',
            'middlewares/utils',
            
            // Standards and interfaces
            'psr/cache',
            'psr/simple-cache',
            'psr/clock',
            'psr/event-dispatcher',
            'psr/link',
            
            // Polyfills
            'symfony/polyfill-ctype',
            'symfony/polyfill-iconv',
            'symfony/polyfill-intl-grapheme',
            'symfony/polyfill-intl-idn',
            'symfony/polyfill-intl-normalizer',
            'symfony/polyfill-mbstring',
            'symfony/polyfill-php72',
            'symfony/polyfill-php73',
            'symfony/polyfill-php74',
            'symfony/polyfill-php80',
            'symfony/polyfill-php81',
            'symfony/polyfill-php82',
            'symfony/polyfill-php83',
            'symfony/polyfill-uuid',
        ]),
        
        // Exclude by namespace patterns
        NamespaceExclusion::fromList([
            'Illuminate\\*',
            'Laravel\\*',
            'Livewire\\*',
            'Spatie\\*',
            'Symfony\\*',
            'Doctrine\\*',
            'Monolog\\*',
            'Psr\\*',
            'Carbon\\*',
            'Ramsey\\*',
            'League\\*',
            'GuzzleHttp\\*',
            'Intervention\\*',
            'Barryvdh\\*',
            'Sentry\\*',
            'Stripe\\*',
            'PayPal\\*',
            'PHPUnit\\*',
            'Mockery\\*',
            'Faker\\*',
            'Pest\\*',
            'Codeception\\*',
            'Behat\\*',
            'PhpCsFixer\\*',
            'PHPStan\\*',
            'Psalm\\*',
            'PHPMD\\*',
            'Infection\\*',
            'Rector\\*',
            'Deptrac\\*',
        ]),
        
        // Exclude by file patterns
        PatternExclusion::fromList([
            '*/vendor/*',
            '*/node_modules/*',
            '*/storage/*',
            '*/bootstrap/cache/*',
            '*/public/build/*',
            '*/public/hot',
            '*/public/storage',
            '*/.git/*',
            '*/.github/*',
            '*/.vscode/*',
            '*/.idea/*',
            '*/tmp/*',
            '*/temp/*',
            '*/cache/*',
            '*/.phpunit.cache/*',
            '*/.php-cs-fixer.cache',
            '*/infection.log',
            '*/coverage/*',
            '*/reports/*',
            '*/docs/*',
            '*/build/*',
            '*/dist/*',
        ])
    );
