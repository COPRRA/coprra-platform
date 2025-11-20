<?php

declare(strict_types=1);

namespace App\Services\Scrapers\Adapters;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Amazon scraper adapter using web scraping.
 * 
 * Note: This is a basic implementation. For production use,
 * consider using Amazon Product Advertising API with proper credentials.
 */
final class AmazonScraperAdapter implements ScraperAdapterInterface
{
    private readonly LoggerInterface $logger;
    private readonly string $baseUrl;

    public function __construct(LoggerInterface $logger, string $countryCode = 'US')
    {
        $this->logger = $logger;
        $this->baseUrl = $this->getAmazonDomain($countryCode);
    }

    public function getStoreName(): string
    {
        return 'Amazon';
    }

    public function getStoreIdentifier(): string
    {
        return 'amazon';
    }

    public function isAvailableForCountry(string $countryCode): bool
    {
        $supportedCountries = ['US', 'GB', 'CA', 'DE', 'FR', 'IT', 'ES', 'JP', 'AU', 'IN'];
        
        return \in_array(strtoupper($countryCode), $supportedCountries, true);
    }

    /**
     * Scrape Amazon search results.
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
            
            $this->logger->info('Scraping Amazon', [
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
                        $this->logger->warning('Amazon request failed, retrying', [
                            'attempt' => $attempt,
                            'status' => $response->status(),
                        ]);
                        sleep($retryDelay);
                    }
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    $lastException = $e;
                    if ($attempt < $maxRetries) {
                        $this->logger->warning('Amazon connection failed, retrying', [
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
                $this->logger->error('Amazon request failed after retries', [
                    'status' => $status,
                    'url' => $searchUrl,
                    'error' => $lastException ? $lastException->getMessage() : 'Unknown error',
                ]);
                return [];
            }


            $html = $response->body();
            $crawler = new Crawler($html);
            
            $results = [];
            
            // Amazon search results are typically in div[data-component-type="s-search-result"]
            // Try multiple selectors to handle different Amazon layouts
            $selectors = [
                '[data-component-type="s-search-result"]',
                '.s-result-item',
                'div[data-asin]:not([data-asin=""])',
            ];
            
            $foundResults = false;
            foreach ($selectors as $selector) {
                try {
                    $crawler->filter($selector)->each(function (Crawler $node) use (&$results, &$foundResults) {
                        try {
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
                                    'store_logo_url' => asset('images/stores/amazon.png'),
                                ];
                                $foundResults = true;
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning('Failed to extract Amazon product', [
                                'error' => $e->getMessage(),
                                'selector' => $selector,
                            ]);
                        }
                    });
                    
                    if ($foundResults) {
                        break; // Found results with this selector, no need to try others
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Selector failed', [
                        'selector' => $selector,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // If no results found with the standard selectors, try fallback scraping
            if (empty($results)) {
                $this->logger->info('No results found with standard selectors, trying fallback');
                $results = $this->fallbackScraping($crawler);
            }

            $this->logger->info('Amazon scraping completed', [
                'query' => $query,
                'results_count' => \count($results),
            ]);

            return $results;
        } catch (\Exception $e) {
            $this->logger->error('Amazon scraping failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Build Amazon search URL.
     */
    private function buildSearchUrl(string $query): string
    {
        $encodedQuery = urlencode($query);
        
        return "https://www.{$this->baseUrl}/s?k={$encodedQuery}";
    }

    /**
     * Extract product name from crawler node.
     */
    private function extractProductName(Crawler $node): ?string
    {
        // Try multiple selectors for product name (ordered by reliability)
        $selectors = [
            'h2 a span.a-text-normal',
            'h2 a span',
            'h2 a',
            '.s-title-instructions-style h2 a span',
            '[data-cy="title-recipe-title"]',
            'h2 span',
            '.s-link-style a span',
            'a.a-link-normal span',
        ];

        foreach ($selectors as $selector) {
            try {
                $elements = $node->filter($selector);
                if ($elements->count() > 0) {
                    $name = $elements->first()->text();
                    $name = trim($name);
                    // Filter out common non-product text
                    if (!empty($name) && 
                        !in_array(strtolower($name), ['sponsored', 'ad', 'best seller', 'new', '']) &&
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
        // Try multiple selectors for product URL
        $selectors = [
            'h2 a',
            'a.a-link-normal',
            '.s-link-style a',
            'a[href*="/dp/"]',
            'a[href*="/gp/product/"]',
        ];

        foreach ($selectors as $selector) {
            try {
                $link = $node->filter($selector)->first();
                if ($link->count() > 0) {
                    $href = $link->attr('href');
                    if ($href && (str_contains($href, '/dp/') || str_contains($href, '/gp/product/'))) {
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
            '.a-price .a-offscreen',
            '.a-price-whole',
            '.a-price[data-a-color="base"] .a-offscreen',
            'span.a-price-whole',
            '.a-price span.a-offscreen',
            '[data-a-color="price"] .a-offscreen',
            '.a-price-range .a-offscreen',
        ];

        foreach ($selectors as $selector) {
            try {
                $elements = $node->filter($selector);
                if ($elements->count() > 0) {
                    $priceText = $elements->first()->text();
                    $price = $this->parsePrice($priceText);
                    if ($price > 0 && $price < 1000000) { // Sanity check: reasonable price range
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
        
        return (float) $cleaned;
    }

    /**
     * Extract availability status.
     */
    private function extractAvailability(Crawler $node): string
    {
        try {
            // Check for "In Stock" indicators
            $stockSelectors = [
                '.a-color-success',
                '[aria-label*="stock"]',
                '.a-text-bold:contains("In Stock")',
            ];

            foreach ($stockSelectors as $selector) {
                try {
                    $stockText = $node->filter($selector)->first()->text();
                    if (stripos($stockText, 'stock') !== false || stripos($stockText, 'available') !== false) {
                        return 'In Stock';
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Default to "In Stock" for Amazon (most items are available)
            return 'In Stock';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Normalize URL to full Amazon URL.
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
     * Get Amazon domain based on country code.
     */
    private function getAmazonDomain(string $countryCode): string
    {
        return match (strtoupper($countryCode)) {
            'US' => 'amazon.com',
            'GB' => 'amazon.co.uk',
            'CA' => 'amazon.ca',
            'DE' => 'amazon.de',
            'FR' => 'amazon.fr',
            'IT' => 'amazon.it',
            'ES' => 'amazon.es',
            'JP' => 'amazon.co.jp',
            'AU' => 'amazon.com.au',
            'IN' => 'amazon.in',
            default => 'amazon.com',
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
            '.s-result-item',
            'div[data-asin]:not([data-asin=""])',
            '.s-card-container',
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
                                'store_logo_url' => asset('images/stores/amazon.png'),
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

