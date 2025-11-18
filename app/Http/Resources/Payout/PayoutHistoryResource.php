<?php

namespace App\Http\Resources\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutHistoryResource extends JsonResource
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
            'batch_number' => $this->payoutBatch->batch_number ?? null,
            'amount' => (int) $this->amount,
            'status' => $this->status,
            'payout_method' => $this->payout_method,
            'transaction_id' => $this->transaction_id,
            'error_message' => $this->error_message,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'transaction' => $this->whenLoaded('transaction', fn () => [
                'id' => $this->transaction->id,
                'transaction_id' => $this->transaction->transaction_id,
                'status' => $this->transaction->status,
                'initiated_at' => $this->transaction->initiated_at?->toIso8601String(),
                'completed_at' => $this->transaction->completed_at?->toIso8601String(),
            ]),
        ];
    }
}
