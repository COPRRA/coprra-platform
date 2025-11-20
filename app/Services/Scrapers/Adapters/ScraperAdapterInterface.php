<?php

declare(strict_types=1);

namespace App\Services\Scrapers\Adapters;

/**
 * Interface for scraper adapters.
 * 
 * Each scraper adapter must implement this interface to provide
 * a unified way to scrape product data from different online stores.
 */
interface ScraperAdapterInterface
{
    /**
     * Scrape products from the store based on search query.
     *
     * @param string $query The search query
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     price: float,
     *     currency: string,
     *     availability: string,
     *     store_name: string,
     *     store_logo_url: string
     * }> Array of standardized product data
     */
    public function scrape(string $query): array;

    /**
     * Get the store name.
     */
    public function getStoreName(): string;

    /**
     * Get the store identifier.
     */
    public function getStoreIdentifier(): string;

    /**
     * Check if the scraper is available for the given country.
     *
     * @param string $countryCode ISO country code (e.g., 'US', 'GB')
     */
    public function isAvailableForCountry(string $countryCode): bool;
}

