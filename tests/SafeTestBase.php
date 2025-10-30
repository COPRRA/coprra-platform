<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\DB;

/**
 * Safe test base class that handles database setup without RefreshDatabase conflicts.
 * Use this for tests that need database access but encounter transaction conflicts.
 */
abstract class SafeTestBase extends TestCase
{
    protected static bool $databaseSetup = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$databaseSetup) {
            $this->setUpDatabase();
            static::$databaseSetup = true;
        }

        // Clear data before each test instead of using transactions
        $this->clearTestData();
    }

    /**
     * Set up the database for testing.
     */
    protected function setUpDatabase(): void
    {
        $connection = config('database.default', 'sqlite');

        try {
            // Test connection
            DB::connection($connection)->select('SELECT 1');
        } catch (\Exception $e) {
            // If connection fails, use in-memory SQLite
            config(['database.default' => 'sqlite']);
            config(['database.connections.sqlite.database' => ':memory:']);
            $connection = 'sqlite';
        }

        $this->createTablesInConnection($connection);
    }

    /**
     * Create tables in a specific connection.
     */
    protected function createTablesInConnection(?string $connection = null): void
    {
        $conn = $connection ?: config('database.default', 'sqlite');
        DB::connection($conn)->statement('PRAGMA foreign_keys = OFF');

        $this->createUsersTable($conn);
        $this->createProductsTable($conn);
        $this->createCategoriesTable($conn);
        $this->createBrandsTable($conn);
        $this->createStoresTable($conn);
        $this->createLanguagesTable($conn);
        $this->createCurrenciesTable($conn);
        $this->createExchangeRatesTable($conn);
        $this->createMigrationsTable($conn);
        $this->createPasswordResetTokensTable($conn);
        $this->createPersonalAccessTokensTable($conn);

        DB::connection($conn)->statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Clear test data before each test.
     */
    protected function clearTestData(): void
    {
        // Clear tables in reverse order to respect foreign keys
        $tables = [
            'personal_access_tokens',
            'password_reset_tokens',
            'exchange_rates',
            'currencies',
            'languages',
            'stores',
            'brands',
            'categories',
            'products',
            'users',
        ];

        foreach ($tables as $table) {
            try {
                DB::table($table)->truncate();
            } catch (\Exception $e) {
                // Table might not exist or be empty, continue
            }
        }
    }

    // Table creation methods (copied from DatabaseSetup trait)

    protected function createUsersTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'users')) {
            DB::connection($connection)->statement('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    email_verified_at TIMESTAMP NULL,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createProductsTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'products')) {
            DB::connection($connection)->statement('
                CREATE TABLE products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    category_id INTEGER NULL,
                    brand_id INTEGER NULL,
                    store_id INTEGER NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createCategoriesTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'categories')) {
            DB::connection($connection)->statement('
                CREATE TABLE categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    parent_id INTEGER NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createBrandsTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'brands')) {
            DB::connection($connection)->statement('
                CREATE TABLE brands (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createStoresTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'stores')) {
            DB::connection($connection)->statement('
                CREATE TABLE stores (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createLanguagesTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'languages')) {
            DB::connection($connection)->statement('
                CREATE TABLE languages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(10) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    is_active BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createCurrenciesTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'currencies')) {
            DB::connection($connection)->statement('
                CREATE TABLE currencies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(10) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    symbol VARCHAR(10) NOT NULL,
                    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
                    decimal_places INTEGER DEFAULT 2,
                    is_active BOOLEAN DEFAULT 1,
                    is_default BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createExchangeRatesTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'exchange_rates')) {
            DB::connection($connection)->statement('
                CREATE TABLE exchange_rates (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    from_currency VARCHAR(3) NOT NULL,
                    to_currency VARCHAR(3) NOT NULL,
                    rate DECIMAL(15,8) NOT NULL,
                    source VARCHAR(50) NULL,
                    fetched_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    UNIQUE(from_currency, to_currency)
                )
            ');
        }
    }

    protected function createMigrationsTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'migrations')) {
            DB::connection($connection)->statement('
                CREATE TABLE migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL
                )
            ');
        }
    }

    protected function createPasswordResetTokensTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'password_reset_tokens')) {
            DB::connection($connection)->statement('
                CREATE TABLE password_reset_tokens (
                    email VARCHAR(255) PRIMARY KEY,
                    token VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NULL
                )
            ');
        }
    }

    protected function createPersonalAccessTokensTable(string $connection): void
    {
        if (! $this->tableExists($connection, 'personal_access_tokens')) {
            DB::connection($connection)->statement('
                CREATE TABLE personal_access_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tokenable_type VARCHAR(255) NOT NULL,
                    tokenable_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    token VARCHAR(64) UNIQUE NOT NULL,
                    abilities TEXT NULL,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }
    }

    /**
     * Check if a table exists in the given connection.
     */
    protected function tableExists(string $connection, string $table): bool
    {
        try {
            $result = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);

            return ! empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
}
