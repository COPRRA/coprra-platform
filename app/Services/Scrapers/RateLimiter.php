<?php

declare(strict_types=1);

namespace App\Services\Scrapers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Rate limiter for scraper adapters.
 * 
 * Prevents excessive requests to external sites to avoid getting blocked.
 */
final class RateLimiter
{
    private const CACHE_PREFIX = 'scraper_rate_limit:';
    /**
     * Get default rate limits from config.
     */
    private function getDefaultLimits(): array
    {
        return config('scrapers.rate_limits', [
            'amazon' => [
                'requests_per_minute' => 5,
                'requests_per_hour' => 30,
                'requests_per_day' => 200,
            ],
            'ebay' => [
                'requests_per_minute' => 10,
                'requests_per_hour' => 50,
                'requests_per_day' => 300,
            ],
        ]);
    }

    public function __construct(
        private readonly CacheRepository $cache
    ) {}

    /**
     * Check if a request is allowed for the given store.
     *
     * @param string $storeIdentifier Store identifier (e.g., 'amazon', 'ebay')
     * @return bool True if request is allowed, false otherwise
     */
    public function isAllowed(string $storeIdentifier): bool
    {
        $defaultLimits = $this->getDefaultLimits();
        $limits = $defaultLimits[strtolower($storeIdentifier)] ?? $defaultLimits['amazon'];
        
        // Check per-minute limit
        $minuteKey = self::CACHE_PREFIX . $storeIdentifier . ':minute:' . date('Y-m-d-H-i');
        $minuteCount = (int) $this->cache->get($minuteKey, 0);
        
        if ($minuteCount >= $limits['requests_per_minute']) {
            return false;
        }
        
        // Check per-hour limit
        $hourKey = self::CACHE_PREFIX . $storeIdentifier . ':hour:' . date('Y-m-d-H');
        $hourCount = (int) $this->cache->get($hourKey, 0);
        
        if ($hourCount >= $limits['requests_per_hour']) {
            return false;
        }
        
        // Check per-day limit
        $dayKey = self::CACHE_PREFIX . $storeIdentifier . ':day:' . date('Y-m-d');
        $dayCount = (int) $this->cache->get($dayKey, 0);
        
        if ($dayCount >= $limits['requests_per_day']) {
            return false;
        }
        
        return true;
    }

    /**
     * Record a request for the given store.
     *
     * @param string $storeIdentifier Store identifier
     */
    public function recordRequest(string $storeIdentifier): void
    {
        $minuteKey = self::CACHE_PREFIX . $storeIdentifier . ':minute:' . date('Y-m-d-H-i');
        $hourKey = self::CACHE_PREFIX . $storeIdentifier . ':hour:' . date('Y-m-d-H');
        $dayKey = self::CACHE_PREFIX . $storeIdentifier . ':day:' . date('Y-m-d');
        
        // Increment counters with appropriate TTL
        $this->cache->increment($minuteKey, 1);
        $this->cache->put($minuteKey, $this->cache->get($minuteKey, 0), 60); // 1 minute TTL
        
        $this->cache->increment($hourKey, 1);
        $this->cache->put($hourKey, $this->cache->get($hourKey, 0), 3600); // 1 hour TTL
        
        $this->cache->increment($dayKey, 1);
        $this->cache->put($dayKey, $this->cache->get($dayKey, 0), 86400); // 24 hours TTL
    }

    /**
     * Get remaining requests for the given store.
     *
     * @param string $storeIdentifier Store identifier
     * @return array{minute: int, hour: int, day: int} Remaining requests
     */
    public function getRemainingRequests(string $storeIdentifier): array
    {
        $defaultLimits = $this->getDefaultLimits();
        $limits = $defaultLimits[strtolower($storeIdentifier)] ?? $defaultLimits['amazon'];
        
        $minuteKey = self::CACHE_PREFIX . $storeIdentifier . ':minute:' . date('Y-m-d-H-i');
        $hourKey = self::CACHE_PREFIX . $storeIdentifier . ':hour:' . date('Y-m-d-H');
        $dayKey = self::CACHE_PREFIX . $storeIdentifier . ':day:' . date('Y-m-d');
        
        $minuteCount = (int) $this->cache->get($minuteKey, 0);
        $hourCount = (int) $this->cache->get($hourKey, 0);
        $dayCount = (int) $this->cache->get($dayKey, 0);
        
        return [
            'minute' => max(0, $limits['requests_per_minute'] - $minuteCount),
            'hour' => max(0, $limits['requests_per_hour'] - $hourCount),
            'day' => max(0, $limits['requests_per_day'] - $dayCount),
        ];
    }

    /**
     * Reset rate limit counters for a store (useful for testing or manual reset).
     *
     * @param string $storeIdentifier Store identifier
     */
    public function reset(string $storeIdentifier): void
    {
        $patterns = [
            self::CACHE_PREFIX . $storeIdentifier . ':minute:*',
            self::CACHE_PREFIX . $storeIdentifier . ':hour:*',
            self::CACHE_PREFIX . $storeIdentifier . ':day:*',
        ];
        
        foreach ($patterns as $pattern) {
            // Note: Laravel cache doesn't support wildcard deletion directly
            // This would need to be implemented based on cache driver
            // For now, we'll clear specific keys
            $this->cache->forget(self::CACHE_PREFIX . $storeIdentifier . ':minute:' . date('Y-m-d-H-i'));
            $this->cache->forget(self::CACHE_PREFIX . $storeIdentifier . ':hour:' . date('Y-m-d-H'));
            $this->cache->forget(self::CACHE_PREFIX . $storeIdentifier . ':day:' . date('Y-m-d'));
        }
    }
}

