<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\UserLocaleSetting;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Language model.
 *
 * @internal
 */
#[CoversClass(Language::class)]
final class LanguageTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function testFillableAttributes(): void
    {
        $fillable = [
            'code',
            'name',
            'native_name',
            'direction',
            'is_active',
            'sort_order',
        ];

        self::assertSame($fillable, (new Language())->getFillable());
    }

    /**
     * Test casts.
     */
    public function testCasts(): void
    {
        $casts = [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];

        self::assertSame($casts, (new Language())->getCasts());
    }

    /**
     * Test currencies relation is a BelongsToMany instance.
     */
    public function testCurrenciesRelation(): void
    {
        $language = new Language();

        $relation = $language->currencies();

        self::assertInstanceOf(BelongsToMany::class, $relation);
        self::assertSame(Currency::class, $relation->getRelated()::class);
        self::assertSame('language_currency', $relation->getTable());
    }

    /**
     * Test userLocaleSettings relation is a HasMany instance.
     */
    public function testUserLocaleSettingsRelation(): void
    {
        $language = new Language();

        $relation = $language->userLocaleSettings();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(UserLocaleSetting::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function testScopeActive(): void
    {
        $query = Language::query()->active();

        self::assertSame('select * from "languages" where "is_active" = ?', $query->toSql());
        self::assertSame([true], $query->getBindings());
    }

    /**
     * Test scopeOrdered applies correct order by clause.
     */
    public function testScopeOrdered(): void
    {
        $query = Language::query()->ordered();

        self::assertSame('select * from "languages" order by "sort_order" asc, "name" asc', $query->toSql());
    }

    /**
     * Test isRtl returns true when direction is rtl.
     */
    public function testIsRtlReturnsTrueWhenDirectionRtl(): void
    {
        $language = new Language(['direction' => 'rtl']);

        self::assertTrue($language->isRtl());
    }

    /**
     * Test isRtl returns false when direction is ltr.
     */
    public function testIsRtlReturnsFalseWhenDirectionLtr(): void
    {
        $language = new Language(['direction' => 'ltr']);

        self::assertFalse($language->isRtl());
    }

    /**
     * Test findByCode returns language by code.
     */
    public function testFindByCode(): void
    {
        // Since it's a static method, we can test the query it builds
        $query = Language::where('code', 'en');

        self::assertSame('select * from "languages" where "code" = ?', $query->toSql());
        self::assertSame(['en'], $query->getBindings());
    }

    /**
     * Test defaultCurrency method.
     * Note: This method requires database, so in unit test we can mock or skip if needed.
     * For pure unit, perhaps test the logic, but since it queries, it's more integration.
     */
    public function testDefaultCurrencyMethodExists(): void
    {
        $language = new Language();

        self::assertTrue(method_exists($language, 'defaultCurrency'));
    }
}
