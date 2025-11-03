<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property int|null    $parent_id
 * @property int         $level
 * @property bool        $is_active
 ** @property Carbon|nullCarbon|null $created_at
 ** @property Carbon|nullCarbon|null $updated_at
 ** @property Carbon|nullCarbon|null $deleted_at
 * @property Category|null             $parent
 * @property Collection<int, Category> $childfinal ren
 **  @property Collection<int, Product> $products
 *
 * @method static \App\Models\Category create(array<string, string|int|bool|* @method static \App\Models\Brand create(array<string, string|bool|null> $attributes = [])
 * @method static CategoryFactory      factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\CategoryFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Category extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    /**
     * @var class-string<Factory<Category>>
     */
    protected static $factory = CategoryFactory::class;

    // Use $errors from ValidatableModel (MessageBag). Do not redeclare here.

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'level',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255|unique:categories,slug',
        'description' => 'nullable|string|max:1000',
        'parent_id' => 'nullable|exists:categories,id',
        'level' => 'integer|min:0',
        'is_active' => 'boolean',
    ];

    // --- Relationships ---

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // --- Scopes ---

    /**
     * Scope a query to only include active categories.
     *
     * @param mixed $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search categories by name.
     *
     * @param mixed $query
     * @param mixed $term
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', '%'.$term.'%');
    }

    // --- Methods ---

    /**
     * Get the validation rules for the model.
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Boot the model.
     */
    #[\Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(/**
         * @return true
         */
            static fn (Category $category): bool => $category->handleCreatingEvent()
        );

        static::updating(/**
         * @return true
         */
            static fn (Category $category): bool => $category->handleUpdatingEvent()
        );
    }

    private function handleCreatingEvent(): bool
    {
        $this->generateSlug();
        // Respect explicitly provided level when no parent is set
        if (null !== $this->parent_id || null === $this->level) {
            $this->calculateLevel();
        }

        return true;
    }

    private function handleUpdatingEvent(): bool
    {
        if ($this->isDirty('name')) {
            $this->slug = Str::slug($this->name);
        }

        if ($this->isDirty('parent_id')) {
            $this->calculateLevel();
        }

        return true;
    }

    private function generateSlug(): void
    {
        if ((null === $this->slug) || ('' === $this->slug)) {
            $this->slug = \Str::slug($this->name);
        }
    }

    private function calculateLevel(): void
    {
        // Recalculate level based on parent when applicable
        if (null !== $this->parent_id) {
            $this->load('parent');
            $parent = $this->parent;
            $this->level = $parent ? $parent->level + 1 : 0;

            return;
        }

        // No parent: set default only if not explicitly provided
        if (null === $this->level) {
            $this->level = 0;
        }
    }
}
