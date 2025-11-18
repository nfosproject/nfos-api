<?php

namespace App\Services\Notifications;

use App\Mail\OrderCreatedCustomerMail;
use App\Mail\OrderCreatedSellerMail;
use App\Mail\OrderStatusChangedCustomerMail;
use App\Mail\OrderStatusChangedSellerMail;
use App\Models\Notification;
use App\Models\Order;
use App\Services\SMS\SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    public function __construct(private SmsService $smsService)
    {
    }

    public function sendOrderCreated(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        // Load relationships
        $order->load(['buyer', 'seller', 'items']);

        // Create in-app notification for customer
        Notification::create([
            'user_id' => $order->user_id,
            'audience' => 'customer',
            'type' => 'order_status',
            'title' => 'Order placed successfully',
            'message' => sprintf('Your order %s has been placed successfully.', $order->order_number ?? $order->id),
            'link' => $this->orderLink($order),
            'metadata' => $this->baseMetadata($order),
        ]);

        // Send email to customer (synchronously, not queued)
        try {
            if ($order->buyer && $order->buyer->email) {
                Mail::to($order->buyer->email)->send(new OrderCreatedCustomerMail($order));
                Log::info('Order created email sent to customer', [
                    'order_id' => $order->id,
                    'customer_email' => $order->buyer->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order created email to customer', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send email to seller if exists (synchronously, not queued)
        try {
            if ($order->seller && $order->seller->email) {
                Mail::to($order->seller->email)->send(new OrderCreatedSellerMail($order));
                Log::info('Order created email sent to seller', [
                    'order_id' => $order->id,
                    'seller_email' => $order->seller->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order created email to seller', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send SMS to customer
        try {
            if ($order->buyer && $order->buyer->phone) {
                $message = "Your order {$order->order_number} has been placed successfully. Total: Rs. " . number_format($order->grand_total / 100, 2);
                $this->smsService->send($order->buyer->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order created SMS to customer', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendOrderStatusChanged(Order $order, string $previousStatus): void
    {
        if (! $order->user_id || $order->status === $previousStatus) {
            return;
        }

        // Load relationships
        $order->load(['buyer', 'seller', 'items']);

        $copy = $this->statusCopy($order->status, $order->order_number ?? $order->id);

        // Create in-app notification for customer
        Notification::create([
            'user_id' => $order->user_id,
            'audience' => 'customer',
            'type' => 'order_status',
            'title' => $copy['title'],
            'message' => $copy['message'],
            'link' => $this->orderLink($order),
            'metadata' => array_merge($this->baseMetadata($order), [
                'previous_status' => $previousStatus,
            ]),
        ]);

        // Send email to customer (synchronously, not queued)
        try {
            if ($order->buyer && $order->buyer->email) {
                Mail::to($order->buyer->email)->send(new OrderStatusChangedCustomerMail($order, $previousStatus));
                Log::info('Order status changed email sent to customer', [
                    'order_id' => $order->id,
                    'customer_email' => $order->buyer->email,
                    'status' => $order->status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order status changed email to customer', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send email to seller if exists (synchronously, not queued)
        try {
            if ($order->seller && $order->seller->email) {
                Mail::to($order->seller->email)->send(new OrderStatusChangedSellerMail($order, $previousStatus));
                Log::info('Order status changed email sent to seller', [
                    'order_id' => $order->id,
                    'seller_email' => $order->seller->email,
                    'status' => $order->status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order status changed email to seller', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send SMS to customer for order status updates
        try {
            if ($order->buyer && $order->buyer->phone) {
                $this->smsService->sendOrderStatusUpdate(
                    $order->buyer->phone,
                    $order->order_number ?? $order->id,
                    $order->status
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order status SMS to customer', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function baseMetadata(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
        ];
    }

    private function formatStatus(?string $status): string
    {
        return $status ? ucwords(str_replace('_', ' ', $status)) : 'Unknown';
    }

    private function orderLink(Order $order): string
    {
        return '/orders/' . $order->id;
    }

    private function statusCopy(?string $status, string $orderNumber): array
    {
        $status = strtolower($status ?? '');
        return match ($status) {
            'paid', 'processing', 'confirmed' => [
                'title' => 'Order confirmed',
                'message' => "Your order #{$orderNumber} has been confirmed and is moving to fulfilment.",
            ],
            'shipped' => [
                'title' => 'Order shipped',
                'message' => "Great news! Order #{$orderNumber} is on the way to you.",
            ],
            'completed' => [
                'title' => 'Order delivered',
                'message' => "Order #{$orderNumber} has been delivered. We hope you love it!",
            ],
            'cancelled' => [
                'title' => 'Order cancelled',
                'message' => "Order #{$orderNumber} has been cancelled. Contact support if this was unexpected.",
            ],
            'refunded' => [
                'title' => 'Order refunded',
                'message' => "A refund has been processed for order #{$orderNumber}.",
            ],
            default => [
                'title' => 'Order status updated',
                'message' => "Your order #{$orderNumber} status is now {$this->formatStatus($status)}.",
            ],
        };
    }
}


