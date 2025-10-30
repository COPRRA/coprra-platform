<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\NullHandler;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait CreatesApplication
{
    /**
     * Application instance cache.
     */
    protected static ?Application $applicationInstance = null;

    /**
     * Test environment configuration cache.
     */
    protected static array $testConfig = [];

    /**
     * Database connection cache.
     */
    protected static array $databaseConnections = [];

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // Use cached application instance for better performance
        if (null !== static::$applicationInstance && ! $this->shouldRecreateApplication()) {
            return static::$applicationInstance;
        }

        // Set essential environment variables for testing
        $this->setTestEnvironmentVariables();

        // Create new application instance
        $app = require __DIR__.'/../bootstrap/app.php';

        // Bootstrap the application
        $app->make(Kernel::class)->bootstrap();

        // Ensure config service is bound after bootstrap
        $this->ensureConfigServiceIsBound($app);

        // Configure application for testing
        $this->configureApplicationForTesting($app);

        // Set up optimized database configuration
        $this->setupOptimizedDatabaseConfiguration($app);

        // Configure logging for testing
        $this->configureTestLogging($app);

        // Set up caching for testing
        $this->configureTestCaching($app);

        // Configure session for testing
        $this->configureTestSession($app);

        // Set up mail configuration for testing
        $this->configureTestMail($app);

        // Configure queue for testing
        $this->configureTestQueue($app);

        // Set up broadcasting for testing
        $this->configureTestBroadcasting($app);

        // Configure filesystem for testing
        $this->configureTestFilesystem($app);

        // Bind silent console input/output to prevent interactive prompts
        $this->bindSilentConsoleIO($app);

        // Cache the application instance
        static::$applicationInstance = $app;

        return $app;
    }

    /**
     * Set essential environment variables for testing.
     */
    protected function setTestEnvironmentVariables(): void
    {
        // Set application key if not already set
        if (! env('APP_KEY')) {
            putenv('APP_KEY=base64:'.base64_encode(random_bytes(32)));
        }

        // Set test environment
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=false');

        // Disable external services
        putenv('MAIL_MAILER=array');
        putenv('QUEUE_CONNECTION=sync');
        putenv('SESSION_DRIVER=array');
        putenv('CACHE_DRIVER=array');

        // Set secure defaults
        putenv('BCRYPT_ROUNDS=4'); // Faster password hashing for tests
        putenv('HASH_DRIVER=bcrypt');
    }

    /**
     * Configure application for testing environment.
     */
    protected function configureApplicationForTesting(Application $app): void
    {
        // Ensure config service is available before using it
        if (! $app->bound('config')) {
            return;
        }

        // Clear cached configuration to ensure fresh config for tests
        $config = $app->make('config');
        $config->set([
            'app.debug' => false,
            'app.env' => 'testing',
            'app.log_level' => 'emergency',
        ]);

        // Disable maintenance mode
        $config->set('app.maintenance', false);

        // Set timezone for consistent testing
        $config->set('app.timezone', 'UTC');

        // Configure locale for testing
        $config->set('app.locale', 'en');
        $config->set('app.fallback_locale', 'en');
    }

    /**
     * Set up optimized database configuration for testing.
     */
    protected function setupOptimizedDatabaseConfiguration(Application $app): void
    {
        // Ensure config service is available
        if (! $app->bound('config')) {
            return;
        }

        $config = $app->make('config');

        // Use in-memory SQLite for faster tests
        $config->set('database.default', 'sqlite');
        $config->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
            'journal_mode' => 'MEMORY',
            'synchronous' => 'OFF',
            'temp_store' => 'MEMORY',
            'mmap_size' => 268435456, // 256MB
            'cache_size' => 10000,
            'locking_mode' => 'EXCLUSIVE',
        ]);

        // Configure MySQL for testing if needed
        $config->set('database.connections.mysql_testing', [
            'driver' => 'mysql',
            'host' => env('DB_TEST_HOST', '127.0.0.1'),
            'port' => env('DB_TEST_PORT', '3306'),
            'database' => env('DB_TEST_DATABASE', 'testing'),
            'username' => env('DB_TEST_USERNAME', 'root'),
            'password' => env('DB_TEST_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => 'InnoDB',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        ]);

        // Configure PostgreSQL for testing if needed
        $config->set('database.connections.pgsql_testing', [
            'driver' => 'pgsql',
            'host' => env('DB_TEST_HOST', '127.0.0.1'),
            'port' => env('DB_TEST_PORT', '5432'),
            'database' => env('DB_TEST_DATABASE', 'testing'),
            'username' => env('DB_TEST_USERNAME', 'postgres'),
            'password' => env('DB_TEST_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
    }

    /**
     * Configure logging for testing.
     */
    protected function configureTestLogging(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('logging', [
            'default' => 'null',
            'deprecations' => 'null',
            'channels' => [
                'null' => [
                    'driver' => 'monolog',
                    'handler' => NullHandler::class,
                ],
                'testing' => [
                    'driver' => 'single',
                    'path' => storage_path('logs/testing.log'),
                    'level' => 'emergency',
                ],
            ],
        ]);
    }

    /**
     * Configure caching for testing.
     */
    protected function configureTestCaching(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('cache', [
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'serialize' => false,
                ],
                'file' => [
                    'driver' => 'file',
                    'path' => storage_path('framework/cache/data'),
                ],
            ],
            'prefix' => 'test_cache',
        ]);
    }

    /**
     * Configure session for testing.
     */
    protected function configureTestSession(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('session', [
            'driver' => 'array',
            'lifetime' => 120,
            'expire_on_close' => false,
            'encrypt' => false,
            'files' => storage_path('framework/sessions'),
            'connection' => null,
            'table' => 'sessions',
            'store' => null,
            'lottery' => [2, 100],
            'cookie' => 'test_session',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'lax',
        ]);
    }

    /**
     * Configure mail for testing.
     */
    protected function configureTestMail(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('mail', [
            'default' => 'array',
            'mailers' => [
                'array' => [
                    'transport' => 'array',
                ],
                'log' => [
                    'transport' => 'log',
                    'channel' => 'null',
                ],
            ],
            'from' => [
                'address' => 'test@example.com',
                'name' => 'Test Application',
            ],
        ]);
    }

    /**
     * Configure queue for testing.
     */
    protected function configureTestQueue(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('queue', [
            'default' => 'sync',
            'connections' => [
                'sync' => [
                    'driver' => 'sync',
                ],
                'array' => [
                    'driver' => 'array',
                ],
            ],
            'failed' => [
                'driver' => 'array',
            ],
        ]);
    }

    /**
     * Configure broadcasting for testing.
     */
    protected function configureTestBroadcasting(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('broadcasting', [
            'default' => 'null',
            'connections' => [
                'null' => [
                    'driver' => 'null',
                ],
                'log' => [
                    'driver' => 'log',
                ],
            ],
        ]);
    }

    /**
     * Configure filesystem for testing.
     */
    protected function configureTestFilesystem(Application $app): void
    {
        if (! $app->bound('config')) {
            return;
        }

        $app->make('config')->set('filesystems', [
            'default' => 'local',
            'disks' => [
                'local' => [
                    'driver' => 'local',
                    'root' => storage_path('app'),
                ],
                'public' => [
                    'driver' => 'local',
                    'root' => storage_path('app/public'),
                    'url' => env('APP_URL').'/storage',
                    'visibility' => 'public',
                ],
                'testing' => [
                    'driver' => 'local',
                    'root' => storage_path('app/testing'),
                ],
            ],
        ]);
    }

    /**
     * Bind silent console input/output to prevent interactive prompts during tests.
     */
    protected function bindSilentConsoleIO(Application $app): void
    {
        $app->bind(InputInterface::class, static function () {
            $input = new ArrayInput([]);
            $input->setInteractive(false);

            return $input;
        });

        $app->bind(OutputInterface::class, static function () {
            return new NullOutput();
        });
    }

    /**
     * Determine if the application should be recreated.
     */
    protected function shouldRecreateApplication(): bool
    {
        // Recreate if environment changed
        if ('testing' !== app()->environment()) {
            return true;
        }

        // Recreate if configuration changed
        $currentConfig = $this->getCurrentTestConfig();
        if (static::$testConfig !== $currentConfig) {
            static::$testConfig = $currentConfig;

            return true;
        }

        return false;
    }

    /**
     * Get current test configuration for comparison.
     */
    protected function getCurrentTestConfig(): array
    {
        return [
            'app_env' => env('APP_ENV'),
            'app_debug' => env('APP_DEBUG'),
            'db_connection' => env('DB_CONNECTION'),
            'cache_driver' => env('CACHE_DRIVER'),
            'session_driver' => env('SESSION_DRIVER'),
            'queue_connection' => env('QUEUE_CONNECTION'),
            'mail_mailer' => env('MAIL_MAILER'),
        ];
    }

    /**
     * Reset application instance (useful for tests that need fresh application).
     */
    protected function resetApplication(): void
    {
        static::$applicationInstance = null;
        static::$testConfig = [];
        static::$databaseConnections = [];
    }

    /**
     * Get optimized database connection for testing.
     */
    protected function getOptimizedDatabaseConnection(string $connection = 'sqlite'): array
    {
        if (isset(static::$databaseConnections[$connection])) {
            return static::$databaseConnections[$connection];
        }

        $config = match ($connection) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3306',
                'database' => 'testing',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'testing',
                'username' => 'postgres',
                'password' => '',
                'charset' => 'utf8',
            ],
            default => throw new \InvalidArgumentException("Unsupported database connection: {$connection}"),
        };

        static::$databaseConnections[$connection] = $config;

        return $config;
    }

    /**
     * Ensure database is properly configured and optimized.
     */
    protected function ensureDatabaseOptimization(): void
    {
        if ('sqlite' === config('database.default')) {
            try {
                DB::statement('PRAGMA synchronous = OFF');
                DB::statement('PRAGMA journal_mode = MEMORY');
                DB::statement('PRAGMA temp_store = MEMORY');
                DB::statement('PRAGMA cache_size = 10000');
                DB::statement('PRAGMA locking_mode = EXCLUSIVE');
            } catch (\Exception $e) {
                // Silently ignore pragma errors in case of connection issues
            }
        }
    }

    /**
     * Clear application caches for testing.
     */
    protected function clearApplicationCaches(): void
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
        } catch (\Exception $e) {
            // Silently ignore cache clearing errors during testing
        }
    }

    /**
     * Ensure config service is properly bound in the container.
     */
    protected function ensureConfigServiceIsBound(Application $app): void
    {
        if (! $app->bound('config')) {
            // Manually bind the config repository if it's not already bound
            $app->singleton('config', function ($app) {
                $config = new Repository();

                // Load essential configuration files
                $configPath = $app->configPath();
                if (file_exists($configPath.'/app.php')) {
                    $config->set('app', require $configPath.'/app.php');
                }
                if (file_exists($configPath.'/database.php')) {
                    $config->set('database', require $configPath.'/database.php');
                }

                return $config;
            });
        }
    }
}
