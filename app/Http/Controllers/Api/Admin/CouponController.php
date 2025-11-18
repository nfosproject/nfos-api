<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    private const TYPES = ['percentage', 'fixed_amount', 'free_shipping'];
    private const STATUSES = ['draft', 'scheduled', 'active', 'expired', 'archived'];

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->integer('per_page', 12), 100));

        $query = Coupon::query();

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

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $query->orderByDesc('created_at');

        $coupons = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $coupons->getCollection()->map(fn (Coupon $coupon) => $this->formatCoupon($coupon))->values(),
                'pagination' => [
                    'currentPage' => $coupons->currentPage(),
                    'perPage' => $coupons->perPage(),
                    'total' => $coupons->total(),
                    'lastPage' => $coupons->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatCoupon($coupon),
        ], 201);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validatedData($request, $coupon);

        $coupon->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatCoupon($coupon->fresh()),
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ]);
    }

    protected function validatedData(Request $request, ?Coupon $coupon = null): array
    {
        $id = $coupon?->id;

        $validated = $request->validate([
            'code' => [$coupon ? 'sometimes' : 'required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($id)],
            'title' => [$coupon ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'type' => [$coupon ? 'sometimes' : 'required', Rule::in(self::TYPES)],
            'value' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'min_order_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_discount_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_limit_per_user' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_stackable' => ['sometimes', 'boolean'],
            'status' => [$coupon ? 'sometimes' : 'required', Rule::in(self::STATUSES)],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = array_merge($coupon?->toArray() ?? [], $validated);

        $data['code'] = Str::upper($data['code']);
        $data['value'] = (int) ($data['value'] ?? 0);
        $data['min_order_amount'] = (int) ($data['min_order_amount'] ?? 0);

        if (!array_key_exists('is_stackable', $validated)) {
            $data['is_stackable'] = $coupon?->is_stackable ?? false;
        }

        return $data;
    }

    protected function formatCoupon(Coupon $coupon): array
    {
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
            'usage_limit_per_user' => $coupon->usage_limit_per_user !== null ? (int) $coupon->usage_limit_per_user : null,
            'usage_count' => (int) $coupon->usage_count,
            'is_stackable' => (bool) $coupon->is_stackable,
            'status' => $coupon->status,
            'starts_at' => optional($coupon->starts_at)?->toIso8601String(),
            'ends_at' => optional($coupon->ends_at)?->toIso8601String(),
            'created_at' => optional($coupon->created_at)?->toIso8601String(),
            'updated_at' => optional($coupon->updated_at)?->toIso8601String(),
        ];
    }
}

