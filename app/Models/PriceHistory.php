<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'price',
        'effective_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_date' => 'datetime',
    ];
}
