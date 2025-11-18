<?php

namespace App\Services\Payout;

use Illuminate\Support\Facades\Log;

class WalletPayoutAdapter implements PayoutAdapterInterface
{
    public function processPayout(string $sellerId, int $amount, array $payoutDetails): array
    {
        // TODO: Integrate with actual wallet API (eSewa, Khalti, IME Pay, etc.)
        // For now, this is a mock implementation
        
        Log::info('Processing wallet payout', [
            'seller_id' => $sellerId,
            'amount' => $amount,
            'details' => $payoutDetails,
        ]);

        // Mock implementation - replace with actual API call
        // Example structure:
        // $response = Http::post('https://wallet-api.example.com/transfer', [
        //     'wallet_id' => $payoutDetails['wallet_id'],
        //     'phone' => $payoutDetails['phone'],
        //     'amount' => $amount / 100, // Convert to main currency unit
        //     'reference' => $reference,
        // ]);

        // Simulate API call delay
        sleep(1);

        // Mock success response
        $transactionId = 'WALLET-' . strtoupper(uniqid());

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'error' => null,
        ];
    }
}

