<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\DB;

trait DatabaseSetup
{
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

        // Disable foreign key constraints during table creation
        DB::connection($conn)->statement('PRAGMA foreign_keys=OFF;');
        DB::connection($conn)->statement('PRAGMA auto_vacuum=0;');

        // Create essential tables for testing
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

        // Re-enable foreign key constraints
        DB::connection($conn)->statement('PRAGMA foreign_keys=ON;');
    }

    /**
     * Create users table.
     */
    protected function createUsersTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE,
                    email_verified_at DATETIME,
                    password VARCHAR(255) NOT NULL,
                    password_confirmed_at DATETIME,
                    is_admin BOOLEAN DEFAULT 0,
                    phone VARCHAR(20),
                    role VARCHAR(255) DEFAULT "user",
                    is_blocked BOOLEAN DEFAULT 0,
                    ban_reason VARCHAR(255),
                    ban_description TEXT,
                    banned_at DATETIME,
                    ban_expires_at DATETIME,
                    is_active BOOLEAN DEFAULT 1,
                    session_id VARCHAR(255),
                    remember_token VARCHAR(100),
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create products table.
     */
    protected function createProductsTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='products'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL,
                    category_id INTEGER,
                    brand_id INTEGER,
                    store_id INTEGER,
                    currency_id INTEGER,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create categories table.
     */
    protected function createCategoriesTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='categories'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    parent_id INTEGER,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create brands table.
     */
    protected function createBrandsTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='brands'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE brands (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    logo_url VARCHAR(255),
                    website_url VARCHAR(255),
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create stores table.
     */
    protected function createStoresTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='stores'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE stores (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    url VARCHAR(255),
                    logo_url VARCHAR(255),
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create languages table.
     */
    protected function createLanguagesTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='languages'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE languages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(10) NOT NULL UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create currencies table.
     */
    protected function createCurrenciesTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='currencies'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE currencies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(10) NOT NULL UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    symbol VARCHAR(10),
                    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
                    decimal_places INTEGER DEFAULT 2,
                    is_active BOOLEAN DEFAULT 1,
                    is_default BOOLEAN DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create migrations table.
     */
    protected function createMigrationsTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL
                )
            ');
        }
    }

    /**
     * Create password_reset_tokens table.
     */
    protected function createPasswordResetTokensTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='password_reset_tokens'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE password_reset_tokens (
                    email VARCHAR(255) PRIMARY KEY,
                    token VARCHAR(255) NOT NULL,
                    created_at DATETIME
                )
            ');
        }
    }

    /**
     * Create personal_access_tokens table.
     */
    protected function createPersonalAccessTokensTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='personal_access_tokens'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE personal_access_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tokenable_type VARCHAR(255) NOT NULL,
                    tokenable_id INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    abilities TEXT,
                    last_used_at DATETIME,
                    expires_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create exchange_rates table.
     */
    protected function createExchangeRatesTable(string $connection): void
    {
        $exists = DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='exchange_rates'");
        if (empty($exists)) {
            DB::connection($connection)->statement('
                CREATE TABLE exchange_rates (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    from_currency VARCHAR(3) NOT NULL,
                    to_currency VARCHAR(3) NOT NULL,
                    rate DECIMAL(20,10) NOT NULL,
                    source VARCHAR(255) DEFAULT "manual",
                    fetched_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME,
                    UNIQUE(from_currency, to_currency)
                )
            ');
        }
    }

    /**
     * Tear down the database after testing.
     */
    protected function tearDownDatabase(): void
    {
        // Restore any error handlers that might have been set
        restore_error_handler();
        restore_exception_handler();

        // Clear any database connections
        DB::purge();
    }
}
