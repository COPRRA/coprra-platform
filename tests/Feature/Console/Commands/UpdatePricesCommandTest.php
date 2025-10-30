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
final class UpdatePricesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function updatePricesCommandRunsSuccessfully(): void
    {
        $this->artisan('prices:update')->assertExitCode(0);
    }

    #[Test]
    public function updatePricesCommandRunsInDryRunMode(): void
    {
        $this->artisan('prices:update', ['--dry-run' => true])->assertExitCode(0);
    }

    #[Test]
    public function updatePricesCommandFiltersByStore(): void
    {
        $this->artisan('prices:update', ['--store' => 1])->assertExitCode(0);
    }

    #[Test]
    public function updatePricesCommandFiltersByProduct(): void
    {
        $this->artisan('prices:update', ['--product' => 1])->assertExitCode(0);
    }
}
