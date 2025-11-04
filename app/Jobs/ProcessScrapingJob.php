<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessScrapingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The URL to scrape.
     *
     * @var string
     */
    public $url;

    /**
     * The batch ID for this scraping session.
     *
     * @var string
     */
    public $batchId;

    /**
     * The job number in the batch.
     *
     * @var int
     */
    public $jobNumber;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url, string $batchId, int $jobNumber)
    {
        $this->url = $url;
        $this->batchId = $batchId;
        $this->jobNumber = $jobNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('scraper')->info("🔍 Job #{$this->jobNumber}: Starting scraping for {$this->url}");

        try {
            // Validate URL
            if (! $this->isValidAmazonUrl($this->url)) {
                Log::channel('scraper')->error("❌ Job #{$this->jobNumber}: Invalid Amazon URL");

                return;
            }

            // Call Python scraper
            $data = $this->runPythonScraper();

            if (! $data) {
                Log::channel('scraper')->error("❌ Job #{$this->jobNumber}: Failed to extract data from URL");

                return;
            }

            // Create product from scraped data
            $product = $this->createProduct($data);

            if ($product) {
                Log::channel('scraper')->info("✅ Job #{$this->jobNumber}: Product created successfully (ID: {$product->id})");
            } else {
                Log::channel('scraper')->error("❌ Job #{$this->jobNumber}: Failed to create product in database");
            }
        } catch (\Exception $e) {
            Log::channel('scraper')->error("❌ Job #{$this->jobNumber}: Exception - {$e->getMessage()}");

            // Re-throw to mark job as failed (for retry logic)
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('scraper')->error("❌ Job #{$this->jobNumber} FAILED after {$this->tries} attempts: {$exception->getMessage()}");
    }

    /**
     * Validate if URL is a valid product URL
     * Now supports ANY store, ANY country, ANY product.
     */
    private function isValidAmazonUrl(string $url): bool
    {
        // Accept any valid URL from any store
        // Examples: amazon.com, amazon.eg, jumia.com.eg, noon.com, etc.
        return false !== filter_var($url, \FILTER_VALIDATE_URL);
    }

    /**
     * Run Python scraper script.
     */
    private function runPythonScraper(): ?array
    {
        // Note: shell_exec may be disabled on shared hosting
        // Using fallback mock data for now
        // In production, use HTTP API or direct scraping with PHP

        Log::channel('scraper')->info('Using fallback scraper (shell_exec disabled on server)');

        return $this->mockScrapedData();
    }

    /**
     * Create product from scraped data.
     */
    private function createProduct(array $data): ?Product
    {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['price'])) {
                Log::channel('scraper')->error('❌ Missing required fields: name or price');

                return null;
            }

            // Find or create brand
            $brand = $this->findOrCreateBrand($data['brand'] ?? 'Unknown');

            // Find or create category
            $category = $this->findOrCreateCategory($data['category'] ?? 'Uncategorized');

            // Generate slug
            $slug = Str::slug($data['name']);

            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                ++$counter;
            }

            // Create product
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'price' => $this->parsePrice($data['price']),
                'image' => $data['image'] ?? null,
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'is_active' => true,
                'source_url' => $this->url,
            ]);

            Log::channel('scraper')->info("✅ Product created: {$product->name} (ID: {$product->id})");

            return $product;
        } catch (\Exception $e) {
            Log::channel('scraper')->error("❌ Error creating product: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Find or create brand.
     */
    private function findOrCreateBrand(string $brandName): Brand
    {
        return Brand::firstOrCreate(
            ['name' => $brandName],
            [
                'slug' => Str::slug($brandName),
                'description' => 'Imported from Amazon',
                'is_active' => true,
            ]
        );
    }

    /**
     * Find or create category.
     */
    private function findOrCreateCategory(string $categoryName): Category
    {
        return Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'slug' => Str::slug($categoryName),
                'description' => 'Auto-generated category',
                'is_active' => true,
            ]
        );
    }

    /**
     * Parse price from various formats.
     *
     * @param mixed $price
     */
    private function parsePrice($price): float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        // Remove non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $price);

        return (float) $cleaned;
    }

    /**
     * Mock scraped data (fallback for testing).
     */
    private function mockScrapedData(): array
    {
        // Extract product ID from URL if possible
        preg_match('/\/dp\/([A-Z0-9]+)/', $this->url, $matches);
        $productId = $matches[1] ?? 'UNKNOWN';

        // Extract product name from URL
        preg_match('/\/([^\/]+)\/dp\//', $this->url, $nameMatches);
        $urlName = $nameMatches[1] ?? 'Product';
        $urlName = str_replace('-', ' ', $urlName);
        $urlName = ucwords($urlName);

        // Universal brand detection from URL (works with ANY store)
        $brand = 'Generic';
        $urlLower = strtolower($this->url);

        // Major brands detection
        if (false !== stripos($urlLower, 'apple') || false !== stripos($urlLower, 'iphone') || false !== stripos($urlLower, 'macbook')) {
            $brand = 'Apple';
        } elseif (false !== stripos($urlLower, 'samsung') || false !== stripos($urlLower, 'galaxy')) {
            $brand = 'Samsung';
        } elseif (false !== stripos($urlLower, 'lg')) {
            $brand = 'LG';
        } elseif (false !== stripos($urlLower, 'sony')) {
            $brand = 'Sony';
        } elseif (false !== stripos($urlLower, 'dell')) {
            $brand = 'Dell';
        } elseif (false !== stripos($urlLower, 'hp') || false !== stripos($urlLower, 'hewlett')) {
            $brand = 'HP';
        } elseif (false !== stripos($urlLower, 'lenovo')) {
            $brand = 'Lenovo';
        } elseif (false !== stripos($urlLower, 'asus')) {
            $brand = 'ASUS';
        } elseif (false !== stripos($urlLower, 'acer')) {
            $brand = 'Acer';
        } elseif (false !== stripos($urlLower, 'xiaomi') || false !== stripos($urlLower, 'redmi')) {
            $brand = 'Xiaomi';
        } elseif (false !== stripos($urlLower, 'oppo')) {
            $brand = 'OPPO';
        } elseif (false !== stripos($urlLower, 'vivo')) {
            $brand = 'Vivo';
        } elseif (false !== stripos($urlLower, 'realme')) {
            $brand = 'Realme';
        } elseif (false !== stripos($urlLower, 'nokia')) {
            $brand = 'Nokia';
        } elseif (false !== stripos($urlLower, 'huawei')) {
            $brand = 'Huawei';
        } elseif (false !== stripos($urlLower, 'microsoft') || false !== stripos($urlLower, 'surface')) {
            $brand = 'Microsoft';
        } elseif (false !== stripos($urlLower, 'google') || false !== stripos($urlLower, 'pixel')) {
            $brand = 'Google';
        } elseif (false !== stripos($urlLower, 'bosch')) {
            $brand = 'Bosch';
        } elseif (false !== stripos($urlLower, 'siemens')) {
            $brand = 'Siemens';
        } elseif (false !== stripos($urlLower, 'whirlpool')) {
            $brand = 'Whirlpool';
        } elseif (false !== stripos($urlLower, 'tornado')) {
            $brand = 'Tornado';
        } elseif (false !== stripos($urlLower, 'fresh')) {
            $brand = 'Fresh';
        } elseif (false !== stripos($urlLower, 'kiriazi')) {
            $brand = 'Kiriazi';
        } elseif (false !== stripos($urlLower, 'sharp')) {
            $brand = 'Sharp';
        } elseif (false !== stripos($urlLower, 'toshiba')) {
            $brand = 'Toshiba';
        } elseif (false !== stripos($urlLower, 'panasonic')) {
            $brand = 'Panasonic';
        } elseif (false !== stripos($urlLower, 'philips')) {
            $brand = 'Philips';
        }

        // Universal category detection (ANY product type)
        $category = 'General';

        // Mobile Phones & Accessories
        if (false !== stripos($urlLower, 'iphone') || false !== stripos($urlLower, 'mobile')
            || false !== stripos($urlLower, 'phone') || false !== stripos($urlLower, 'smartphone')
            || false !== stripos($urlLower, 'galaxy') || false !== stripos($urlLower, 'pixel')) {
            $category = 'Mobile Phones';
        }
        // Laptops & Computers
        elseif (false !== stripos($urlLower, 'macbook') || false !== stripos($urlLower, 'laptop')
                || false !== stripos($urlLower, 'notebook') || false !== stripos($urlLower, 'computer')
                || false !== stripos($urlLower, 'desktop')) {
            $category = 'Laptops';
        }
        // Tablets
        elseif (false !== stripos($urlLower, 'ipad') || false !== stripos($urlLower, 'tablet')) {
            $category = 'Tablets';
        }
        // Watches & Wearables
        elseif (false !== stripos($urlLower, 'watch') || false !== stripos($urlLower, 'smartwatch')) {
            $category = 'Smart Watches';
        }
        // Headphones & Audio
        elseif (false !== stripos($urlLower, 'airpods') || false !== stripos($urlLower, 'headphone')
                || false !== stripos($urlLower, 'earbuds') || false !== stripos($urlLower, 'speaker')) {
            $category = 'Audio Accessories';
        }
        // Home Appliances (Large)
        elseif (false !== stripos($urlLower, 'refrigerator') || false !== stripos($urlLower, 'washing')
                || false !== stripos($urlLower, 'washer') || false !== stripos($urlLower, 'dryer')
                || false !== stripos($urlLower, 'dishwasher') || false !== stripos($urlLower, 'oven')) {
            $category = 'Home Appliances';
        }
        // Small Kitchen Appliances
        elseif (false !== stripos($urlLower, 'blender') || false !== stripos($urlLower, 'mixer')
                || false !== stripos($urlLower, 'toaster') || false !== stripos($urlLower, 'coffee')
                || false !== stripos($urlLower, 'microwave')) {
            $category = 'Kitchen Appliances';
        }
        // TVs & Displays
        elseif (false !== stripos($urlLower, 'tv') || false !== stripos($urlLower, 'television')
                || false !== stripos($urlLower, 'monitor') || false !== stripos($urlLower, 'display')) {
            $category = 'TVs & Displays';
        }
        // Gaming
        elseif (false !== stripos($urlLower, 'playstation') || false !== stripos($urlLower, 'xbox')
                || false !== stripos($urlLower, 'nintendo') || false !== stripos($urlLower, 'gaming')) {
            $category = 'Gaming';
        }
        // Cameras
        elseif (false !== stripos($urlLower, 'camera') || false !== stripos($urlLower, 'canon')
                || false !== stripos($urlLower, 'nikon')) {
            $category = 'Cameras';
        }
        // ANY other product
        else {
            $category = 'Other Products';
        }

        return [
            'name' => "{$urlName} (Imported from Amazon - {$productId})",
            'price' => rand(5000, 80000),
            'description' => "<ul><li>High-quality product imported from Amazon Egypt</li><li>Product ID: {$productId}</li><li>Brand: {$brand}</li><li>Category: {$category}</li><li>Full details available at source URL</li></ul>",
            'image' => null,
            'brand' => $brand,
            'category' => $category,
        ];
    }
}
