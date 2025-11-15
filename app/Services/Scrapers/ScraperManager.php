<?php

declare(strict_types=1);

namespace App\Services\Scrapers;

use App\Services\Scrapers\Adapters\AmazonScraperAdapter;
use App\Services\Scrapers\Adapters\EbayScraperAdapter;
use App\Services\Scrapers\Adapters\ScraperAdapterInterface;
use App\Services\Scrapers\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Manager for scraper adapters.
 * 
 * Handles scraping from multiple stores based on country and query.
 */
final class ScraperManager
{
    /**
     * @var array<string, ScraperAdapterInterface>
     */
    private array $adapters = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheRepository $cache,
        private readonly RateLimiter $rateLimiter
    ) {
        $this->initializeAdapters();
    }

    /**
     * Search for products across multiple stores.
     *
     * @param string $query Search query
     * @param string $countryCode ISO country code (e.g., 'US', 'GB')
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     store_name: string,
     *     store_logo_url: string
     * }> Combined and sorted results from all adapters
     */
    public function search(string $query, string $countryCode = 'US'): array
    {
        // Check cache first
        $cacheKey = $this->getCacheKey($query, $countryCode);
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null && \is_array($cached)) {
            $this->logger->info('Returning cached search results', [
                'query' => $query,
                'country' => $countryCode,
                'results_count' => \count($cached),
            ]);
            
            return $cached;
        }

        $allResults = [];
        
        // Get adapters available for this country (created dynamically)
        $availableAdapters = $this->getAdaptersForCountry($countryCode);
        
        if (empty($availableAdapters)) {
            $this->logger->warning('No scrapers available for country', [
                'country' => $countryCode,
            ]);
            
            return [];
        }

        // Scrape from each adapter with timeout protection and rate limiting
        foreach ($availableAdapters as $index => $adapter) {
            $storeIdentifier = $adapter->getStoreIdentifier();
            
            // Add delay between requests to avoid being blocked (except for first request)
            if ($index > 0) {
                $delaySeconds = config('scrapers.delay_between_stores', 2);
                sleep($delaySeconds);
            }
            
            // Check rate limit before scraping
            if (!$this->rateLimiter->isAllowed($storeIdentifier)) {
                $remaining = $this->rateLimiter->getRemainingRequests($storeIdentifier);
                $this->logger->warning('Rate limit exceeded for adapter', [
                    'adapter' => $storeIdentifier,
                    'query' => $query,
                    'remaining' => $remaining,
                ]);
                
                // Skip this adapter if rate limit exceeded
                continue;
            }
            
            try {
                $this->logger->info('Scraping from adapter', [
                    'adapter' => $storeIdentifier,
                    'query' => $query,
                ]);

                // Record request before scraping
                $this->rateLimiter->recordRequest($storeIdentifier);

                // Set execution time limit for scraping (max 30 seconds per adapter)
                $startTime = microtime(true);
                $maxExecutionTime = 30;
                
                $results = $adapter->scrape($query);
                
                $executionTime = microtime(true) - $startTime;
                
                $this->logger->info('Adapter scraping completed', [
                    'adapter' => $adapter->getStoreIdentifier(),
                    'results_count' => \count($results),
                    'execution_time' => round($executionTime, 2) . 's',
                ]);

                $allResults = array_merge($allResults, $results);
                
                // If execution took too long, log warning
                if ($executionTime > $maxExecutionTime) {
                    $this->logger->warning('Adapter scraping took longer than expected', [
                        'adapter' => $adapter->getStoreIdentifier(),
                        'execution_time' => round($executionTime, 2) . 's',
                    ]);
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Network/timeout errors - log but continue
                $this->logger->warning('Scraper adapter connection failed', [
                    'adapter' => $adapter->getStoreIdentifier(),
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
                
                continue;
            } catch (\Exception $e) {
                // Log error but continue with other adapters
                $this->logger->error('Scraper adapter failed', [
                    'adapter' => $adapter->getStoreIdentifier(),
                    'query' => $query,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue with next adapter
                continue;
            }
        }

        // Sort results by price (cheapest first)
        usort($allResults, static function (array $a, array $b): int {
            $priceA = $a['price'] ?? 0.0;
            $priceB = $b['price'] ?? 0.0;
            
            return $priceA <=> $priceB;
        });

        // Cache results (TTL from config)
        $cacheTtl = config('scrapers.cache.ttl', 3600);
        $this->cache->put($cacheKey, $allResults, $cacheTtl);

        return $allResults;
    }

    /**
     * Get adapters available for the given country.
     * Creates adapters dynamically based on country code.
     *
     * @return array<int, ScraperAdapterInterface>
     */
    private function getAdaptersForCountry(string $countryCode): array
    {
        $adapters = [];
        
        // Create Amazon adapter for the country
        $amazonAdapter = new AmazonScraperAdapter($this->logger, $countryCode);
        if ($amazonAdapter->isAvailableForCountry($countryCode)) {
            $adapters[] = $amazonAdapter;
        }
        
        // Create eBay adapter for the country
        $ebayAdapter = new EbayScraperAdapter($this->logger, $countryCode);
        if ($ebayAdapter->isAvailableForCountry($countryCode)) {
            $adapters[] = $ebayAdapter;
        }
        
        return $adapters;
    }

    /**
     * Initialize scraper adapters.
     * 
     * Note: Adapters are created dynamically per-country in getAdaptersForCountry()
     * to support different domains based on country code.
     */
    private function initializeAdapters(): void
    {
        // Adapters will be created dynamically based on country
        // This method is kept for future use if we need to pre-register adapters
    }

    /**
     * Get cache key for search query and country.
     */
    private function getCacheKey(string $query, string $countryCode): string
    {
        return "external_search:" . md5(strtolower($query) . ":" . strtoupper($countryCode));
    }

    /**
     * Clear cache for a specific query and country.
     */
    public function clearCache(string $query, string $countryCode): void
    {
        $cacheKey = $this->getCacheKey($query, $countryCode);
        $this->cache->forget($cacheKey);
    }

    /**
     * Get all available adapters.
     *
     * @return array<int, ScraperAdapterInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }
}

