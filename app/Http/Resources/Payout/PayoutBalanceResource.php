<?php

namespace App\Http\Resources\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'available_balance' => (int) $this->available_balance,
            'pending_balance' => (int) $this->pending_balance,
            'total_earned' => (int) $this->total_earned,
            'total_paid_out' => (int) $this->total_paid_out,
            'last_payout_at' => $this->last_payout_at?->toIso8601String(),
        ];
    }
}
