<?php

namespace App\Http\Resources\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSellerPayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $balance = $this->balance;
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'payout_method' => $this->payout_method,
            'payout_verified' => (bool) $this->payout_verified,
            'payout_threshold' => (int) ($this->payout_threshold ?? 100000),
            'available_balance' => $balance ? (int) $balance->available_balance : 0,
            'pending_balance' => $balance ? (int) $balance->pending_balance : 0,
            'total_earned' => $balance ? (int) $balance->total_earned : 0,
            'total_paid_out' => $balance ? (int) $balance->total_paid_out : 0,
            'last_payout_at' => $balance?->last_payout_at?->toIso8601String(),
            'pending_payouts' => (int) ($this->pending_payouts ?? 0),
        ];
    }
}
