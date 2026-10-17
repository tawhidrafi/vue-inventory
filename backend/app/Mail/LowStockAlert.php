<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public Warehouse $warehouse,
        public int $quantity,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Low stock: {$this->product->name} ({$this->product->sku})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock',
        );
    }
}
