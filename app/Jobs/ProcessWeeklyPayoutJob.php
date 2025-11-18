<?php

namespace App\Jobs;

use App\Services\Payout\PayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWeeklyPayoutJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $threshold = 100000
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PayoutService $payoutService): void
    {
        Log::info('Starting weekly payout job', ['threshold' => $this->threshold]);
        
        try {
            $result = $payoutService->processWeeklyPayout($this->threshold);
            Log::info('Weekly payout job completed', $result);
        } catch (\Exception $e) {
            Log::error('Weekly payout job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
