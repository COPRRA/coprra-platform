<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class ReviewNotification extends Mailable implements ShouldQueue
{
    use Queueable;

    protected Product $product;

    protected User $reviewer;

    protected float|int $rating;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, User $reviewer, float|int $rating)
    {
        $this->product = $product;
        $this->reviewer = $reviewer;
        $this->rating = $rating;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<float|int>
     *
     * @psalm-return array{product_id: int, reviewer_id: int, rating: float|int}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'reviewer_id' => $this->reviewer->id,
            'rating' => $this->rating,
        ];
    }
}
