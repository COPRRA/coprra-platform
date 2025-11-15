<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating affiliate links for products.
 */
final class AffiliateLinkService
{
    /**
     * Generate affiliate link for a product on a specific store.
     *
     * @param Product $product The product
     * @param string $storeIdentifier Store identifier (e.g., 'amazon', 'ebay')
     * @param string|null $productUrl Optional product URL on the store
     * @return string The affiliate link
     */
    public function generate(Product $product, string $storeIdentifier, ?string $productUrl = null): string
    {
        $storeConfig = config("affiliates.stores.{$storeIdentifier}");
        
        if (!$storeConfig) {
            Log::warning('Store config not found', [
                'store' => $storeIdentifier,
                'product_id' => $product->id,
            ]);
            
            // Fallback: return a search URL
            return $this->generateSearchUrl($product, $storeIdentifier);
        }

        $baseUrl = $storeConfig['base_url'];
        $affiliateTag = $storeConfig['affiliate_tag'];
        $tagParameter = $storeConfig['tag_parameter'] ?? 'tag';

        // If product URL is provided, use it; otherwise generate search URL
        if ($productUrl) {
            return $this->addAffiliateTagToUrl($productUrl, $affiliateTag, $tagParameter);
        }

        // Generate search URL based on product name
        return $this->generateSearchUrl($product, $storeIdentifier, $affiliateTag, $tagParameter);
    }

    /**
     * Add affiliate tag to an existing URL.
     */
    private function addAffiliateTagToUrl(string $url, string $tag, string $tagParameter): string
    {
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl) {
            return $url;
        }

        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        // Add or replace affiliate tag
        $queryParams[$tagParameter] = $tag;

        $newQuery = http_build_query($queryParams);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '/';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return "{$scheme}://{$host}{$path}?{$newQuery}{$fragment}";
    }

    /**
     * Generate search URL for a product.
     */
    private function generateSearchUrl(
        Product $product,
        string $storeIdentifier,
        ?string $affiliateTag = null,
        string $tagParameter = 'tag'
    ): string {
        $storeConfig = config("affiliates.stores.{$storeIdentifier}");
        
        if (!$storeConfig) {
            return '#';
        }

        $baseUrl = $storeConfig['base_url'];
        $tag = $affiliateTag ?? $storeConfig['affiliate_tag'];
        
        // Generate search query from product name
        $searchQuery = urlencode($product->name);
        
        // Different stores have different search URL formats
        return match ($storeIdentifier) {
            'amazon' => "{$baseUrl}/s?k={$searchQuery}&{$tagParameter}={$tag}",
            'ebay' => "{$baseUrl}/sch/i.html?_nkw={$searchQuery}&{$tagParameter}={$tag}",
            'noon' => "{$baseUrl}/catalog/?q={$searchQuery}&{$tagParameter}={$tag}",
            'jumia' => "{$baseUrl}/catalog/?q={$searchQuery}&{$tagParameter}={$tag}",
            default => "{$baseUrl}/search?q={$searchQuery}&{$tagParameter}={$tag}",
        };
    }

    /**
     * Get store configuration.
     */
    public function getStoreConfig(string $storeIdentifier): ?array
    {
        return config("affiliates.stores.{$storeIdentifier}");
    }

    /**
     * Check if store is available for country.
     */
    public function isStoreAvailableForCountry(string $storeIdentifier, string $countryCode): bool
    {
        $storeConfig = config("affiliates.stores.{$storeIdentifier}");
        
        if (!$storeConfig) {
            return false;
        }

        $availableCountries = $storeConfig['available_countries'] ?? [];
        
        return in_array(strtoupper($countryCode), $availableCountries, true);
    }
}

