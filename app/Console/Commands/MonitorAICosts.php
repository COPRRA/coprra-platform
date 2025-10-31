<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\ModelVersionTracker;
use Illuminate\Console\Command;

class MonitorAICosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:monitor-costs
                            {--model= : Specific model to check}
                            {--detailed : Show detailed breakdown}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor AI usage costs and statistics';

    /**
     * Execute the console command.
     */
    public function handle(ModelVersionTracker $tracker): int
    {
        $this->info('🤖 AI Usage & Cost Monitor');
        $this->newLine();

        $models = ['gpt-4', 'gpt-4-vision', 'gpt-3.5-turbo', 'claude-3', 'claude-3-vision'];

        if ($this->option('model')) {
            $models = [$this->option('model')];
        }

        $totalCost = 0;
        $totalRequests = 0;
        $totalTokens = 0;

        $tableData = [];

        foreach ($models as $model) {
            $metrics = $tracker->getModelMetrics($model);

            if ($metrics['total_requests'] > 0) {
                $tableData[] = [
                    $model,
                    $metrics['total_requests'],
                    number_format($metrics['success_rate'], 2).'%',
                    number_format($metrics['average_response_time'], 3).'s',
                    number_format($metrics['total_tokens']),
                    '$'.number_format($metrics['total_cost'], 4),
                ];

                $totalCost += $metrics['total_cost'];
                $totalRequests += $metrics['total_requests'];
                $totalTokens += $metrics['total_tokens'];
            }
        }

        if (empty($tableData)) {
            $this->warn('⚠️  No AI usage data found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Model', 'Requests', 'Success Rate', 'Avg Response', 'Tokens', 'Cost'],
            $tableData
        );

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("   Total Requests: {$totalRequests}");
        $this->line('   Total Tokens: '.number_format($totalTokens));
        $this->line('   Total Cost: $'.number_format($totalCost, 4));

        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('📈 Detailed Breakdown:');

            foreach ($models as $model) {
                $metrics = $tracker->getModelMetrics($model);
                if ($metrics['total_requests'] > 0) {
                    $this->newLine();
                    $this->line("  <fg=cyan>{$model}:</>");
                    $this->line("    Total Requests: {$metrics['total_requests']}");
                    $this->line("    Successful: {$metrics['successful_requests']}");
                    $this->line("    Failed: {$metrics['failed_requests']}");
                    $this->line('    Success Rate: '.number_format($metrics['success_rate'], 2).'%');
                    $this->line('    Avg Response Time: '.number_format($metrics['average_response_time'], 3).'s');
                    $this->line('    Total Tokens: '.number_format($metrics['total_tokens']));
                    $this->line('    Total Cost: $'.number_format($metrics['total_cost'], 4));
                }
            }
        }

        // Cost warnings
        $this->newLine();
        if ($totalCost > 10) {
            $this->error("⚠️  High cost alert: {$totalCost} - Consider optimizing AI usage!");
        } elseif ($totalCost > 5) {
            $this->warn("⚡ Moderate cost: {$totalCost} - Monitor closely");
        } else {
            $this->info("✅ Cost within normal range: {$totalCost}");
        }

        return self::SUCCESS;
    }
}
