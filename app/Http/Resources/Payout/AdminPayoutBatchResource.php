<?php

namespace App\Http\Resources\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPayoutBatchResource extends JsonResource
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
            'items_count' => $this->items_count ?? 0,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
