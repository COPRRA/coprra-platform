<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'payment_method_id',
        'transaction_id',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'processed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];

    // --- Relationships ---

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment method that owns the payment.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // --- Scopes ---

    /**
     * Scope a query to only include payments with a specific status.
     *
     * @param mixed $query
     * @param mixed $status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
