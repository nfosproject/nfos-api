<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Coupon::query()->active();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        $coupons = $query->orderBy('ends_at')->get();

        // Filter by user usage limits if user is authenticated
        if ($user) {
            $coupons = $coupons->filter(function (Coupon $coupon) use ($user) {
                // If coupon has per-user limit, check user's usage
                if ($coupon->usage_limit_per_user !== null) {
                    $userUsageCount = CouponUsage::where('user_id', $user->id)
                        ->where('coupon_id', $coupon->id)
                        ->count();
                    
                    return $userUsageCount < $coupon->usage_limit_per_user;
                }
                return true;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $coupons->map(fn (Coupon $coupon) => $this->formatCoupon($coupon, $user))->values(),
        ]);
    }

    public function validateCode(Request $request)
    {
        $code = $request->string('code')->toString();
        $user = $request->user();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is required.',
            ], 422);
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper($code))
            ->active()
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is invalid or expired.',
            ], 404);
        }

        // Check user usage limit if user is authenticated
        if ($user && $coupon->usage_limit_per_user !== null) {
            $userUsageCount = CouponUsage::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->count();
            
            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the usage limit for this coupon.',
                ], 403);
            }
        }

        $formatted = $this->formatCoupon($coupon, $user);

        $formatted['summary'] = $this->buildSummary($coupon, $request->input('order_total'));

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    protected function formatCoupon(Coupon $coupon, $user = null): array
    {
        $userUsageCount = 0;
        if ($user) {
            $userUsageCount = CouponUsage::where('user_id', $user->id)
                ->where('coupon_id', $coupon->id)
                ->count();
        }

        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'type' => $coupon->type,
            'value' => (int) $coupon->value,
            'min_order_amount' => (int) $coupon->min_order_amount,
            'max_discount_amount' => $coupon->max_discount_amount !== null ? (int) $coupon->max_discount_amount : null,
            'usage_limit' => $coupon->usage_limit !== null ? (int) $coupon->usage_limit : null,
            'usage_count' => (int) $coupon->usage_count,
            'usage_limit_per_user' => $coupon->usage_limit_per_user !== null ? (int) $coupon->usage_limit_per_user : null,
            'user_usage_count' => $userUsageCount,
            'is_stackable' => (bool) $coupon->is_stackable,
            'starts_at' => optional($coupon->starts_at)?->toIso8601String(),
            'ends_at' => optional($coupon->ends_at)?->toIso8601String(),
        ];
    }

    protected function buildSummary(Coupon $coupon, $orderTotal = null): array
    {
        $summary = [
            'eligible' => true,
            'messages' => [],
            'estimated_discount' => 0,
        ];

        $orderValue = is_numeric($orderTotal) ? (int) $orderTotal : null;

        if ($coupon->min_order_amount > 0) {
            if ($orderValue === null || $orderValue < $coupon->min_order_amount) {
                $summary['eligible'] = false;
                $summary['messages'][] = sprintf('Requires minimum order of रु %s', number_format($coupon->min_order_amount));
            }
        }

        if ($summary['eligible'] && $orderValue !== null) {
            $discount = 0;
            if ($coupon->type === 'percentage') {
                $discount = (int) round($orderValue * ($coupon->value / 100));
                if ($coupon->max_discount_amount) {
                    $discount = min($discount, $coupon->max_discount_amount);
                }
            } elseif ($coupon->type === 'fixed_amount') {
                $discount = $coupon->value;
            } elseif ($coupon->type === 'free_shipping') {
                $summary['messages'][] = 'Shipping will be free at checkout.';
            }

            if ($discount > $orderValue) {
                $discount = $orderValue;
            }

            $summary['estimated_discount'] = $discount;
            if ($discount > 0) {
                $summary['messages'][] = sprintf('Estimated discount: रु %s', number_format($discount));
            }
        }

        return $summary;
    }
}

