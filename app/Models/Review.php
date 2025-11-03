<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                $id
 * @property int                $user_id
 * @property int                $product_id
 * @property string             $title
 * @property string             $content
 * @property int                $rating
 * @property bool               $is_verified_purchase
 * @property bool               $is_approved
 * @property array<string, int> $helpful_votes
 ** @property nt
 * @property User    $user
 * @property Product $product
 *
 * @method static ReviewFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\ReviewFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Review extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    /**
     * @var class-string<Factory<Review>>
     */
    protected static $factory = ReviewFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'title',
        'content',
        'rating',
        'is_verified_purchase',
        'is_approved',
        'helpful_votes',
        'helpful_count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_votes' => 'array',
        'helpful_count' => 'integer',
        'rating' => 'integer',
    ];

    // --- Relationships ---

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that the review belongs to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // --- Accessors ---

    /**
     * Get the review text attribute.
     */
    public function getReviewTextAttribute()
    {
        return $this->content;
    }
}
