<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AICostLog extends Model
{
    protected $fillable = [
        'service_name',
        'operation',
        'tokens_used',
        'estimated_cost',
        'metadata',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'estimated_cost' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get total cost for today.
     */
    public static function getTodayCost(): float
    {
        return (float) self::whereDate('created_at', today())
            ->sum('estimated_cost')
        ;
    }

    /**
     * Get cost by service for a date range.
     */
    public static function getCostByService(\DateTime $start, \DateTime $end): array
    {
        return self::whereBetween('created_at', [$start, $end])
            ->selectRaw('service_name, SUM(estimated_cost) as total_cost, SUM(tokens_used) as total_tokens')
            ->groupBy('service_name')
            ->get()
            ->toArray()
        ;
    }
}
