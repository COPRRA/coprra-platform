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
final class CategoryRecommendationTest extends TestCase
{
    #[Test]
    public function itRecommendsCategoriesBasedOnUserHistory(): void
    {
        $userHistory = [
            ['category' => 'Electronics', 'purchases' => 5],
            ['category' => 'Clothing', 'purchases' => 3],
            ['category' => 'Books', 'purchases' => 2],
        ];

        $recommendations = $this->getCategoryRecommendations($userHistory);

        self::assertContains('Electronics', $recommendations);
        self::assertContains('Clothing', $recommendations);
        self::assertCount(2, $recommendations);
    }

    #[Test]
    public function itRecommendsPopularCategories(): void
    {
        $categoryStats = [
            'Electronics' => 1000,
            'Clothing' => 800,
            'Books' => 600,
            'Home' => 400,
            'Sports' => 200,
        ];

        $recommendations = $this->getPopularCategoryRecommendations($categoryStats, 3);

        self::assertContains('Electronics', $recommendations);
        self::assertContains('Clothing', $recommendations);
        self::assertContains('Books', $recommendations);
        self::assertCount(3, $recommendations);
    }

    #[Test]
    public function itRecommendsRelatedCategories(): void
    {
        $currentCategory = 'Electronics';
        $categoryRelations = [
            'Electronics' => ['Accessories', 'Gadgets', 'Computers'],
            'Clothing' => ['Shoes', 'Accessories', 'Jewelry'],
            'Books' => ['Educational', 'Fiction', 'Non-fiction'],
        ];

        $recommendations = $this->getRelatedCategoryRecommendations($currentCategory, $categoryRelations);

        self::assertContains('Accessories', $recommendations);
        self::assertContains('Gadgets', $recommendations);
        self::assertContains('Computers', $recommendations);
    }

    #[Test]
    public function itHandlesSeasonalCategoryRecommendations(): void
    {
        $currentSeason = 'Winter';
        $seasonalCategories = [
            'Winter' => ['Winter Clothing', 'Heating', 'Hot Beverages'],
            'Summer' => ['Summer Clothing', 'Cooling', 'Cold Beverages'],
            'Spring' => ['Spring Clothing', 'Gardening', 'Outdoor'],
            'Fall' => ['Fall Clothing', 'Harvest', 'Indoor'],
        ];

        $recommendations = $this->getSeasonalCategoryRecommendations($currentSeason, $seasonalCategories);

        self::assertContains('Winter Clothing', $recommendations);
        self::assertContains('Heating', $recommendations);
        self::assertContains('Hot Beverages', $recommendations);
    }

    #[Test]
    public function itRecommendsCategoriesBasedOnDemographics(): void
    {
        $userProfile = [
            'age_group' => '25-35',
            'gender' => 'Female',
            'location' => 'Urban',
        ];

        $demographicCategories = [
            '25-35' => ['Electronics', 'Fashion', 'Fitness'],
            'Female' => ['Beauty', 'Fashion', 'Home Decor'],
            'Urban' => ['Tech', 'Fashion', 'Entertainment'],
        ];

        $recommendations = $this->getDemographicCategoryRecommendations($userProfile, $demographicCategories);

        self::assertNotEmpty($recommendations);
        self::assertContains('Fashion', $recommendations);
    }

    #[Test]
    public function itFiltersCategoriesByAvailability(): void
    {
        $allCategories = ['Electronics', 'Clothing', 'Books', 'Sports'];
        $availableCategories = ['Electronics', 'Books', 'Sports'];

        $filteredRecommendations = $this->filterCategoriesByAvailability($allCategories, $availableCategories);

        self::assertNotContains('Clothing', $filteredRecommendations);
        self::assertContains('Electronics', $filteredRecommendations);
        self::assertContains('Books', $filteredRecommendations);
    }

    #[Test]
    public function itRanksCategoriesByRelevanceScore(): void
    {
        $categories = [
            ['name' => 'Electronics', 'score' => 0.9],
            ['name' => 'Clothing', 'score' => 0.7],
            ['name' => 'Books', 'score' => 0.8],
            ['name' => 'Sports', 'score' => 0.6],
        ];

        $rankedCategories = $this->rankCategoriesByScore($categories);

        self::assertIsArray($rankedCategories[0]);
        self::assertSame('Electronics', $rankedCategories[0]['name']);
        self::assertIsArray($rankedCategories[1]);
        self::assertSame('Books', $rankedCategories[1]['name']);
        self::assertIsArray($rankedCategories[2]);
        self::assertSame('Clothing', $rankedCategories[2]['name']);
        self::assertIsArray($rankedCategories[3]);
        self::assertSame('Sports', $rankedCategories[3]['name']);
    }

    #[Test]
    public function itHandlesEmptyCategoryData(): void
    {
        $emptyHistory = [];
        $recommendations = $this->getCategoryRecommendations($emptyHistory);

        self::assertEmpty($recommendations);
    }

    #[Test]
    public function itCalculatesCategoryAffinityScore(): void
    {
        $userPurchases = [
            'Electronics' => 10,
            'Clothing' => 5,
            'Books' => 2,
        ];

        $category = 'Electronics';
        $affinityScore = $this->calculateCategoryAffinityScore($userPurchases, $category);

        self::assertGreaterThan(0.5, $affinityScore);
    }

    #[Test]
    public function itRecommendsTrendingCategories(): void
    {
        $categoryTrends = [
            'Electronics' => ['trend' => 'up', 'growth' => 0.15],
            'Clothing' => ['trend' => 'stable', 'growth' => 0.02],
            'Books' => ['trend' => 'down', 'growth' => -0.05],
            'Fitness' => ['trend' => 'up', 'growth' => 0.25],
        ];

        $trendingCategories = $this->getTrendingCategoryRecommendations($categoryTrends);

        self::assertContains('Electronics', $trendingCategories);
        self::assertContains('Fitness', $trendingCategories);
        self::assertNotContains('Books', $trendingCategories);
    }

    /**
     * @param array<int, array<string, mixed>> $userHistory
     *
     * @return list<mixed>
     */
    private function getCategoryRecommendations(array $userHistory): array
    {
        if (empty($userHistory)) {
            return [];
        }

        // Sort by purchase count and return top 2 categories
        usort($userHistory, static function ($a, $b) {
            return $b['purchases'] <=> $a['purchases'];
        });

        return \array_slice(array_column($userHistory, 'category'), 0, 2);
    }

    /**
     * @param array<string, mixed> $categoryStats
     *
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param array<string, mixed> $categoryStats
     *
     * @return list<string>
     */
    private function getPopularCategoryRecommendations(array $categoryStats, int $limit): array
    {
        arsort($categoryStats);

        return \array_slice(array_keys($categoryStats), 0, $limit);
    }

    /**
     * @param array<string, list<string>> $categoryRelations
     *
     * @return list<string>
     */
    private function getRelatedCategoryRecommendations(string $currentCategory, array $categoryRelations): array
    {
        return $categoryRelations[$currentCategory] ?? [];
    }

    /**
     * @param array<string, list<string>> $seasonalCategories
     *
     * @return list<string>
     */
    private function getSeasonalCategoryRecommendations(string $season, array $seasonalCategories): array
    {
        return $seasonalCategories[$season] ?? [];
    }

    /**
     * @param array<string, mixed> $userProfile
     *
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param array<string, mixed>        $userProfile
     * @param array<string, list<string>> $demographicCategories
     *
     * @return array<int, string>
     */
    private function getDemographicCategoryRecommendations(array $userProfile, array $demographicCategories): array
    {
        $recommendations = [];

        foreach ($userProfile as $demographic => $value) {
            if (\is_string($value) && isset($demographicCategories[$value])) {
                $categories = $demographicCategories[$value];
                if (\is_array($categories)) {
                    $recommendations = array_merge($recommendations, $categories);
                }
            }
        }

        return array_unique($recommendations);
    }

    /**
     * @param array<int, array<string, mixed>> $allCategories
     *
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param list<string> $allCategories
     * @param list<string> $availableCategories
     *
     * @return array<int, string>
     */
    private function filterCategoriesByAvailability(array $allCategories, array $availableCategories): array
    {
        return array_intersect($allCategories, $availableCategories);
    }

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return list<array<string, mixed>>
     */
    private function rankCategoriesByScore(array $categories): array
    {
        usort($categories, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $categories;
    }

    /**
     * @param array<string, int> $userPurchases
     */
    private function calculateCategoryAffinityScore(array $userPurchases, string $category): float
    {
        $totalPurchases = array_sum($userPurchases);
        $categoryPurchases = $userPurchases[$category] ?? 0;

        return $totalPurchases > 0 ? $categoryPurchases / $totalPurchases : 0;
    }

    /**
     * @param array<string, array<string, mixed>> $categoryTrends
     *
     * @return list<string>
     */
    private function getTrendingCategoryRecommendations(array $categoryTrends): array
    {
        $trending = [];

        foreach ($categoryTrends as $category => $data) {
            if ('up' === $data['trend'] && $data['growth'] > 0.1) {
                $trending[] = $category;
            }
        }

        return $trending;
    }
}
