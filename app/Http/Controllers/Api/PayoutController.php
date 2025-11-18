<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payout\PayoutBalanceResource;
use App\Http\Resources\Payout\PayoutHistoryResource;
use App\Models\PayoutBatchItem;
use App\Models\PayoutTransaction;
use App\Models\SellerBalance;
use App\Models\SellerEarning;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends Controller
{
    /**
     * Get seller balance
     */
    public function balance(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $balance = SellerBalance::firstOrCreate(
            ['seller_id' => $user->id],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_paid_out' => 0,
            ]
        );

        return new PayoutBalanceResource($balance);
    }

    /**
     * Get payout history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $items = PayoutBatchItem::where('seller_id', $user->id)
            ->with(['payoutBatch', 'transaction'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return PayoutHistoryResource::collection($items);
    }

    /**
     * Get earnings list
     */
    public function earnings(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $earnings = SellerEarning::where('seller_id', $user->id)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $earnings->map(function ($earning) {
                return [
                    'id' => $earning->id,
                    'order_id' => $earning->order_id,
                    'order_number' => $earning->order->order_number ?? null,
                    'amount' => $earning->amount,
                    'platform_fee' => $earning->platform_fee,
                    'net_amount' => $earning->net_amount,
                    'status' => $earning->status,
                    'eligible_at' => $earning->eligible_at?->toIso8601String(),
                    'paid_at' => $earning->paid_at?->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $earnings->currentPage(),
                'last_page' => $earnings->lastPage(),
                'per_page' => $earnings->perPage(),
                'total' => $earnings->total(),
            ],
        ]);
    }

    /**
     * Update payout information
     */
    public function updatePayoutInfo(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'payout_method' => ['required', 'in:bank,wallet'],
            'payout_details' => ['required', 'array'],
            'payout_threshold' => ['sometimes', 'integer', 'min:10000'], // Minimum 100 NPR
        ]);

        $user->payout_method = $data['payout_method'];
        $user->payout_details = $data['payout_details'];
        $user->payout_verified = false; // Require re-verification after update
        if (isset($data['payout_threshold'])) {
            $user->payout_threshold = $data['payout_threshold'];
        }
        $user->save();

        return response()->json([
            'message' => 'Payout information updated. Please wait for verification.',
            'data' => [
                'payout_method' => $user->payout_method,
                'payout_verified' => $user->payout_verified,
            ],
        ]);
    }
}
