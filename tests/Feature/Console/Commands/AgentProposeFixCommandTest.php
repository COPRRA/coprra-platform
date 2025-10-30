<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AgentProposeFixCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function agentProposeFixCommandRunsSuccessfullyWithStyleOption(): void
    {
        Process::fake();
        $this->artisan('agent:propose-fix', ['--type' => 'style'])->assertExitCode(0);
    }

    #[Test]
    public function agentProposeFixCommandRunsSuccessfullyWithAnalysisOption(): void
    {
        Process::fake();
        $this->artisan('agent:propose-fix', ['--type' => 'analysis'])->assertExitCode(0);
    }

    #[Test]
    public function agentProposeFixCommandHandlesUnsupportedType(): void
    {
        $this->artisan('agent:propose-fix', ['--type' => 'invalid'])->assertExitCode(1);
    }
}
