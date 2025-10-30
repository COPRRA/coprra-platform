<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type TFactory \Illuminate\Database\Eloquent\Factories\Factory<Reward>
 */
class Reward extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'value',
        'is_active',
        'usage_limit',
        'valid_from',
        'valid_until',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];
}
