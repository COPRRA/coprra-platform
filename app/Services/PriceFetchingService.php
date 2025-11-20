<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching live prices from external stores.
 */
final class PriceFetchingService
{
    public function __construct(
        private readonly AffiliateLinkService $affiliateLinkService
    ) {}

    /**
     * Get live offers for a product from available stores.
     *
     * @param Product $product The product
     * @param string $countryCode ISO country code (e.g., 'US', 'GB')
     * @return array<int, array{
     *     store_identifier: string,
     *     store_name: string,
     *     store_logo: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     affiliate_url: string,
     *     last_updated: string|null
     * }>
     */
    public function getLiveOffers(Product $product, string $countryCode = 'US'): array
    {
        $countryCode = strtoupper($countryCode);
        
        // Get available stores for this country
        $availableStores = $this->getAvailableStoresForCountry($countryCode);
        
        if (empty($availableStores)) {
            Log::warning('No stores available for country', [
                'country' => $countryCode,
                'product_id' => $product->id,
            ]);
            
            return [];
        }

        $offers = [];
        
        foreach ($availableStores as $storeIdentifier) {
            try {
                $offer = $this->fetchOfferFromStore($product, $storeIdentifier, $countryCode);
                
                if ($offer) {
                    $offers[] = $offer;
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch offer from store', [
                    'store' => $storeIdentifier,
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Continue with other stores
                continue;
            }
        }

        return $offers;
    }

    /**
     * Fetch offer from a specific store.
     *
     * @return array{
     *     store_identifier: string,
     *     store_name: string,
     *     store_logo: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     affiliate_url: string,
     *     last_updated: string|null
     * }|null
     */
    private function fetchOfferFromStore(Product $product, string $storeIdentifier, string $countryCode): ?array
    {
        $storeConfig = config("affiliates.stores.{$storeIdentifier}");
        
        if (!$storeConfig) {
            return null;
        }

        // For now, simulate price fetching with realistic mock data
        // In production, this would call actual APIs or scraping services
        $mockPrice = $this->generateMockPrice($product, $storeIdentifier);
        $mockAvailability = $this->generateMockAvailability();
        
        // Generate affiliate link
        $affiliateUrl = $this->affiliateLinkService->generate($product, $storeIdentifier);
        
        // Get currency based on country
        $currency = $this->getCurrencyForCountry($countryCode);

        return [
            'store_identifier' => $storeIdentifier,
            'store_name' => $storeConfig['name'],
            'store_logo' => $storeConfig['logo'],
            'price' => $mockPrice,
            'currency' => $currency,
            'availability' => $mockAvailability,
            'affiliate_url' => $affiliateUrl,
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate mock price based on product's official price.
     */
    private function generateMockPrice(Product $product, string $storeIdentifier): float
    {
        $officialPrice = (float) ($product->price ?? 0);
        
        if ($officialPrice <= 0) {
            // If no official price, generate a random reasonable price
            return round(random_int(50, 2000) + (random_int(0, 99) / 100), 2);
        }

        // Generate price within +/- 10% of official price
        $variation = $officialPrice * 0.10; // 10%
        $minPrice = $officialPrice - $variation;
        $maxPrice = $officialPrice + $variation;
        
        // Add some randomness based on store (different stores might have different prices)
        $storeMultiplier = match ($storeIdentifier) {
            'amazon' => 1.0, // Amazon usually competitive
            'ebay' => 0.95, // eBay might be slightly cheaper
            'noon' => 1.05, // Noon might be slightly more expensive
            'jumia' => 0.98,
            default => 1.0,
        };
        
        $basePrice = random_int((int) ($minPrice * 100), (int) ($maxPrice * 100)) / 100;
        
        return round($basePrice * $storeMultiplier, 2);
    }

    /**
     * Generate mock availability status.
     */
    private function generateMockAvailability(): string
    {
        $statuses = ['In Stock', 'In Stock', 'In Stock', 'Low Stock', 'Out of Stock'];
        
        return $statuses[random_int(0, count($statuses) - 1)];
    }

    /**
     * Get available stores for a country.
     *
     * @return array<int, string>
     */
    private function getAvailableStoresForCountry(string $countryCode): array
    {
        $countryStores = config('affiliates.country_stores', []);
        
        return $countryStores[$countryCode] ?? [];
    }

    /**
     * Get currency code for country.
     */
    private function getCurrencyForCountry(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'US' => 'USD',
            'GB' => 'GBP',
            'CA' => 'CAD',
            'AU' => 'AUD',
            'DE', 'FR', 'IT', 'ES' => 'EUR',
            'JP' => 'JPY',
            'IN' => 'INR',
            'AE' => 'AED',
            'SA' => 'SAR',
            'EG' => 'EGP',
            'NG' => 'NGN',
            'KE' => 'KES',
            default => 'USD',
        };
    }
}

