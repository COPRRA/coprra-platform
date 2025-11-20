<?php

declare(strict_types=1);

namespace App\Services\Scrapers\Adapters;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * eBay scraper adapter using web scraping.
 */
final class EbayScraperAdapter implements ScraperAdapterInterface
{
    private readonly LoggerInterface $logger;
    private readonly string $baseUrl;

    public function __construct(LoggerInterface $logger, string $countryCode = 'US')
    {
        $this->logger = $logger;
        $this->baseUrl = $this->getEbayDomain($countryCode);
    }

    public function getStoreName(): string
    {
        return 'eBay';
    }

    public function getStoreIdentifier(): string
    {
        return 'ebay';
    }

    public function isAvailableForCountry(string $countryCode): bool
    {
        $supportedCountries = ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES'];
        
        return \in_array(strtoupper($countryCode), $supportedCountries, true);
    }

    /**
     * Scrape eBay search results.
     *
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     store_name: string,
     *     store_logo_url: string
     * }>
     */
    public function scrape(string $query): array
    {
        try {
            $searchUrl = $this->buildSearchUrl($query);
            
            $this->logger->info('Scraping eBay', [
                'query' => $query,
                'url' => $searchUrl,
            ]);

            // Fetch HTML content with timeout and retry logic
            $maxRetries = config('scrapers.retry.max_attempts', 3);
            $retryDelay = config('scrapers.retry.delay_seconds', 2);
            $requestTimeout = config('scrapers.timeouts.request', 15);
            $connectionTimeout = config('scrapers.timeouts.connection', 5);
            
            $userAgents = config('scrapers.user_agents', [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ]);
            $userAgent = $userAgents[random_int(0, max(0, count($userAgents) - 1))];
            
            $response = null;
            $lastException = null;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = Http::timeout($requestTimeout)
                        ->connectTimeout($connectionTimeout)
                        ->withHeaders([
                            'User-Agent' => $userAgent,
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.5',
                            'Accept-Encoding' => 'gzip, deflate, br',
                            'Connection' => 'keep-alive',
                            'Upgrade-Insecure-Requests' => '1',
                            'Sec-Fetch-Dest' => 'document',
                            'Sec-Fetch-Mode' => 'navigate',
                            'Sec-Fetch-Site' => 'none',
                        ])
                        ->get($searchUrl);
                    
                    if ($response->successful()) {
                        break; // Success, exit retry loop
                    }
                    
                    if ($attempt < $maxRetries) {
                        $this->logger->warning('eBay request failed, retrying', [
                            'attempt' => $attempt,
                            'status' => $response->status(),
                        ]);
                        sleep($retryDelay);
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    if ($attempt < $maxRetries) {
                        $this->logger->warning('eBay connection failed, retrying', [
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        sleep($retryDelay);
                    }
                } catch (\Exception $e) {
                    $lastException = $e;
                    break; // Don't retry for other exceptions
                }
            }
            
            if (!$response || !$response->successful()) {
                $status = $response ? $response->status() : 'N/A';
                $this->logger->error('eBay request failed after retries', [
                    'status' => $status,
                    'url' => $searchUrl,
                    'error' => $lastException ? $lastException->getMessage() : 'Unknown error',
                ]);
                return [];
            }

            $html = $response->body();
            $crawler = new Crawler($html);
            
            $results = [];
            
            // eBay search results - try multiple selectors
            $selectors = [
                '.s-item',
                'li.s-item',
                '.srp-results .s-item',
            ];
            
            $foundResults = false;
            foreach ($selectors as $selector) {
                try {
                    $crawler->filter($selector)->each(function (Crawler $node) use (&$results, &$foundResults) {
                        try {
                            // Skip items without title (usually template/example)
                            if ($node->filter('.s-item__title')->count() === 0 && 
                                $node->filter('h3.s-item__title')->count() === 0) {
                                return;
                            }

                            $name = $this->extractProductName($node);
                            $url = $this->extractProductUrl($node);
                            $price = $this->extractPrice($node);
                            $availability = $this->extractAvailability($node);
                            
                            if ($name && $url && $price > 0) {
                                $results[] = [
                                    'name' => $name,
                                    'url' => $this->normalizeUrl($url),
                                    'price' => $price,
                                    'currency' => $this->getCurrency(),
                                    'availability' => $availability,
                                    'store_name' => $this->getStoreName(),
                                    'store_logo_url' => asset('images/stores/ebay.png'),
                                ];
                                $foundResults = true;
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning('Failed to extract eBay product', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    });
                    
                    if ($foundResults) {
                        break; // Found results, no need to try other selectors
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('eBay selector failed', [
                        'selector' => $selector,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
            
            // If no results, try fallback
            if (empty($results)) {
                $this->logger->info('No results found with standard selectors, trying fallback');
                $results = $this->fallbackScraping($crawler);
            }

            $this->logger->info('eBay scraping completed', [
                'query' => $query,
                'results_count' => \count($results),
            ]);

            return $results;
        } catch (\Exception $e) {
            $this->logger->error('eBay scraping failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Build eBay search URL.
     */
    private function buildSearchUrl(string $query): string
    {
        $encodedQuery = urlencode($query);
        
        return "https://www.{$this->baseUrl}/sch/i.html?_nkw={$encodedQuery}&_sop=15";
    }

    /**
     * Extract product name from crawler node.
     */
    private function extractProductName(Crawler $node): ?string
    {
        $selectors = [
            '.s-item__title',
            'h3.s-item__title',
            '.s-item__title span',
            'a.s-item__link',
            'h3 a',
        ];

        foreach ($selectors as $selector) {
            try {
                $elements = $node->filter($selector);
                if ($elements->count() > 0) {
                    $name = $elements->first()->text();
                    // eBay sometimes includes prefixes
                    $name = preg_replace('/^(New Listing|Shop on eBay)\s*/i', '', $name);
                    $name = trim($name);
                    
                    if (!empty($name) && 
                        !in_array(strtolower($name), ['shop on ebay', '']) &&
                        strlen($name) > 3) {
                        return $name;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extract product URL from crawler node.
     */
    private function extractProductUrl(Crawler $node): ?string
    {
        $selectors = [
            '.s-item__link',
            'a.s-item__link',
            'h3.s-item__title a',
            'a[href*="/itm/"]',
            'a[href*="ebay.com/itm/"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $link = $node->filter($selector)->first();
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    if ($href && (str_contains($href, '/itm/') || str_contains($href, 'ebay.com'))) {
                        return $href;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Extract price from crawler node.
     */
    private function extractPrice(Crawler $node): float
    {
        $selectors = [
            '.s-item__price',
            '.s-item__detail--primary .s-item__price',
            'span.s-item__price',
            '.s-item__detail--primary span',
            '[class*="price"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $elements = $node->filter($selector);
                if ($elements->count() > 0) {
                    $priceText = $elements->first()->text();
                    $price = $this->parsePrice($priceText);
                    if ($price > 0 && $price < 1000000) { // Sanity check
                        return $price;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return 0.0;
    }

    /**
     * Parse price string to float.
     */
    private function parsePrice(string $priceText): float
    {
        // Remove currency symbols and non-numeric characters except decimal point
        $cleaned = preg_replace('/[^\d.,]/', '', $priceText);
        $cleaned = str_replace(',', '', $cleaned);
        
        // Handle price ranges (e.g., "$10.99 to $15.99" -> take first price)
        if (stripos($cleaned, 'to') !== false) {
            $parts = explode('to', $cleaned);
            $cleaned = trim($parts[0]);
        }
        
        return (float) $cleaned;
    }

    /**
     * Extract availability status.
     */
    private function extractAvailability(Crawler $node): string
    {
        try {
            // Check for "Buy It Now" or auction status
            $buyItNow = $node->filter('.s-item__purchase-options')->count() > 0;
            $sold = $node->filter('.s-item__ended-date')->count() > 0;
            
            if ($sold) {
                return 'Sold';
            }
            
            if ($buyItNow) {
                return 'In Stock';
            }
            
            return 'Available';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Normalize URL to full eBay URL.
     */
    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return "https://www.{$this->baseUrl}{$url}";
        }

        return "https://www.{$this->baseUrl}/{$url}";
    }

    /**
     * Get currency based on country.
     */
    private function getCurrency(): string
    {
        // This could be enhanced to return currency based on baseUrl
        return 'USD';
    }

    /**
     * Get eBay domain based on country code.
     */
    private function getEbayDomain(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'US' => 'ebay.com',
            'GB' => 'ebay.co.uk',
            'CA' => 'ebay.ca',
            'AU' => 'ebay.com.au',
            'DE' => 'ebay.de',
            'FR' => 'ebay.fr',
            'IT' => 'ebay.it',
            'ES' => 'ebay.es',
            default => 'ebay.com',
        };
    }

    /**
     * Fallback scraping method using alternative selectors.
     *
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     store_name: string,
     *     store_logo_url: string
     * }>
     */
    private function fallbackScraping(Crawler $crawler): array
    {
        $results = [];
        
        // Try multiple fallback strategies
        $fallbackSelectors = [
            'li.s-item',
            '.srp-results li',
            'div[data-view]',
        ];
        
        foreach ($fallbackSelectors as $itemSelector) {
            try {
                $crawler->filter($itemSelector)->each(function (Crawler $node) use (&$results) {
                    try {
                        // Try to extract data with multiple methods
                        $name = $this->extractProductName($node);
                        $url = $this->extractProductUrl($node);
                        $price = $this->extractPrice($node);
                        
                        // Only add if we have minimum required data
                        if ($name && $url && $price > 0) {
                            $results[] = [
                                'name' => trim($name),
                                'url' => $this->normalizeUrl($url),
                                'price' => $price,
                                'currency' => $this->getCurrency(),
                                'availability' => $this->extractAvailability($node),
                                'store_name' => $this->getStoreName(),
                                'store_logo_url' => asset('images/stores/ebay.png'),
                            ];
                        }
                    } catch (\Exception $e) {
                        // Skip this item
                        $this->logger->debug('Failed to extract product in fallback', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
                
                // If we found results, no need to try other selectors
                if (!empty($results)) {
                    break;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Fallback selector failed', [
                    'selector' => $itemSelector,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $results;
    }
}

