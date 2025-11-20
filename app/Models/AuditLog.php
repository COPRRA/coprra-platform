<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int         $id
 * @property string      $event
 * @property string      $auditable_type
 * @property int         $auditable_id
 * @property int|null    $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, string|int|bool|* @method static \App\Models\Brand create(array<string, string|bool|null>|null $old_values
 * @property array<string, string|int|bool|* @method static \App\Models\Brand create(array<string, string|bool|null>|null $new_values
 * @property array<string, string|int|bool|* @method static \App\Models\Brand create(array<string, string|bool|null>|null $metadata
 * @property string|null $url
 * @property string|null $method
 ** @property Carbon|nullCarbon|null $created_at
 *                                           final final* @property Carbon|nullCarbon|null $updated_at
 * @property User|null  $user
 * @property Model|null $auditable
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'user_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'url',
        'method',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'int',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // --- Relationships ---

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // --- Scopes ---

    /**
     * Scope a query to filter by event type.
     */
    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by model type.
     */
    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('auditable_type', $modelType);
    }

    // --- Accessors ---

    /**
     * Get the formatted event attribute.
     */
    public function getFormattedEventAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->event));
    }

    /**
     * Get the changes summary attribute.
     */
    public function getChangesSummaryAttribute(): string
    {
        if (empty($this->old_values) && empty($this->new_values)) {
            return 'No changes recorded';
        }

        if (empty($this->new_values) || empty($this->old_values)) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                // Convert boolean values to 0/1 for display
                $displayOldValue = \is_bool($oldValue) ? (int) $oldValue : ($oldValue ?? 'null');
                $displayNewValue = \is_bool($newValue) ? (int) $newValue : ($newValue ?? 'null');
                $changes[] = "{$key}: {$displayOldValue} → {$displayNewValue}";
            }
        }

        return empty($changes) ? '' : implode(', ', $changes);
    }
}
