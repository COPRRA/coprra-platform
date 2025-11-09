<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scraper Job Model
 *
 * Tracks the status of each scraping operation
 *
 * @property int $id
 * @property string $batch_id
 * @property int $job_number
 * @property string $url
 * @property string|null $store_adapter
 * @property string $status
 * @property int|null $product_id
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class ScraperJob extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_id',
        'job_number',
        'url',
        'store_adapter',
        'status',
        'product_id',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that was created by this scraper job.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mark job as running.
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed.
     */
    public function markAsCompleted(?int $productId = null): void
    {
        $this->update([
            'status' => 'completed',
            'product_id' => $productId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark job as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the duration in seconds.
     */
    public function getDuration(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return (int) round($this->completed_at->diffInSeconds($this->started_at));
        }

        return null;
    }

    /**
     * Check if job is still pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'running']);
    }

    /**
     * Check if job is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if job failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope to get recent jobs.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get jobs by batch.
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId)->orderBy('job_number');
    }

    /**
     * Scope to get pending jobs.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['queued', 'running']);
    }

    /**
     * Scope to get completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
