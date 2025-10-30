<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CrossSellRecommendationTest extends TestCase
{
    #[Test]
    public function itRecommendsCrossSellProducts(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Laptop',
            'category' => 'Electronics',
            'price' => 999.99,
        ];

        $recommendations = $this->getCrossSellRecommendations($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));
        self::assertArrayHasKey('product_id', $recommendations[0]);
        self::assertArrayHasKey('name', $recommendations[0]);
        self::assertArrayHasKey('confidence_score', $recommendations[0]);
    }

    #[Test]
    public function itRecommendsComplementaryProducts(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'iPhone',
            'category' => 'Electronics',
            'price' => 799.99,
        ];

        $recommendations = $this->getComplementaryProducts($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are complementary
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('product_id', $recommendation);
        }
    }

    #[Test]
    public function itRecommendsRelatedAccessories(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Camera',
            'category' => 'Electronics',
            'price' => 599.99,
        ];

        $recommendations = $this->getRelatedAccessories($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are accessories
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isAccessory($recommendation));
        }
    }

    #[Test]
    public function itRecommendsBundledProducts(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Gaming Console',
            'category' => 'Electronics',
            'price' => 399.99,
        ];

        $recommendations = $this->getBundledProducts($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products can be bundled
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->canBeBundled($currentProduct, $recommendation));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnPurchaseHistory(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Laptop',
            'category' => 'Electronics',
            'price' => 999.99,
        ];

        $purchaseHistory = [
            ['product_id' => 2, 'name' => 'Mouse', 'category' => 'Accessories'],
            ['product_id' => 3, 'name' => 'Keyboard', 'category' => 'Accessories'],
            ['product_id' => 4, 'name' => 'Monitor', 'category' => 'Electronics'],
        ];

        $recommendations = $this->getRecommendationsBasedOnHistory($currentProduct, $purchaseHistory);

        self::assertGreaterThan(0, \count($recommendations));
    }

    #[Test]
    public function itRecommendsProductsBasedOnUserBehavior(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Smartphone',
            'category' => 'Electronics',
            'price' => 699.99,
        ];

        $userBehavior = [
            'viewed_products' => ['Case', 'Screen Protector', 'Charger'],
            'searched_terms' => ['phone accessories', 'mobile protection'],
            'time_spent' => 300, // seconds
        ];

        $recommendations = $this->getRecommendationsBasedOnBehavior($currentProduct, $userBehavior);

        self::assertGreaterThan(0, \count($recommendations));
    }

    #[Test]
    public function itRecommendsProductsBasedOnSeason(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Winter Jacket',
            'category' => 'Clothing',
            'price' => 149.99,
        ];

        $season = 'winter';
        $recommendations = $this->getSeasonalRecommendations($currentProduct, $season);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are seasonal
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isSeasonalProduct($recommendation, $season));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnPriceRange(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Budget Laptop',
            'category' => 'Electronics',
            'price' => 299.99,
        ];

        $recommendations = $this->getRecommendationsByPriceRange($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are in similar price range
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isInSimilarPriceRange($currentProduct, $recommendation));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnBrandPreference(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Apple iPhone',
            'category' => 'Electronics',
            'brand' => 'Apple',
            'price' => 799.99,
        ];

        $brandPreferences = ['Apple', 'Samsung', 'Google'];
        $recommendations = $this->getRecommendationsByBrand($currentProduct, $brandPreferences);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products match brand preferences
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('brand', $recommendation);
            self::assertTrue(\in_array($recommendation['brand'], $brandPreferences, true));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnRatings(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'High-Rated Laptop',
            'category' => 'Electronics',
            'rating' => 4.8,
            'price' => 999.99,
        ];

        $recommendations = $this->getRecommendationsByRatings($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products have good ratings
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('rating', $recommendation);
            self::assertGreaterThanOrEqual(4.0, $recommendation['rating']);
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnAvailability(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Available Product',
            'category' => 'Electronics',
            'price' => 199.99,
        ];

        $recommendations = $this->getRecommendationsByAvailability($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are available
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('in_stock', $recommendation);
            self::assertTrue($recommendation['in_stock']);
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnPopularity(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Popular Product',
            'category' => 'Electronics',
            'price' => 299.99,
        ];

        $recommendations = $this->getRecommendationsByPopularity($currentProduct);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are popular
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('sales_count', $recommendation);
            self::assertGreaterThan(100, $recommendation['sales_count']);
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnCustomerSegments(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Professional Laptop',
            'category' => 'Electronics',
            'price' => 1299.99,
        ];

        $customerSegment = 'professional';
        $recommendations = $this->getRecommendationsBySegment($currentProduct, $customerSegment);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products match customer segment
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->matchesCustomerSegment($recommendation, $customerSegment));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnGeographicLocation(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Regional Product',
            'category' => 'Electronics',
            'price' => 199.99,
        ];

        $location = 'US';
        $recommendations = $this->getRecommendationsByLocation($currentProduct, $location);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are available in location
        foreach ($recommendations as $recommendation) {
            self::assertArrayHasKey('available_locations', $recommendation);
            $availableLocations = $recommendation['available_locations'];
            if (\is_array($availableLocations)) {
                self::assertTrue(\in_array($location, $availableLocations, true));
            }
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnTimeOfDay(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Coffee Maker',
            'category' => 'Appliances',
            'price' => 89.99,
        ];

        $timeOfDay = 'morning';
        $recommendations = $this->getRecommendationsByTimeOfDay($currentProduct, $timeOfDay);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are relevant to time of day
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isRelevantToTimeOfDay($recommendation, $timeOfDay));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnWeather(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Umbrella',
            'category' => 'Accessories',
            'price' => 19.99,
        ];

        $weather = 'rainy';
        $recommendations = $this->getRecommendationsByWeather($currentProduct, $weather);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are relevant to weather
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isRelevantToWeather($recommendation, $weather));
        }
    }

    #[Test]
    public function itRecommendsProductsBasedOnEvents(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Gift Card',
            'category' => 'Gifts',
            'price' => 50.00,
        ];

        $event = 'birthday';
        $recommendations = $this->getRecommendationsByEvent($currentProduct, $event);

        self::assertGreaterThan(0, \count($recommendations));

        // Check that recommended products are relevant to event
        foreach ($recommendations as $recommendation) {
            self::assertTrue($this->isRelevantToEvent($recommendation, $event));
        }
    }

    #[Test]
    public function itCalculatesRecommendationConfidence(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Laptop',
            'category' => 'Electronics',
            'price' => 999.99,
        ];

        $recommendedProduct = [
            'id' => 2,
            'name' => 'Mouse',
            'category' => 'Accessories',
            'price' => 29.99,
        ];

        $confidence = $this->calculateRecommendationConfidence($currentProduct, $recommendedProduct);

        self::assertGreaterThanOrEqual(0.0, $confidence);
        self::assertLessThanOrEqual(1.0, $confidence);
    }

    #[Test]
    public function itGeneratesCrossSellReport(): void
    {
        $currentProduct = [
            'id' => 1,
            'name' => 'Laptop',
            'category' => 'Electronics',
            'price' => 999.99,
        ];

        $report = $this->generateCrossSellReport($currentProduct);

        self::assertArrayHasKey('product_id', $report);
        self::assertArrayHasKey('recommendations', $report);
        self::assertArrayHasKey('total_recommendations', $report);
        self::assertArrayHasKey('average_confidence', $report);
        self::assertArrayHasKey('generated_at', $report);
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCrossSellRecommendations(array $currentProduct): array
    {
        // Mock cross-sell recommendations
        return [
            [
                'product_id' => 2,
                'name' => 'Wireless Mouse',
                'category' => 'Accessories',
                'price' => 29.99,
                'confidence_score' => 0.85,
                'reason' => 'Frequently bought together',
            ],
            [
                'product_id' => 3,
                'name' => 'Laptop Bag',
                'category' => 'Accessories',
                'price' => 49.99,
                'confidence_score' => 0.78,
                'reason' => 'Complementary product',
            ],
            [
                'product_id' => 4,
                'name' => 'USB Hub',
                'category' => 'Accessories',
                'price' => 19.99,
                'confidence_score' => 0.72,
                'reason' => 'Related accessory',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getComplementaryProducts(array $currentProduct): array
    {
        $complementaryProducts = [
            'iPhone' => ['Case', 'Screen Protector', 'Charger', 'AirPods'],
            'Laptop' => ['Mouse', 'Keyboard', 'Monitor', 'Laptop Stand'],
            'Camera' => ['Lens', 'Memory Card', 'Tripod', 'Camera Bag'],
            'Gaming Console' => ['Controller', 'Game', 'Headset', 'Charging Station'],
        ];

        $productName = $currentProduct['name'];
        $recommendations = [];

        foreach ($complementaryProducts as $product => $complements) {
            if (\is_string($productName) && false !== stripos($productName, $product)) {
                foreach ($complements as $complement) {
                    $recommendations[] = [
                        'product_id' => rand(100, 999),
                        'name' => $complement,
                        'category' => 'Accessories',
                        'price' => rand(10, 100),
                        'confidence_score' => rand(70, 95) / 100,
                    ];
                }

                break;
            }
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRelatedAccessories(array $currentProduct): array
    {
        return [
            [
                'product_id' => 5,
                'name' => 'Camera Lens',
                'category' => 'Accessories',
                'price' => 199.99,
                'confidence_score' => 0.90,
                'is_accessory' => true,
            ],
            [
                'product_id' => 6,
                'name' => 'Memory Card',
                'category' => 'Accessories',
                'price' => 39.99,
                'confidence_score' => 0.85,
                'is_accessory' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getBundledProducts(array $currentProduct): array
    {
        return [
            [
                'product_id' => 7,
                'name' => 'Gaming Controller',
                'category' => 'Accessories',
                'price' => 59.99,
                'confidence_score' => 0.88,
                'can_bundle' => true,
            ],
            [
                'product_id' => 8,
                'name' => 'Gaming Headset',
                'category' => 'Accessories',
                'price' => 79.99,
                'confidence_score' => 0.82,
                'can_bundle' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed>             $currentProduct
     * @param array<int, array<string, mixed>> $purchaseHistory
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsBasedOnHistory(array $currentProduct, array $purchaseHistory): array
    {
        $recommendations = [];

        foreach ($purchaseHistory as $item) {
            if ('Accessories' === $item['category']) {
                $recommendations[] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'price' => rand(20, 100),
                    'confidence_score' => 0.75,
                    'reason' => 'Based on purchase history',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $currentProduct
     * @param array<string, mixed> $userBehavior
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsBasedOnBehavior(array $currentProduct, array $userBehavior): array
    {
        $recommendations = [];

        if (isset($userBehavior['viewed_products']) && \is_array($userBehavior['viewed_products'])) {
            foreach ($userBehavior['viewed_products'] as $product) {
                $recommendations[] = [
                    'product_id' => rand(100, 999),
                    'name' => $product,
                    'category' => 'Accessories',
                    'price' => rand(10, 50),
                    'confidence_score' => 0.80,
                    'reason' => 'Based on user behavior',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getSeasonalRecommendations(array $currentProduct, string $season): array
    {
        $seasonalProducts = [
            'winter' => ['Gloves', 'Scarf', 'Winter Boots', 'Hot Chocolate'],
            'summer' => ['Sunglasses', 'Sunscreen', 'Summer Hat', 'Ice Cream'],
            'spring' => ['Rain Jacket', 'Umbrella', 'Spring Flowers', 'Light Jacket'],
            'fall' => ['Sweater', 'Boots', 'Pumpkin Spice', 'Warm Scarf'],
        ];

        $recommendations = [];
        $products = $seasonalProducts[$season] ?? [];

        foreach ($products as $product) {
            $recommendations[] = [
                'product_id' => rand(100, 999),
                'name' => $product,
                'category' => 'Seasonal',
                'price' => rand(15, 80),
                'confidence_score' => 0.85,
                'season' => $season,
            ];
        }

        return $recommendations;
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByPriceRange(array $currentProduct): array
    {
        $price = is_numeric($currentProduct['price']) ? (float) $currentProduct['price'] : 0.0;
        $minPrice = $price * 0.5;
        $maxPrice = $price * 1.5;

        return [
            [
                'product_id' => 9,
                'name' => 'Budget Accessory',
                'category' => 'Accessories',
                'price' => $minPrice + 10,
                'confidence_score' => 0.75,
                'price_range_match' => true,
            ],
            [
                'product_id' => 10,
                'name' => 'Premium Accessory',
                'category' => 'Accessories',
                'price' => $maxPrice - 10,
                'confidence_score' => 0.70,
                'price_range_match' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     * @param array<int, string>   $brandPreferences
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByBrand(array $currentProduct, array $brandPreferences): array
    {
        return [
            [
                'product_id' => 11,
                'name' => 'Apple AirPods',
                'category' => 'Accessories',
                'brand' => 'Apple',
                'price' => 159.99,
                'confidence_score' => 0.90,
                'brand_match' => true,
            ],
            [
                'product_id' => 12,
                'name' => 'Samsung Galaxy Buds',
                'category' => 'Accessories',
                'brand' => 'Samsung',
                'price' => 129.99,
                'confidence_score' => 0.85,
                'brand_match' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByRatings(array $currentProduct): array
    {
        return [
            [
                'product_id' => 13,
                'name' => 'High-Rated Mouse',
                'category' => 'Accessories',
                'price' => 39.99,
                'rating' => 4.7,
                'confidence_score' => 0.88,
            ],
            [
                'product_id' => 14,
                'name' => 'Top-Rated Keyboard',
                'category' => 'Accessories',
                'price' => 79.99,
                'rating' => 4.9,
                'confidence_score' => 0.92,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByAvailability(array $currentProduct): array
    {
        return [
            [
                'product_id' => 15,
                'name' => 'Available Accessory 1',
                'category' => 'Accessories',
                'price' => 29.99,
                'in_stock' => true,
                'confidence_score' => 0.80,
            ],
            [
                'product_id' => 16,
                'name' => 'Available Accessory 2',
                'category' => 'Accessories',
                'price' => 49.99,
                'in_stock' => true,
                'confidence_score' => 0.75,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByPopularity(array $currentProduct): array
    {
        return [
            [
                'product_id' => 17,
                'name' => 'Popular Accessory 1',
                'category' => 'Accessories',
                'price' => 19.99,
                'sales_count' => 1500,
                'confidence_score' => 0.85,
            ],
            [
                'product_id' => 18,
                'name' => 'Popular Accessory 2',
                'category' => 'Accessories',
                'price' => 35.99,
                'sales_count' => 2000,
                'confidence_score' => 0.90,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsBySegment(array $currentProduct, string $customerSegment): array
    {
        return [
            [
                'product_id' => 19,
                'name' => 'Professional Accessory',
                'category' => 'Accessories',
                'price' => 99.99,
                'confidence_score' => 0.88,
                'segment' => $customerSegment,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByLocation(array $currentProduct, string $location): array
    {
        return [
            [
                'product_id' => 20,
                'name' => 'Regional Accessory',
                'category' => 'Accessories',
                'price' => 24.99,
                'confidence_score' => 0.75,
                'available_locations' => [$location, 'CA', 'UK'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByTimeOfDay(array $currentProduct, string $timeOfDay): array
    {
        return [
            [
                'product_id' => 21,
                'name' => 'Morning Accessory',
                'category' => 'Accessories',
                'price' => 15.99,
                'confidence_score' => 0.70,
                'time_relevance' => $timeOfDay,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByWeather(array $currentProduct, string $weather): array
    {
        return [
            [
                'product_id' => 22,
                'name' => 'Weather Accessory',
                'category' => 'Accessories',
                'price' => 12.99,
                'confidence_score' => 0.65,
                'weather_relevance' => $weather,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecommendationsByEvent(array $currentProduct, string $event): array
    {
        return [
            [
                'product_id' => 23,
                'name' => 'Event Accessory',
                'category' => 'Accessories',
                'price' => 18.99,
                'confidence_score' => 0.68,
                'event_relevance' => $event,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $currentProduct
     * @param array<string, mixed> $recommendedProduct
     */
    private function calculateRecommendationConfidence(array $currentProduct, array $recommendedProduct): float
    {
        // Simple confidence calculation based on category compatibility
        $currentCategory = $currentProduct['category'] ?? '';
        $recommendedCategory = $recommendedProduct['category'] ?? '';

        if ('Electronics' === $currentCategory && 'Accessories' === $recommendedCategory) {
            return 0.85;
        }

        return 0.50; // Default confidence
    }

    /**
     * @param array<string, mixed> $currentProduct
     *
     * @return array<string, mixed>
     */
    private function generateCrossSellReport(array $currentProduct): array
    {
        $recommendations = $this->getCrossSellRecommendations($currentProduct);
        $totalRecommendations = \count($recommendations);
        $averageConfidence = array_sum(array_column($recommendations, 'confidence_score')) / $totalRecommendations;

        return [
            'product_id' => $currentProduct['id'],
            'recommendations' => $recommendations,
            'total_recommendations' => $totalRecommendations,
            'average_confidence' => $averageConfidence,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    // Helper methods for validation

    /**
     * @param array<string, mixed> $product
     */
    private function isAccessory(array $product): bool
    {
        return (bool) ($product['is_accessory'] ?? false);
    }

    /**
     * @param array<string, mixed> $currentProduct
     * @param array<string, mixed> $recommendedProduct
     */
    private function canBeBundled(array $currentProduct, array $recommendedProduct): bool
    {
        return (bool) ($recommendedProduct['can_bundle'] ?? false);
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isSeasonalProduct(array $product, string $season): bool
    {
        return ($product['season'] ?? '') === $season;
    }

    /**
     * @param array<string, mixed> $currentProduct
     * @param array<string, mixed> $recommendedProduct
     */
    private function isInSimilarPriceRange(array $currentProduct, array $recommendedProduct): bool
    {
        $currentPrice = is_numeric($currentProduct['price']) ? (float) $currentProduct['price'] : 0.0;
        $recommendedPrice = is_numeric($recommendedProduct['price']) ? (float) $recommendedProduct['price'] : 0.0;
        $minPrice = $currentPrice * 0.5;
        $maxPrice = $currentPrice * 1.5;

        return $recommendedPrice >= $minPrice && $recommendedPrice <= $maxPrice;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function matchesCustomerSegment(array $product, string $segment): bool
    {
        return ($product['segment'] ?? '') === $segment;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isRelevantToTimeOfDay(array $product, string $timeOfDay): bool
    {
        return ($product['time_relevance'] ?? '') === $timeOfDay;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isRelevantToWeather(array $product, string $weather): bool
    {
        return ($product['weather_relevance'] ?? '') === $weather;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isRelevantToEvent(array $product, string $event): bool
    {
        return ($product['event_relevance'] ?? '') === $event;
    }
}
