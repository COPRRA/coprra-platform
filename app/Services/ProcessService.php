<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ProcessResult;
use Illuminate\Support\Facades\Process;

final class ProcessService
{
    /**
     * Run a process command.
     *
     * @param array<string>|string $command
     */
    public function run(array|string $command): ProcessResult
    {
        // Set timeout for long-running commands like Pint
        $timeout = 300; // 5 minutes
        if (\is_string($command) && str_contains($command, 'pint')) {
            $timeout = 300;
        }

        $result = Process::timeout($timeout)->run($command);

        // Pre-populate defaults
        $output = $result->output();
        $errorOutput = $result->errorOutput();
        $exitCode = $result->exitCode() ?? 0;

        // For git commands, stderr often contains success messages, not errors
        $isGitCommand = \is_string($command) && str_starts_with($command, 'git');

        if ($isGitCommand) {
            // Check if the stderr contains success messages and normalize accordingly
            $isSuccessMessage = str_contains($errorOutput, 'Switched to a new branch')
                || str_contains($errorOutput, 'Branch created')
                || str_contains($errorOutput, 'successfully');

            if ($isSuccessMessage) {
                $output = '' !== $output ? $output : $errorOutput;
                $errorOutput = '';
                $exitCode = 0;
            }
        }

        return new ProcessResult(
            $exitCode,
            $output,
            $errorOutput
        );
    }
}
