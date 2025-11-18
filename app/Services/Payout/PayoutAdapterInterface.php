<?php

namespace App\Services\Payout;

interface PayoutAdapterInterface
{
    /**
     * Process a payout to a seller
     *
     * @param string $sellerId
     * @param int $amount Amount in smallest currency unit
     * @param array $payoutDetails Bank account or wallet details
     * @return array ['success' => bool, 'transaction_id' => string|null, 'error' => string|null]
     */
    public function processPayout(string $sellerId, int $amount, array $payoutDetails): array;
}

