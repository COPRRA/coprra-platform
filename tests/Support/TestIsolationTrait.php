<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;

/**
 * Trait for ensuring proper test isolation.
 */
trait TestIsolationTrait
{
    /**
     * Backup of original global state.
     */
    private array $originalGlobals = [];

    /**
     * Setup test isolation.
     */
    protected function setUpTestIsolation(): void
    {
        $this->backupGlobalState();
        $this->clearCaches();
        $this->resetSingletons();
    }

    /**
     * Cleanup after test.
     */
    protected function tearDownTestIsolation(): void
    {
        $this->restoreGlobalState();
        $this->clearCaches();
        $this->closeMockery();
        $this->resetSingletons();
    }

    /**
     * Reset database connections.
     */
    protected function resetDatabaseConnections(): void
    {
        if (app()->bound('db')) {
            try {
                DB::purge();
            } catch (\Exception $e) {
                // Ignore database reset errors
            }
        }
    }

    /**
     * Clear queued jobs.
     */
    protected function clearQueuedJobs(): void
    {
        if (app()->bound('queue')) {
            try {
                Queue::purge();
            } catch (\Exception $e) {
                // Ignore queue clearing errors
            }
        }
    }

    /**
     * Reset application state for clean test environment.
     */
    protected function resetApplicationState(): void
    {
        $this->clearCaches();
        $this->resetDatabaseConnections();
        $this->clearQueuedJobs();
        $this->resetSingletons();
    }

    /**
     * Verify test isolation by checking for common pollution sources.
     */
    protected function verifyTestIsolation(): void
    {
        // Check for leftover mocks
        $this->assertEmpty(
            \Mockery::getContainer()->getMocks(),
            'Mockery mocks were not properly cleaned up'
        );

        // Check for modified global state
        $this->assertEmpty(
            array_diff_key($_ENV, $this->originalGlobals['env']),
            'Global $_ENV was modified during test'
        );

        // Check for database transactions
        if (app()->bound('db')) {
            $this->assertEquals(
                0,
                DB::transactionLevel(),
                'Database transaction was not properly closed'
            );
        }
    }

    /**
     * Force garbage collection to clean up memory.
     */
    protected function forceGarbageCollection(): void
    {
        if (\function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Backup global state before test.
     */
    private function backupGlobalState(): void
    {
        $this->originalGlobals = [
            'env' => $_ENV ?? [],
            'server' => $_SERVER ?? [],
            'session' => $_SESSION ?? [],
            'cookie' => $_COOKIE ?? [],
        ];
    }

    /**
     * Restore global state after test.
     */
    private function restoreGlobalState(): void
    {
        $_ENV = $this->originalGlobals['env'] ?? [];
        $_SERVER = $this->originalGlobals['server'] ?? [];
        $_SESSION = $this->originalGlobals['session'] ?? [];
        $_COOKIE = $this->originalGlobals['cookie'] ?? [];
    }

    /**
     * Clear all caches.
     */
    private function clearCaches(): void
    {
        if (app()->bound('cache')) {
            try {
                Cache::flush();
            } catch (\Exception $e) {
                // Ignore cache clearing errors in tests
            }
        }
    }

    /**
     * Reset singletons that might affect test isolation.
     */
    private function resetSingletons(): void
    {
        // Reset common singletons
        $singletons = [
            'config',
            'db',
            'cache',
            'queue',
            'log',
            'events',
        ];

        foreach ($singletons as $singleton) {
            if (app()->bound($singleton)) {
                try {
                    app()->forgetInstance($singleton);
                } catch (\Exception $e) {
                    // Ignore singleton reset errors
                }
            }
        }
    }

    /**
     * Close Mockery to prevent test pollution.
     */
    private function closeMockery(): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }
    }
}
