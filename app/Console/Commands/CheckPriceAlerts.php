<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PriceCheckerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to check price alerts and send notifications.
 */
final class CheckPriceAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active price alerts and send notifications for triggered alerts';

    public function __construct(
        private readonly PriceCheckerService $priceCheckerService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting price alerts check...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            $stats = $this->priceCheckerService->checkAlerts();

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info('Price alerts check completed!');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Alerts Checked', $stats['total_checked']],
                    ['Products Checked', $stats['products_checked']],
                    ['Alerts Triggered', $stats['alerts_triggered']],
                    ['Notifications Sent', $stats['notifications_sent']],
                    ['Errors', $stats['errors']],
                    ['Execution Time', $executionTime . 's'],
                ]
            );

            Log::info('Price alerts check completed', [
                'stats' => $stats,
                'execution_time' => $executionTime,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to check price alerts: ' . $e->getMessage());
            
            Log::error('Price alerts check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

