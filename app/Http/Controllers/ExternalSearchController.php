<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Scrapers\ScraperManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExternalSearchController extends Controller
{
    public function __construct(
        private readonly ScraperManager $scraperManager
    ) {}

    /**
     * Display external search results page.
     */
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $country = session('locale_country') 
            ?? $request->cookie('locale_country') 
            ?? 'US'; // Default to US if not set
        
        $results = [];
        $error = null;
        $searchTime = null;
        
        if (!empty($query)) {
            $startTime = microtime(true);
            
            try {
                // Perform search using ScraperManager
                $results = $this->scraperManager->search($query, $country);
                
                // Process results: apply affiliate links if available
                $results = $this->applyAffiliateLinks($results);
                
                $searchTime = round(microtime(true) - $startTime, 2);
                
                \Log::info('External search completed', [
                    'query' => $query,
                    'country' => $country,
                    'results_count' => \count($results),
                    'search_time' => $searchTime,
                ]);
            } catch (\Exception $e) {
                $searchTime = round(microtime(true) - $startTime, 2);
                $error = __('An error occurred while searching. Please try again later.');
                
                \Log::error('External search failed', [
                    'query' => $query,
                    'country' => $country,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'search_time' => $searchTime,
                ]);
                
                // Return empty results on error
                $results = [];
            }
        }
        
        return view('search.external', [
            'query' => $query,
            'country' => $country,
            'results' => $results,
            'error' => $error,
            'searchTime' => $searchTime,
        ]);
    }

    /**
     * Apply affiliate links to results if available.
     *
     * @param array<int, array{
     *     name: string,
     *     url: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     store_name: string,
     *     store_logo_url: string
     * }> $results
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
    private function applyAffiliateLinks(array $results): array
    {
        // TODO: Implement affiliate link generation based on store agreements
        // For now, return results as-is
        
        return array_map(function (array $result): array {
            $storeIdentifier = strtolower($result['store_name'] ?? '');
            
            // Check if we have affiliate agreement for this store
            $affiliateTag = $this->getAffiliateTag($storeIdentifier);
            
            if ($affiliateTag) {
                $result['url'] = $this->addAffiliateTag($result['url'], $affiliateTag);
            }
            
            return $result;
        }, $results);
    }

    /**
     * Get affiliate tag for a store.
     */
    private function getAffiliateTag(string $storeIdentifier): ?string
    {
        // Check config for affiliate tags (from scrapers config)
        $affiliateTags = config('scrapers.affiliate_tags', []);
        
        return $affiliateTags[strtolower($storeIdentifier)] ?? null;
    }

    /**
     * Add affiliate tag to URL.
     */
    private function addAffiliateTag(string $url, string $tag): string
    {
        // Parse URL and add affiliate tag
        $parsedUrl = parse_url($url);
        
        if (!$parsedUrl) {
            return $url;
        }
        
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        // Add affiliate tag based on store
        if (str_contains($url, 'amazon.com')) {
            $queryParams['tag'] = $tag;
        } elseif (str_contains($url, 'ebay.com')) {
            $queryParams['mkcid'] = '1';
            $queryParams['mkrid'] = $tag;
        }
        
        $queryString = http_build_query($queryParams);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '/';
        
        return "{$scheme}://{$host}{$path}?" . $queryString;
    }
}

