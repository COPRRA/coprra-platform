<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\PriceFetchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to update product prices from external stores.
 */
final class UpdateProductPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prices:update 
                            {--limit=100 : Maximum number of products to update}
                            {--country=US : Country code to fetch prices for}
                            {--force : Force update even if recently updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product prices from external stores';

    public function __construct(
        private readonly PriceFetchingService $priceFetchingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting product prices update...');

        $limit = (int) $this->option('limit');
        $country = (string) $this->option('country');
        $force = $this->option('force');

        // Get products to update
        $products = Product::query()
            ->where('is_active', true)
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No active products found to update.');
            return Command::SUCCESS;
        }

        $this->info("Found {$products->count()} products to update.");
        $this->newLine();

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                // Fetch live offers for this product
                $offers = $this->priceFetchingService->getLiveOffers($product, $country);

                if (!empty($offers)) {
                    // Store offers in cache for quick access
                    $cacheKey = "product_offers:{$product->id}:{$country}";
                    cache()->put($cacheKey, $offers, now()->addHours(24));

                    $updated++;
                } else {
                    $this->newLine();
                    $this->warn("No offers found for product: {$product->name} (ID: {$product->id})");
                }

                $bar->advance();
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to update prices for product', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'error' => $e->getMessage(),
                ]);

                $this->newLine();
                $this->error("Failed to update product {$product->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Update completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Failed', $failed],
                ['Total', $products->count()],
            ]
        );

        Log::info('Product prices update completed', [
            'updated' => $updated,
            'failed' => $failed,
            'total' => $products->count(),
            'country' => $country,
        ]);

        return Command::SUCCESS;
    }
}

