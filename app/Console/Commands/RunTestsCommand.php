<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class RunTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {--filter= : Filter tests by name pattern}
                                {--testsuite= : Run only the specified test suite}
                                {--group= : Run only tests from the given group}
                                {--stop-on-failure : Stop execution upon first failure}
                                {--coverage-html= : Generate HTML coverage report in the given path}
                                {--coverage-clover= : Generate Clover coverage report in the given path}
                                {--without-tty : Disable TTY mode for the PHPUnit process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the application\'s automated tests (proxy to vendor/bin/phpunit).';

    public function handle(): int
    {
        $binary = $this->resolvePhpUnitBinary();

        if ($binary === null) {
            $this->error('Unable to locate PHPUnit binary at vendor/bin/phpunit.');

            return self::FAILURE;
        }

        $command = array_merge([$binary], $this->buildArguments());

        $environment = array_merge($_SERVER, $_ENV, [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'PERMISSION_CACHE_STORE' => 'array',
        ]);

        $process = new Process($command, base_path(), $environment);

        if (! $this->option('without-tty') && Process::isTtySupported() && $this->output->isDecorated()) {
            $process->setTty(true);
        }

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            return $process->getExitCode() ?? self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the phpunit executable path.
     */
    private function resolvePhpUnitBinary(): ?string
    {
        $binary = base_path('vendor/bin/phpunit');

        if (DIRECTORY_SEPARATOR === '\\') {
            $binary .= '.bat';
        }

        return is_file($binary) ? $binary : null;
    }

    /**
     * Build the list of CLI arguments to forward to PHPUnit.
     *
     * @return array<int, string>
     */
    private function buildArguments(): array
    {
        $arguments = [];

        $forwardOptions = [
            'filter',
            'testsuite',
            'group',
            'coverage-html',
            'coverage-clover',
        ];

        foreach ($forwardOptions as $option) {
            $value = $this->option($option);

            if ($value !== null && $value !== '') {
                $arguments[] = '--'.$option;
                $arguments[] = (string) $value;
            }
        }

        if ($this->option('stop-on-failure')) {
            $arguments[] = '--stop-on-failure';
        }

        return $arguments;
    }
}
