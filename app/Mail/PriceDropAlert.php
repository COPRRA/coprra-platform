<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable for price drop alert notifications.
 */
final class PriceDropAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly PriceAlert $alert,
        public readonly Product $product,
        public readonly float $currentPrice
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Price Drop Alert: :product', ['product' => $this->product->name]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.price_drop_alert',
            with: [
                'user' => $this->alert->user,
                'product' => $this->product,
                'alert' => $this->alert,
                'currentPrice' => $this->currentPrice,
                'targetPrice' => $this->alert->target_price,
                'offersUrl' => route('products.offers', $this->product->slug),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

