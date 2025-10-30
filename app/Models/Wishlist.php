<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\MessageBag;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int         $product_id
 * @property string|null $notes
 *** @property Carbon|nullCarbon|null $created_at
 ** @property Carbon|nullCarbon|null $updated_at
 ** @property Carbon|nullCarbon|null $deleted_at
 * @property User    $user
 * @property Product $product
 *
 * @method static WishlistFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\WishlistFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Wishlist extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<Factory<Wishlist>>
     */
    protected static $factory = WishlistFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'notes',
    ];

    protected ?MessageBag $errors = null;

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'user_id' => 'required|exists:users,id',
        'product_id' => 'required|exists:products,id',
        'notes' => 'nullable|string|max:1000',
    ];
}
