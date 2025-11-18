<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\UserPoint;
use App\Services\Notifications\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private OrderNotificationService $orderNotifications)
    {
    }

    public function index(Request $request)
    {
        $orders = Order::with(['items.product.seller', 'items.product.images'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('placed_at')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $order = DB::transaction(function () use ($data, $user) {
            $items = collect($data['items']);
            $products = Product::with('images', 'seller')
                ->whereIn('id', $items->pluck('product_id'))
                ->get()
                ->keyBy('id');

            $sellerId = optional($products->first())->user_id;

            $order = Order::create([
                'user_id' => $user->id,
                'seller_id' => $sellerId,
                'order_number' => 'ORD-' . Str::upper(Str::random(10)),
                'status' => 'pending',
                'subtotal' => $data['totals']['subtotal'],
                'discount_total' => $data['totals']['discount'],
                'shipping_total' => $data['totals']['shipping'],
                'tax_total' => $data['totals']['tax'],
                'grand_total' => $data['totals']['total'],
                'shipping_address' => [
                    'name' => trim($data['contact']['first_name'] . ' ' . $data['contact']['last_name']),
                    'email' => $data['contact']['email'],
                    'phone' => $data['contact']['phone'],
                    'address' => $data['shipping']['address'],
                    'city' => $data['shipping']['city'],
                    'district' => $data['shipping']['district'],
                    'notes' => $data['shipping']['notes'] ?? null,
                ],
                'billing_address' => $data['billing'] ?? null,
                'metadata' => array_merge([
                    'coupon' => $data['coupon'] ?? null,
                    'payment_method' => $data['payment']['method'],
                    'payment_status' => $data['payment']['status'] ?? 'pending',
                    'payment_reference' => $data['payment']['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ], $data['metadata'] ?? []),
                'delivery_date' => isset($data['delivery_date']) ? $data['delivery_date'] : null,
                'delivery_time' => isset($data['delivery_time']) ? $data['delivery_time'] : null,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                    'snapshot' => [
                        'name' => $product?->title,
                        'title' => $product?->title,
                        'vendor' => $product?->seller?->name,
                        'image' => $product?->images->firstWhere('is_primary', true)?->url
                            ?? $product?->images->first()?->url,
                    ],
                ]);
            }

            // Track coupon usage if a coupon was applied
            if (!empty($data['coupon'])) {
                $coupon = Coupon::where('code', strtoupper($data['coupon']))->first();
                if ($coupon) {
                    // Create coupon usage record
                    CouponUsage::create([
                        'user_id' => $user->id,
                        'coupon_id' => $coupon->id,
                        'order_id' => $order->id,
                    ]);

                    // Increment coupon usage count
                    $coupon->increment('usage_count');
                }
            }

            // Earn points for completed order (only if payment is successful)
            // Points will be earned when order status changes to 'paid' or 'processing'
            // This is handled in the update method when payment status changes

            return $order;
        });

        $this->orderNotifications->sendOrderCreated($order);

        return new OrderResource($order->load('items.product.seller'));
    }

    public function show(Request $request, Order $order)
    {
        $this->ensureOwner($request, $order);

        return new OrderResource($order->load('items.product.seller', 'items.product.images'));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $this->ensureOwner($request, $order);

        $data = $request->validated();
        $originalStatus = $order->status;
        $metadata = $order->metadata ?? [];

        if (isset($data['status'])) {
            $order->status = $data['status'];
        }

        if (isset($data['payment']['status'])) {
            $metadata['payment_status'] = $data['payment']['status'];
        }

        if (isset($data['payment']['reference'])) {
            $metadata['payment_reference'] = $data['payment']['reference'];
        }

        $order->metadata = $metadata;
        $order->save();
        $order->refresh();

        // Earn points when order is paid/processing (if not already earned)
        if (($order->status === 'paid' || $order->status === 'processing') && 
            $originalStatus !== 'paid' && $originalStatus !== 'processing') {
            
            // Check if points already earned for this order
            $pointsEarned = UserPoint::where('user_id', $order->user_id)
                ->where('order_id', $order->id)
                ->where('type', 'earn')
                ->exists();

            if (!$pointsEarned && $order->grand_total > 0) {
                // Calculate points (1 point per NPR 100)
                $points = floor($order->grand_total * 0.01);
                
                if ($points > 0) {
                    $expiresAt = now()->addMonths(12);
                    
                    UserPoint::create([
                        'user_id' => $order->user_id,
                        'type' => 'earn',
                        'points' => $points,
                        'description' => "Earned from order {$order->order_number}",
                        'order_id' => $order->id,
                        'expires_at' => $expiresAt,
                        'metadata' => ['amount_npr' => $order->grand_total],
                    ]);
                }
            }
        }

        if ($order->status !== $originalStatus) {
            $this->orderNotifications->sendOrderStatusChanged($order, $originalStatus);
        }

        return new OrderResource($order->load('items.product.seller', 'items.product.images'));
    }

    protected function ensureOwner(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'You are not allowed to modify this order.');
        }
    }
}
