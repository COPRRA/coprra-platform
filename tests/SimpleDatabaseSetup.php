<?php

declare(strict_types=1);

namespace Tests;

trait SimpleDatabaseSetup
{
    /**
     * Set up a minimal database for testing without conflicts.
     */
    protected function setUpSimpleDatabase(): void
    {
        // Only set up if we're using SQLite in memory
        $connection = config('database.default', 'sqlite');

        if (! \in_array($connection, ['sqlite', 'testing', 'sqlite_testing'], true)) {
            return;
        }

        // Create minimal tables needed for basic tests
        $this->createMinimalTables();
    }

    /**
     * Create only the essential tables for testing.
     */
    protected function createMinimalTables(): void
    {
        try {
            // Disable foreign key constraints to avoid conflicts
            \DB::statement('PRAGMA foreign_keys=OFF');

            // Create basic users table
            \DB::statement('
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');

            // Create basic products table
            \DB::statement('
                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10,2) DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');

            // Create migrations table to satisfy Laravel
            \DB::statement('
                CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration VARCHAR(255) NOT NULL,
                    batch INTEGER NOT NULL
                )
            ');
        } catch (\Throwable $e) {
            // Silently ignore table creation errors
            // Tests should work even if tables already exist
        }
    }

    /**
     * Clean up database after tests.
     */
    protected function cleanupSimpleDatabase(): void
    {
        try {
            // Only clean up if we're using SQLite in memory
            $connection = config('database.default', 'sqlite');

            if (\in_array($connection, ['sqlite', 'testing', 'sqlite_testing'], true)) {
                // For in-memory SQLite, no cleanup needed
                // Tables will be destroyed when connection closes
            }
        } catch (\Throwable $e) {
            // Silently ignore cleanup errors
        }
    }
}
