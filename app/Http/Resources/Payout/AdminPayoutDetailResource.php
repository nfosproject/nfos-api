<?php

namespace App\Http\Resources\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPayoutDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'status' => $this->status,
            'total_amount' => (int) $this->total_amount,
            'seller_count' => (int) $this->seller_count,
            'successful_count' => (int) $this->successful_count,
            'failed_count' => (int) $this->failed_count,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'seller_id' => $item->seller_id,
                    'seller_name' => $item->seller->name ?? 'N/A',
                    'seller_email' => $item->seller->email ?? 'N/A',
                    'amount' => (int) $item->amount,
                    'status' => $item->status,
                    'payout_method' => $item->payout_method,
                    'transaction_id' => $item->transaction_id,
                    'error_message' => $item->error_message,
                    'processed_at' => $item->processed_at?->toIso8601String(),
                    'transaction' => $item->transaction ? [
                        'id' => $item->transaction->id,
                        'transaction_id' => $item->transaction->transaction_id,
                        'status' => $item->transaction->status,
                        'initiated_at' => $item->transaction->initiated_at?->toIso8601String(),
                        'completed_at' => $item->transaction->completed_at?->toIso8601String(),
                    ] : null,
                ];
            })),
        ];
    }
}
