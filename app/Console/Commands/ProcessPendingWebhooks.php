<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property string $signature
 * @property string $description
 */
final class ProcessPendingWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhooks:process {--limit=100 : Maximum number of webhooks to process} {--retry-failed : Also retry failed webhooks}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending webhooks';

    /**
     * Execute the console command.
     */
    public function handle(WebhookService $webhookService): int
    {
        $this->info('🔄 Processing pending webhooks...');

        $limit = (int) $this->option('limit');
        $retryFailed = (bool) $this->option('retry-failed');

        $webhooks = $this->getWebhooksToProcess($limit, $retryFailed);

        if ($this->handleEmptyWebhooks($webhooks)) {
            return self::SUCCESS;
        }

        $this->processWebhooks($webhooks, $webhookService);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Webhook>
     */
    private function getWebhooksToProcess(int $limit, bool $retryFailed): Collection
    {
        $query = Webhook::where('status', Webhook::STATUS_PENDING)->orderBy('created_at');

        if ($retryFailed) {
            $query->orWhere('status', Webhook::STATUS_FAILED);
        }

        return $query->limit($limit)->get();
    }

    /**
     * @param Collection<int, Webhook> $webhooks
     */
    private function handleEmptyWebhooks(Collection $webhooks): bool
    {
        if ($webhooks->isEmpty()) {
            $this->info('✅ No pending webhooks to process.');

            return true;
        }

        return false;
    }

    /**
     * Process the given webhooks.
     *
     * @param Collection<int, Webhook> $webhooks
     */
    private function processWebhooks(Collection $webhooks, WebhookService $webhookService): void
    {
        $webhookCount = $webhooks->count();
        $this->info("Found {$webhookCount} webhooks to process.");

        $progressBar = $this->output->createProgressBar($webhookCount);
        $progressBar->start();

        $processed = 0;
        $failed = 0;

        foreach ($webhooks as $webhook) {
            try {
                $webhookService->processWebhook($webhook);
                ++$processed;
            } catch (\Throwable $exception) {
                ++$failed;
                $this->error("\nFailed to process webhook {$webhook->id}: {$exception->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Processed: {$processed}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
    }
}
