<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedSellerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus
    ) {
    }

    public function envelope(): Envelope
    {
        $statusMap = [
            'processing' => 'Order Processing',
            'shipped' => 'Order Shipped',
            'completed' => 'Order Completed',
            'cancelled' => 'Order Cancelled',
            'refunded' => 'Order Refunded',
        ];

        $statusTitle = $statusMap[strtolower($this->order->status)] ?? 'Order Status Updated';

        return new Envelope(
            subject: $statusTitle . ': ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-changed-seller',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'appName' => config('app.name'),
                'orderTotal' => number_format($this->order->grand_total / 100, 2),
            ],
        );
    }
}

