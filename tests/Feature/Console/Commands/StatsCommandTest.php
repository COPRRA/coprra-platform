<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class StatsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function statsCommandRunsSuccessfullyWithoutDetailedOption(): void
    {
        $this->artisan('stats')->assertExitCode(0);
    }

    #[Test]
    public function statsCommandRunsSuccessfullyWithDetailedOption(): void
    {
        $this->artisan('stats', ['--detailed' => true])->assertExitCode(0);
    }

    #[Test]
    public function statsCommandDisplaysCorrectBasicStats(): void
    {
        $this->artisan('stats')
            ->expectsOutputToContain('Basic Stats')
            ->assertExitCode(0)
        ;
    }

    #[Test]
    public function statsCommandDisplaysDetailedStatsWhenRequested(): void
    {
        $this->artisan('stats', ['--detailed' => true])
            ->expectsOutputToContain('Detailed Stats')
            ->assertExitCode(0)
        ;
    }

    #[Test]
    public function statsCommandHandlesEmptyDatabase(): void
    {
        $this->artisan('stats')->assertExitCode(0);
    }

    #[Test]
    public function statsCommandHandlesDetailedStatsWithEmptyDatabase(): void
    {
        $this->artisan('stats', ['--detailed' => true])->assertExitCode(0);
    }

    #[Test]
    public function statsCommandDisplaysRecentActivity(): void
    {
        $this->artisan('stats')
            ->expectsOutputToContain('Recent Activity')
            ->assertExitCode(0)
        ;
    }

    #[Test]
    public function statsCommandDisplaysTopCategories(): void
    {
        $this->artisan('stats', ['--detailed' => true])
            ->expectsOutputToContain('Top Categories')
            ->assertExitCode(0);
    }
}
