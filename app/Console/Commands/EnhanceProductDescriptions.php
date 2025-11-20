<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductDescriptionGenerator;
use Illuminate\Console\Command;

class EnhanceProductDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:enhance-descriptions
                            {--limit= : Number of products to enhance (default: all)}
                            {--brand= : Enhance only specific brand}
                            {--force : Force re-enhance already enhanced products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enhance product descriptions with detailed, SEO-friendly content';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Product Description Enhancement...');
        $this->newLine();

        // Build query
        $query = Product::with(['brand', 'category']);

        // Filter by brand if specified
        if ($brand = $this->option('brand')) {
            $query->whereHas('brand', static function ($q) use ($brand) {
                $q->where('name', 'LIKE', "%{$brand}%");
            });
            $this->info("📌 Filtering by brand: {$brand}");
        }

        // Filter by description length unless force option is used
        if (! $this->option('force')) {
            $query->whereRaw('LENGTH(description) < 300');
            $this->info('📌 Only enhancing products with descriptions < 300 characters');
        }

        // Apply limit if specified
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
            $this->info("📌 Limit: {$limit} products");
        }

        $products = $query->get();
        $total = $products->count();

        if (0 === $total) {
            $this->warn('⚠️  No products found matching criteria');

            return 0;
        }

        $this->info("📦 Found {$total} products to enhance");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                // Generate enhanced description
                $productData = [
                    'name' => $product->name,
                    'brand' => $product->brand->name ?? '',
                    'category' => $product->category->name ?? '',
                    'description' => $product->description ?? '',
                    'specs' => json_decode($product->specifications, true) ?? [],
                    'features' => json_decode($product->features, true) ?? [],
                ];

                $enhancedDescription = ProductDescriptionGenerator::generate($productData);

                // Update product
                $product->update([
                    'description' => $enhancedDescription,
                ]);

                ++$success;
            } catch (\Exception $e) {
                ++$failed;
                \Log::error("Failed to enhance product {$product->id}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Enhancement Complete!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $total],
                ['Successfully Enhanced', $success],
                ['Failed', $failed],
                ['Success Rate', round(($success / $total) * 100, 2).'%'],
            ]
        );

        $this->newLine();
        $this->info('🎉 All products have been enhanced with detailed descriptions!');

        return 0;
    }
}
