<?php

namespace App\Services\Payout;

use App\Models\Order;
use App\Models\PayoutBatch;
use App\Models\PayoutBatchItem;
use App\Models\PayoutTransaction;
use App\Models\SellerBalance;
use App\Models\SellerEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayoutService
{
    protected const PLATFORM_FEE_PERCENTAGE = 5; // 5% platform fee
    protected const RETURN_WINDOW_DAYS = 7; // 7 days return window

    /**
     * Mark orders as payout-eligible after delivery and return window clearance
     */
    public function markOrdersEligible(): int
    {
        $count = 0;
        $returnWindowDays = self::RETURN_WINDOW_DAYS;

        // Find completed orders that haven't been marked eligible yet
        // and where return window has passed
        $orders = Order::where('status', 'completed')
            ->where('payout_eligible', false)
            ->whereNotNull('delivery_date')
            ->whereNotNull('seller_id')
            ->where(function ($query) use ($returnWindowDays) {
                $query->whereNull('return_window_ends_at')
                    ->orWhere('return_window_ends_at', '<=', now());
            })
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, &$count) {
                // Calculate return window end date
                if (!$order->return_window_ends_at && $order->delivery_date) {
                    $order->return_window_ends_at = $order->delivery_date->copy()->addDays(self::RETURN_WINDOW_DAYS);
                }

                // Check if return window has passed
                if ($order->return_window_ends_at && $order->return_window_ends_at->isPast()) {
                    $order->payout_eligible = true;
                    $order->payout_eligible_at = now();
                    $order->save();

                    // Create seller earning record
                    $this->createSellerEarning($order);
                    $count++;
                }
            });
        }

        Log::info('Marked orders as payout eligible', ['count' => $count]);
        return $count;
    }

    /**
     * Create seller earning record for an eligible order
     */
    protected function createSellerEarning(Order $order): SellerEarning
    {
        // Calculate platform fee and net amount
        $platformFee = (int) round($order->grand_total * (self::PLATFORM_FEE_PERCENTAGE / 100));
        $netAmount = $order->grand_total - $platformFee;

        $earning = SellerEarning::create([
            'seller_id' => $order->seller_id,
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'status' => 'eligible',
            'eligible_at' => now(),
        ]);

        // Update seller balance
        $this->updateSellerBalance($order->seller_id, $netAmount, 0);

        return $earning;
    }

    /**
     * Update seller balance
     */
    protected function updateSellerBalance(string $sellerId, int $availableIncrease, int $pendingIncrease): void
    {
        $balance = SellerBalance::firstOrCreate(
            ['seller_id' => $sellerId],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_paid_out' => 0,
            ]
        );

        $balance->available_balance += $availableIncrease;
        $balance->pending_balance += $pendingIncrease;
        $balance->total_earned += ($availableIncrease + $pendingIncrease);
        $balance->save();
    }

    /**
     * Process weekly payout batch
     */
    public function processWeeklyPayout(int $threshold = 100000): array
    {
        // Get sellers with balance above threshold
        $sellers = User::where('role', 'seller')
            ->where('payout_verified', true)
            ->whereNotNull('payout_method')
            ->whereHas('balance', function ($query) use ($threshold) {
                $query->where('available_balance', '>=', $threshold);
            })
            ->with('balance')
            ->get();

        if ($sellers->isEmpty()) {
            Log::info('No sellers eligible for payout', ['threshold' => $threshold]);
            return ['success' => true, 'message' => 'No sellers eligible for payout', 'batch_id' => null];
        }

        // Create payout batch
        $batch = PayoutBatch::create([
            'batch_number' => 'PAYOUT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'status' => 'pending',
            'total_amount' => 0,
            'seller_count' => 0,
        ]);

        $totalAmount = 0;
        $items = [];

        foreach ($sellers as $seller) {
            $balance = $seller->balance;
            $amount = $balance->available_balance;

            if ($amount < $threshold) {
                continue;
            }

            $item = PayoutBatchItem::create([
                'payout_batch_id' => $batch->id,
                'seller_id' => $seller->id,
                'amount' => $amount,
                'status' => 'pending',
                'payout_method' => $seller->payout_method,
                'payout_details' => $seller->payout_details,
            ]);

            $items[] = $item;
            $totalAmount += $amount;
        }

        // Update batch totals
        $batch->update([
            'total_amount' => $totalAmount,
            'seller_count' => count($items),
            'status' => 'processing',
        ]);

        // Process each payout
        $successful = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $this->processPayoutItem($item);
                $successful++;
            } catch (\Exception $e) {
                Log::error('Payout item failed', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        // Update batch status
        $finalStatus = $failed === 0 ? 'completed' : ($successful === 0 ? 'failed' : 'partially_failed');
        $batch->update([
            'status' => $finalStatus,
            'successful_count' => $successful,
            'failed_count' => $failed,
            'processed_at' => now(),
        ]);

        Log::info('Payout batch processed', [
            'batch_id' => $batch->id,
            'successful' => $successful,
            'failed' => $failed,
        ]);

        return [
            'success' => true,
            'batch_id' => $batch->id,
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Process a single payout item
     */
    protected function processPayoutItem(PayoutBatchItem $item): void
    {
        $seller = $item->seller;
        $adapter = $this->getAdapter($seller->payout_method);

        $item->update(['status' => 'processing']);

        try {
            $result = $adapter->processPayout(
                $seller->id,
                $item->amount,
                $seller->payout_details ?? []
            );

            if ($result['success']) {
                // Create transaction record
                $transaction = PayoutTransaction::create([
                    'payout_batch_item_id' => $item->id,
                    'seller_id' => $seller->id,
                    'amount' => $item->amount,
                    'transaction_id' => $result['transaction_id'],
                    'status' => 'completed',
                    'payout_method' => $seller->payout_method,
                    'payout_details' => $seller->payout_details,
                    'initiated_at' => now(),
                    'completed_at' => now(),
                ]);

                // Update batch item
                $item->update([
                    'status' => 'completed',
                    'transaction_id' => $result['transaction_id'],
                    'processed_at' => now(),
                ]);

                // Update seller balance
                $balance = $seller->balance;
                $balance->available_balance -= $item->amount;
                $balance->total_paid_out += $item->amount;
                $balance->last_payout_at = now();
                $balance->save();

                // Mark earnings as paid
                SellerEarning::where('seller_id', $seller->id)
                    ->where('status', 'eligible')
                    ->whereNull('payout_batch_item_id')
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'payout_batch_item_id' => $item->id,
                    ]);
            } else {
                throw new \Exception($result['error'] ?? 'Payout failed');
            }
        } catch (\Exception $e) {
            // Mark item as failed
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Create failed transaction record
            PayoutTransaction::create([
                'payout_batch_item_id' => $item->id,
                'seller_id' => $seller->id,
                'amount' => $item->amount,
                'transaction_id' => null,
                'status' => 'failed',
                'payout_method' => $seller->payout_method,
                'payout_details' => $seller->payout_details,
                'error_message' => $e->getMessage(),
                'initiated_at' => now(),
            ]);

            // Balance remains unchanged (not deducted) on failure
            throw $e;
        }
    }

    /**
     * Get appropriate payout adapter
     */
    protected function getAdapter(string $method): PayoutAdapterInterface
    {
        return match ($method) {
            'bank' => new BankPayoutAdapter(),
            'wallet' => new WalletPayoutAdapter(),
            default => throw new \InvalidArgumentException("Unknown payout method: {$method}"),
        };
    }

    /**
     * Process manual payout for a specific seller
     */
    public function processManualPayout(string $sellerId): array
    {
        $seller = User::where('id', $sellerId)
            ->where('role', 'seller')
            ->with('balance')
            ->firstOrFail();

        if (!$seller->payout_verified || !$seller->payout_method) {
            throw new \Exception('Seller payout information not verified');
        }

        $balance = $seller->balance;
        if (!$balance || $balance->available_balance <= 0) {
            throw new \Exception('No available balance for payout');
        }

        // Create a single-item batch
        $batch = PayoutBatch::create([
            'batch_number' => 'MANUAL-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'status' => 'processing',
            'total_amount' => $balance->available_balance,
            'seller_count' => 1,
            'notes' => 'Manual payout triggered by admin',
        ]);

        $item = PayoutBatchItem::create([
            'payout_batch_id' => $batch->id,
            'seller_id' => $seller->id,
            'amount' => $balance->available_balance,
            'status' => 'pending',
            'payout_method' => $seller->payout_method,
            'payout_details' => $seller->payout_details,
        ]);

        try {
            $this->processPayoutItem($item);
            $batch->update([
                'status' => 'completed',
                'successful_count' => 1,
                'processed_at' => now(),
            ]);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'transaction_id' => $item->transaction_id,
            ];
        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'failed_count' => 1,
                'processed_at' => now(),
            ]);

            throw $e;
        }
    }
}

