<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Currency model.
 *
 * @internal
 */
#[CoversClass(Currency::class)]
final class CurrencyTest extends TestCase
{
    /**
     * Test guarded attributes.
     */
    public function testGuardedAttributes(): void
    {
        $guarded = [];

        self::assertSame($guarded, (new Currency())->getGuarded());
    }

    /**
     * Test stores relation is a HasMany instance.
     */
    public function testStoresRelation(): void
    {
        $currency = new Currency();

        $relation = $currency->stores();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(Store::class, $relation->getRelated()::class);
    }

    /**
     * Test languages relation is a BelongsToMany instance.
     */
    public function testLanguagesRelation(): void
    {
        $currency = new Currency();

        $relation = $currency->languages();

        self::assertInstanceOf(BelongsToMany::class, $relation);
        self::assertSame(Language::class, $relation->getRelated()::class);
        self::assertSame('currency_language', $relation->getTable());
    }
}
