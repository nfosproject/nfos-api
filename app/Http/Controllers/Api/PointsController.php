<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPoint;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PointsController extends Controller
{
    const DEFAULT_EARN_RATE = 0.01; // 1 point per NPR 100
    const DEFAULT_CONVERSION_RATE = 1; // 1 point = 1 NPR
    const EXPIRY_MONTHS = 12;

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get all transactions
        $transactions = UserPoint::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($point) => $this->formatPoint($point));

        // Calculate current points (earned - redeemed - expired)
        $currentPoints = $this->calculateCurrentPoints($user->id);
        
        // Calculate lifetime points (all earned points including referral and review)
        $lifetimePoints = UserPoint::where('user_id', $user->id)
            ->whereIn('type', ['earn', 'referral', 'review'])
            ->sum('points');

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $currentPoints,
                'lifetime_points' => $lifetimePoints,
                'transactions' => $transactions,
                'conversion_rate' => self::DEFAULT_CONVERSION_RATE,
                'earn_rate' => self::DEFAULT_EARN_RATE,
            ],
        ]);
    }

    public function redeem(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'points' => 'required|integer|min:100',
            'description' => 'nullable|string|max:255',
        ]);

        $points = $validated['points'];
        
        // Normalize to multiples of 100
        $normalized = floor($points / 100) * 100;
        
        if ($normalized <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Points must be in multiples of 100',
            ], 422);
        }

        // Check if user has enough points
        $currentPoints = $this->calculateCurrentPoints($user->id);
        
        if ($normalized > $currentPoints) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough points available',
            ], 422);
        }

        // Create redemption transaction
        $point = UserPoint::create([
            'user_id' => $user->id,
            'type' => 'redeem',
            'points' => $normalized,
            'description' => $validated['description'] ?? "Redeemed {$normalized} points",
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPoint($point),
            'current_points' => $this->calculateCurrentPoints($user->id),
        ]);
    }

    public function earn(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'amount_npr' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        // Check if points already earned for this order
        $existing = UserPoint::where('user_id', $user->id)
            ->where('order_id', $validated['order_id'])
            ->where('type', 'earn')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Points already earned for this order',
            ], 422);
        }

        // Calculate points
        $points = floor($validated['amount_npr'] * self::DEFAULT_EARN_RATE);
        
        if ($points <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No points to earn for this amount',
            ], 422);
        }

        // Calculate expiry date
        $expiresAt = now()->addMonths(self::EXPIRY_MONTHS);

        // Create earn transaction
        $point = UserPoint::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'points' => $points,
            'description' => $validated['description'] ?? "Earned from order {$validated['order_id']}",
            'order_id' => $validated['order_id'],
            'expires_at' => $expiresAt,
            'metadata' => ['amount_npr' => $validated['amount_npr']],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPoint($point),
            'current_points' => $this->calculateCurrentPoints($user->id),
        ]);
    }

    public function adjust(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'points' => 'required|integer',
            'description' => 'required|string|max:255',
            'admin_note' => 'nullable|string|max:255',
        ]);

        $point = UserPoint::create([
            'user_id' => $user->id,
            'type' => 'adjust',
            'points' => $validated['points'],
            'description' => $validated['description'],
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPoint($point),
            'current_points' => $this->calculateCurrentPoints($user->id),
        ]);
    }

    public function runExpiryCheck(Request $request)
    {
        $user = $request->user();
        
        // Get expired earn transactions that haven't been offset
        $expiredEarns = UserPoint::where('user_id', $user->id)
            ->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $totalExpired = 0;
        $expiredIds = [];

        foreach ($expiredEarns as $earn) {
            // Check if this earn has been fully redeemed/expired
            $redeemed = UserPoint::where('user_id', $user->id)
                ->whereIn('type', ['redeem', 'expire'])
                ->where('created_at', '>', $earn->created_at)
                ->sum('points');

            $alreadyExpired = UserPoint::where('user_id', $user->id)
                ->where('type', 'expire')
                ->where('created_at', '>', $earn->created_at)
                ->sum('points');

            $remaining = $earn->points - $redeemed - $alreadyExpired;
            
            if ($remaining > 0) {
                $totalExpired += $remaining;
                $expiredIds[] = $earn->id;
            }
        }

        if ($totalExpired > 0) {
            UserPoint::create([
                'user_id' => $user->id,
                'type' => 'expire',
                'points' => $totalExpired,
                'description' => "Expired points ({$totalExpired})",
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'expired_points' => $totalExpired,
                'current_points' => $this->calculateCurrentPoints($user->id),
            ],
        ]);
    }

    public function earnReferralPoints(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'referral_code' => 'required|string|exists:users,referral_code',
        ]);

        // Check if user is trying to use their own code
        if ($user->referral_code === $validated['referral_code']) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot use your own referral code',
            ], 422);
        }

        // Check if user was already referred
        if ($user->referred_by) {
            return response()->json([
                'success' => false,
                'message' => 'You have already used a referral code',
            ], 422);
        }

        // Find the referrer
        $referrer = User::where('referral_code', $validated['referral_code'])->first();
        
        if (!$referrer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid referral code',
            ], 422);
        }

        // Check if referral already exists
        $existingReferral = Referral::where('referred_id', $user->id)->first();
        if ($existingReferral) {
            return response()->json([
                'success' => false,
                'message' => 'Referral already processed',
            ], 422);
        }

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'referral_code' => $validated['referral_code'],
        ]);

        // Update user's referred_by
        $user->update(['referred_by' => $referrer->id]);

        // Award points to referrer (500 points)
        $referrerPoints = 500;
        UserPoint::create([
            'user_id' => $referrer->id,
            'type' => 'referral',
            'points' => $referrerPoints,
            'description' => "Referral bonus for referring {$user->name}",
            'metadata' => ['referred_user_id' => $user->id, 'referral_id' => $referral->id],
            'expires_at' => now()->addMonths(self::EXPIRY_MONTHS),
        ]);

        // Award points to referred user (200 points)
        $referredPoints = 200;
        UserPoint::create([
            'user_id' => $user->id,
            'type' => 'referral',
            'points' => $referredPoints,
            'description' => "Welcome bonus for using referral code",
            'metadata' => ['referrer_id' => $referrer->id, 'referral_id' => $referral->id],
            'expires_at' => now()->addMonths(self::EXPIRY_MONTHS),
        ]);

        $referral->update([
            'points_awarded' => true,
            'points_awarded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Referral points awarded successfully',
            'data' => [
                'points_earned' => $referredPoints,
                'current_points' => $this->calculateCurrentPoints($user->id),
            ],
        ]);
    }

    public function earnReviewPoints(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'review_id' => 'required|uuid|exists:product_reviews,id',
            'product_id' => 'required|uuid|exists:products,id',
        ]);

        // Check if points already earned for this review
        $existing = UserPoint::where('user_id', $user->id)
            ->where('type', 'review')
            ->where('metadata->review_id', $validated['review_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Points already earned for this review',
            ], 422);
        }

        // Award points for review (50 points)
        $points = 50;
        $point = UserPoint::create([
            'user_id' => $user->id,
            'type' => 'review',
            'points' => $points,
            'description' => "Points for product review",
            'metadata' => [
                'review_id' => $validated['review_id'],
                'product_id' => $validated['product_id'],
            ],
            'expires_at' => now()->addMonths(self::EXPIRY_MONTHS),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPoint($point),
            'current_points' => $this->calculateCurrentPoints($user->id),
        ]);
    }

    protected function calculateCurrentPoints(string $userId): int
    {
        // Include earn, referral, and review points
        $earned = UserPoint::where('user_id', $userId)
            ->whereIn('type', ['earn', 'referral', 'review'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $redeemed = UserPoint::where('user_id', $userId)
            ->where('type', 'redeem')
            ->sum('points');

        $expired = UserPoint::where('user_id', $userId)
            ->where('type', 'expire')
            ->sum('points');

        $adjusted = UserPoint::where('user_id', $userId)
            ->where('type', 'adjust')
            ->sum('points');

        return max(0, $earned - $redeemed - $expired + $adjusted);
    }

    protected function formatPoint(UserPoint $point): array
    {
        return [
            'id' => $point->id,
            'type' => $point->type,
            'points' => $point->points,
            'description' => $point->description,
            'created_at' => $point->created_at->toIso8601String(),
            'expires_at' => $point->expires_at?->toIso8601String(),
            'order_id' => $point->order_id,
            'admin_note' => $point->admin_note,
            'metadata' => $point->metadata,
        ];
    }
}
