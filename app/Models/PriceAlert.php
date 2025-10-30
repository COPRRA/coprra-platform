<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PriceAlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\MessageBag;

/**
 * @property int   $id
 * @property int   $user_id
 * @property int   $product_id
 * @property float $target_price
 * @property bool  $repeat_alert
 * @property bool  $is_active
 ** @property Carbon|nullCarbon|null $created_at
 ** @property Carbon|nullCarbon|null $updated_at
 ** @property Carbon|nullCarbon|null $deleted_at
 ** @property User $user
 * @property Product $product
 *
 * @method static PriceAlertFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\PriceAlertFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PriceAlert extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<Factory<PriceAlert>>
     */
    protected static $factory = PriceAlertFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'target_price',
        'repeat_alert',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'target_price' => 'decimal:2',
        'repeat_alert' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'user_id' => 'required|exists:users,id',
        'product_id' => 'required|exists:products,id',
        'target_price' => 'required|numeric|min:0',
        'repeat_alert' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Validation errors.
     */
    protected ?MessageBag $errors = null;

    /**
     * Return only explicit casts defined on the model.
     * This excludes framework-added defaults like the primary key or deleted_at.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function getCasts(): array
    {
        $casts = $this->casts;

        $deletedAt = method_exists($this, 'getDeletedAtColumn') ? $this->getDeletedAtColumn() : 'deleted_at';
        unset($casts[$deletedAt]);

        return $casts;
    }

    // --- Relationships ---

    /**
     * Get the user that owns the price alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that this price alert is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // --- Scopes ---

    /**
     * Scope a query to only include active price alerts.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include price alerts for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include price alerts for a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    // --- Methods ---

    /**
     * Get the validation rules.
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Check if the price target has been reached.
     */
    public function isPriceTargetReached(float $currentPrice): bool
    {
        return $currentPrice <= $this->target_price;
    }
}
