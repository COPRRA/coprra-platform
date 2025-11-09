<?php

declare(strict_types=1);

namespace App\Services\StoreAdapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Mock Store Adapter for Development & Testing
 *
 * Returns realistic fake product data without requiring external API credentials.
 * This allows full end-to-end testing of the scraper engine.
 */
final class MockStoreAdapter extends StoreAdapter
{
    public function __construct(HttpFactory $http, CacheRepository $cache, LoggerInterface $logger)
    {
        parent::__construct($http, $cache, $logger);
    }

    /**
     * @psalm-return 'Mock Store'
     */
    public function getStoreName(): string
    {
        return 'Mock Store';
    }

    /**
     * @psalm-return 'mock'
     */
    #[\Override]
    public function getStoreIdentifier(): string
    {
        return 'mock';
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return true; // Always available
    }

    /**
     * Fetch product by URL - extracts data from URL and returns mock product data
     *
     * @return array<string, mixed>|null
     */
    #[\Override]
    public function fetchProduct(string $productIdentifier): ?array
    {
        $this->logger->info('MockStoreAdapter: Fetching product', ['url' => $productIdentifier]);

        // Extract information from URL
        $productData = $this->extractDataFromUrl($productIdentifier);

        if ($productData) {
            $normalized = $this->normalizeProductData($productData);
            $this->logger->info('MockStoreAdapter: Product data generated successfully');

            return $normalized;
        }

        return null;
    }

    #[\Override]
    public function searchProducts(string $query, array $options = []): array
    {
        // Not implemented for mock adapter
        return [];
    }

    public function validateIdentifier(string $identifier): bool
    {
        // Accept any valid URL
        return filter_var($identifier, FILTER_VALIDATE_URL) !== false;
    }

    #[\Override]
    public function getProductUrl(string $identifier): string
    {
        return $identifier;
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 1000,
            'requests_per_hour' => 10000,
            'requests_per_day' => 100000,
        ];
    }

    /**
     * Extract product data from URL using intelligent pattern matching
     *
     * @return array<string, mixed>|null
     */
    private function extractDataFromUrl(string $url): ?array
    {
        $urlLower = strtolower($url);

        // Extract product ID from URL
        $productId = $this->extractProductId($url);

        // Extract product name from URL
        $urlName = $this->extractNameFromUrl($url);

        // Detect brand from URL
        $brand = $this->detectBrand($urlLower);

        // Detect category from URL
        $category = $this->detectCategory($urlLower);

        // Generate realistic price based on category
        $price = $this->generatePrice($category);

        // Generate description
        $description = $this->generateDescription($urlName, $brand, $category, $productId);

        return [
            'name' => $urlName,
            'price' => $price,
            'currency' => 'EGP',
            'url' => $url,
            'image_url' => null, // Will be added manually by admin
            'availability' => 'in_stock',
            'rating' => round(rand(35, 50) / 10, 1), // 3.5 to 5.0
            'reviews_count' => rand(10, 5000),
            'description' => $description,
            'brand' => $brand,
            'category' => $category,
            'metadata' => [
                'product_id' => $productId,
                'source' => $this->detectSource($url),
                'imported_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Extract product ID from URL
     */
    private function extractProductId(string $url): string
    {
        // Try to extract ASIN from Amazon URLs
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
            return $matches[1];
        }

        // Try to extract any product ID pattern
        if (preg_match('/[\/\-]([A-Z0-9]{6,15})(?:[\/\?]|$)/', $url, $matches)) {
            return $matches[1];
        }

        // Fallback: generate from URL hash
        return 'PROD' . strtoupper(substr(md5($url), 0, 8));
    }

    /**
     * Extract product name from URL
     */
    private function extractNameFromUrl(string $url): string
    {
        // Try to extract name from Amazon-style URLs
        if (preg_match('/\/([^\/]+)\/dp\//', $url, $matches)) {
            $urlName = $matches[1];
        }
        // Try to extract from slug-style URLs
        elseif (preg_match('/\/([^\/\?]+?)(?:\?|$)/', $url, $matches)) {
            $urlName = $matches[1];
        } else {
            $urlName = 'Product';
        }

        // Clean up the name
        $urlName = str_replace(['-', '_', '+'], ' ', $urlName);
        $urlName = preg_replace('/\s+/', ' ', $urlName);
        $urlName = ucwords(trim($urlName));

        // Limit length
        if (strlen($urlName) > 100) {
            $urlName = substr($urlName, 0, 97) . '...';
        }

        return $urlName;
    }

    /**
     * Detect brand from URL
     */
    private function detectBrand(string $urlLower): string
    {
        $brands = [
            'apple' => 'Apple',
            'iphone' => 'Apple',
            'ipad' => 'Apple',
            'macbook' => 'Apple',
            'samsung' => 'Samsung',
            'galaxy' => 'Samsung',
            'lg' => 'LG',
            'sony' => 'Sony',
            'dell' => 'Dell',
            'hp' => 'HP',
            'hewlett' => 'HP',
            'lenovo' => 'Lenovo',
            'asus' => 'ASUS',
            'acer' => 'Acer',
            'xiaomi' => 'Xiaomi',
            'redmi' => 'Xiaomi',
            'oppo' => 'OPPO',
            'vivo' => 'Vivo',
            'realme' => 'Realme',
            'nokia' => 'Nokia',
            'huawei' => 'Huawei',
            'microsoft' => 'Microsoft',
            'surface' => 'Microsoft',
            'google' => 'Google',
            'pixel' => 'Google',
            'bosch' => 'Bosch',
            'siemens' => 'Siemens',
            'whirlpool' => 'Whirlpool',
            'tornado' => 'Tornado',
            'fresh' => 'Fresh',
            'kiriazi' => 'Kiriazi',
            'sharp' => 'Sharp',
            'toshiba' => 'Toshiba',
            'panasonic' => 'Panasonic',
            'philips' => 'Philips',
            'canon' => 'Canon',
            'nikon' => 'Nikon',
        ];

        foreach ($brands as $keyword => $brandName) {
            if (stripos($urlLower, $keyword) !== false) {
                return $brandName;
            }
        }

        return 'Generic Brand';
    }

    /**
     * Detect category from URL
     */
    private function detectCategory(string $urlLower): string
    {
        $categories = [
            'Mobile Phones' => ['iphone', 'mobile', 'phone', 'smartphone', 'galaxy', 'pixel'],
            'Laptops' => ['macbook', 'laptop', 'notebook', 'computer', 'desktop'],
            'Tablets' => ['ipad', 'tablet'],
            'Smart Watches' => ['watch', 'smartwatch'],
            'Audio Accessories' => ['airpods', 'headphone', 'earbuds', 'speaker', 'audio'],
            'Home Appliances' => ['refrigerator', 'washing', 'washer', 'dryer', 'dishwasher', 'oven', 'fridge'],
            'Kitchen Appliances' => ['blender', 'mixer', 'toaster', 'coffee', 'microwave', 'kettle'],
            'TVs & Displays' => ['tv', 'television', 'monitor', 'display', 'screen'],
            'Gaming' => ['playstation', 'xbox', 'nintendo', 'gaming', 'console'],
            'Cameras' => ['camera', 'canon', 'nikon', 'photo'],
        ];

        foreach ($categories as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($urlLower, $keyword) !== false) {
                    return $categoryName;
                }
            }
        }

        return 'Electronics';
    }

    /**
     * Generate realistic price based on category
     */
    private function generatePrice(string $category): float
    {
        $priceRanges = [
            'Mobile Phones' => [5000, 40000],
            'Laptops' => [15000, 80000],
            'Tablets' => [8000, 35000],
            'Smart Watches' => [2000, 20000],
            'Audio Accessories' => [500, 8000],
            'Home Appliances' => [5000, 50000],
            'Kitchen Appliances' => [500, 15000],
            'TVs & Displays' => [8000, 60000],
            'Gaming' => [5000, 25000],
            'Cameras' => [10000, 50000],
        ];

        $range = $priceRanges[$category] ?? [1000, 20000];

        return (float) rand($range[0], $range[1]);
    }

    /**
     * Generate product description
     */
    private function generateDescription(string $name, string $brand, string $category, string $productId): string
    {
        $features = [
            'High-quality product with excellent performance',
            'Latest technology and advanced features',
            'Durable construction and premium materials',
            'Energy efficient and eco-friendly design',
            'Easy to use with intuitive controls',
            'Sleek modern design that fits any space',
            'Reliable performance and long-lasting durability',
            'Excellent value for money',
        ];

        shuffle($features);
        $selectedFeatures = array_slice($features, 0, rand(3, 5));

        $description = "<h3>{$name}</h3>\n";
        $description .= "<p><strong>Brand:</strong> {$brand}</p>\n";
        $description .= "<p><strong>Category:</strong> {$category}</p>\n";
        $description .= "<p><strong>Product ID:</strong> {$productId}</p>\n\n";
        $description .= "<h4>Key Features:</h4>\n<ul>\n";

        foreach ($selectedFeatures as $feature) {
            $description .= "<li>{$feature}</li>\n";
        }

        $description .= "</ul>\n\n";
        $description .= "<p><em>Note: This product was imported using the Content Scraper Engine. Images and detailed specifications can be added manually from the product edit page.</em></p>";

        return $description;
    }

    /**
     * Detect source store from URL
     */
    private function detectSource(string $url): string
    {
        if (stripos($url, 'amazon') !== false) {
            return 'Amazon';
        }
        if (stripos($url, 'jumia') !== false) {
            return 'Jumia';
        }
        if (stripos($url, 'noon') !== false) {
            return 'Noon';
        }
        if (stripos($url, 'ebay') !== false) {
            return 'eBay';
        }

        return 'External Store';
    }
}
