<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payout\AdminPayoutBatchResource;
use App\Http\Resources\Payout\AdminPayoutDetailResource;
use App\Http\Resources\Payout\AdminSellerPayoutResource;
use App\Models\PayoutBatch;
use App\Models\PayoutBatchItem;
use App\Models\User;
use App\Services\Payout\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutService $payoutService
    ) {
    }

    /**
     * Get sellers with payout information
     */
    public function sellers(Request $request)
    {
        $sellers = User::where('role', 'seller')
            ->with('balance')
            ->withCount(['payoutBatchItems as pending_payouts' => function ($query) {
                $query->whereIn('status', ['pending', 'processing']);
            }])
            ->get();

        return response()->json([
            'data' => AdminSellerPayoutResource::collection($sellers),
        ]);
    }

    /**
     * Get payout batch history
     */
    public function batches(Request $request)
    {
        $query = PayoutBatch::query()
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $batches = $query->paginate(20);

        return AdminPayoutBatchResource::collection($batches);
    }

    /**
     * Get payout batch details
     */
    public function batchDetails(Request $request, PayoutBatch $batch)
    {
        $batch->load(['items.seller', 'items.transaction']);

        return new AdminPayoutDetailResource($batch);
    }

    /**
     * Trigger manual payout for a seller
     */
    public function triggerManualPayout(Request $request, string $sellerId)
    {
        try {
            $result = $this->payoutService->processManualPayout($sellerId);

            return response()->json([
                'success' => true,
                'message' => 'Payout processed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export payout batch as CSV
     */
    public function exportBatch(Request $request, PayoutBatch $batch)
    {
        $batch->load(['items.seller', 'items.transaction']);

        $filename = 'payout-batch-' . $batch->batch_number . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($batch) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Batch Number',
                'Seller Name',
                'Seller Email',
                'Amount',
                'Payout Method',
                'Transaction ID',
                'Status',
                'Processed At',
                'Error Message',
            ]);

            // Data
            foreach ($batch->items as $item) {
                fputcsv($file, [
                    $batch->batch_number,
                    $item->seller->name ?? 'N/A',
                    $item->seller->email ?? 'N/A',
                    $item->amount / 100, // Convert to main currency unit
                    $item->payout_method ?? 'N/A',
                    $item->transaction_id ?? 'N/A',
                    $item->status,
                    $item->processed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                    $item->error_message ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get payout statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_paid_out' => DB::table('seller_balances')->sum('total_paid_out'),
            'total_available' => DB::table('seller_balances')->sum('available_balance'),
            'total_pending' => DB::table('seller_balances')->sum('pending_balance'),
            'total_batches' => PayoutBatch::count(),
            'successful_batches' => PayoutBatch::where('status', 'completed')->count(),
            'failed_batches' => PayoutBatch::where('status', 'failed')->count(),
            'sellers_with_balance' => DB::table('seller_balances')
                ->where('available_balance', '>', 0)
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
