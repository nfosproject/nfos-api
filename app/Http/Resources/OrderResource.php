<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = $this->metadata ?? [];

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'current_stage' => $metadata['current_stage'] ?? null,
            'payment_status' => $metadata['payment_status'] ?? 'pending',
            'payment_method' => $metadata['payment_method'] ?? null,
            'payment_reference' => $metadata['payment_reference'] ?? null,
            'coupon' => $metadata['coupon'] ?? null,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'delivery_time' => $this->delivery_time,
            'totals' => [
                'subtotal' => (int) $this->subtotal,
                'discount' => (int) $this->discount_total,
                'shipping' => (int) $this->shipping_total,
                'tax' => (int) $this->tax_total,
                'total' => (int) $this->grand_total,
            ],
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'journey' => collect($metadata['journey'] ?? [])
                ->map(fn ($timestamp) => $timestamp ? (string) $timestamp : null)
                ->toArray(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
