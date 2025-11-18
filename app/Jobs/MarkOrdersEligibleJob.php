<?php

namespace App\Jobs;

use App\Services\Payout\PayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MarkOrdersEligibleJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(PayoutService $payoutService): void
    {
        Log::info('Starting mark orders eligible job');
        
        try {
            $count = $payoutService->markOrdersEligible();
            Log::info('Mark orders eligible job completed', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Mark orders eligible job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
