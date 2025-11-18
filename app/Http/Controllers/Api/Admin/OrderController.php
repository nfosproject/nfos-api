<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Notifications\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const ORDER_STATUSES = ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];
    private const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    public function __construct(private OrderNotificationService $orderNotifications)
    {
    }

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->integer('per_page', 12), 100));

        $query = Order::query()
            ->with(['buyer:id,name,email', 'seller:id,name,email'])
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('buyer', fn ($buyer) => $buyer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->string('payment_status')->toString()) {
            $query->where('metadata->payment_status', $paymentStatus);
        }

        if ($customerId = $request->string('customer_id')->toString()) {
            $query->where('user_id', $customerId);
        }

        if ($sellerId = $request->string('seller_id')->toString()) {
            $query->where('seller_id', $sellerId);
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $orders->getCollection()
            ->map(fn (Order $order) => $this->formatOrder($order))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'currentPage' => $orders->currentPage(),
                    'perPage' => $orders->perPage(),
                    'total' => $orders->total(),
                    'lastPage' => $orders->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'seller_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
            'payment_status' => ['required', Rule::in(self::PAYMENT_STATUSES)],
            'subtotal' => ['nullable', 'integer', 'min:0'],
            'tax_total' => ['nullable', 'integer', 'min:0'],
            'shipping_total' => ['nullable', 'integer', 'min:0'],
            'discount_total' => ['nullable', 'integer', 'min:0'],
            'grand_total' => ['nullable', 'integer', 'min:0'],
            'placed_at' => ['nullable', 'date'],
            'order_number' => ['nullable', 'string', 'max:50', 'unique:orders,order_number'],
        ]);

        $subtotal = $validated['subtotal'] ?? 0;
        $tax = $validated['tax_total'] ?? 0;
        $shipping = $validated['shipping_total'] ?? 0;
        $discount = $validated['discount_total'] ?? 0;
        $grandTotal = $validated['grand_total'] ?? max(0, $subtotal + $tax + $shipping - $discount);

        $order = Order::create([
            'user_id' => $validated['user_id'],
            'seller_id' => $validated['seller_id'] ?? null,
            'order_number' => $validated['order_number'] ?? $this->generateOrderNumber(),
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'shipping_total' => $shipping,
            'discount_total' => $discount,
            'grand_total' => $grandTotal,
            'metadata' => [
                'payment_status' => $validated['payment_status'],
            ],
            'placed_at' => $validated['placed_at'] ?? now(),
        ]);

        $order->load(['buyer:id,name,email', 'seller:id,name,email'])->loadCount('items')->loadSum('items as total_quantity', 'quantity');
        $this->orderNotifications->sendOrderCreated($order);

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order),
        ], 201);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(self::ORDER_STATUSES)],
            'payment_status' => ['sometimes', Rule::in(self::PAYMENT_STATUSES)],
            'seller_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'subtotal' => ['sometimes', 'integer', 'min:0'],
            'tax_total' => ['sometimes', 'integer', 'min:0'],
            'shipping_total' => ['sometimes', 'integer', 'min:0'],
            'discount_total' => ['sometimes', 'integer', 'min:0'],
            'grand_total' => ['sometimes', 'integer', 'min:0'],
            'placed_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $originalStatus = $order->status;

        if (array_key_exists('status', $validated)) {
            $order->status = $validated['status'];
        }

        if (array_key_exists('payment_status', $validated)) {
            $metadata = $order->metadata ?? [];
            $metadata['payment_status'] = $validated['payment_status'];
            $order->metadata = $metadata;
        }

        if (array_key_exists('seller_id', $validated)) {
            $order->seller_id = $validated['seller_id'];
        }

        foreach (['subtotal', 'tax_total', 'shipping_total', 'discount_total', 'grand_total'] as $field) {
            if (array_key_exists($field, $validated)) {
                $order->{$field} = (int) $validated[$field];
            }
        }

        if (array_key_exists('placed_at', $validated)) {
            $order->placed_at = $validated['placed_at'];
        }

        $order->save();
        $order->refresh();
        $order->load(['buyer:id,name,email', 'seller:id,name,email'])->loadCount('items')->loadSum('items as total_quantity', 'quantity');

        if ($order->status !== $originalStatus) {
            $this->orderNotifications->sendOrderStatusChanged($order, $originalStatus);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order),
        ]);
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Order removed successfully.',
        ]);
    }

    protected function formatOrder(Order $order): array
    {
        $metadata = $order->metadata ?? [];

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $metadata['payment_status'] ?? 'pending',
            'placed_at' => optional($order->placed_at ?? $order->created_at)?->toIso8601String(),
            'subtotal' => (int) $order->subtotal,
            'tax_total' => (int) $order->tax_total,
            'shipping_total' => (int) $order->shipping_total,
            'discount_total' => (int) $order->discount_total,
            'total_amount' => (int) $order->grand_total,
            'items_count' => (int) ($order->items_count ?? 0),
            'total_quantity' => (int) ($order->total_quantity ?? 0),
            'customer' => [
                'id' => $order->buyer?->id,
                'name' => $order->buyer?->name,
                'email' => $order->buyer?->email,
            ],
            'seller' => [
                'id' => $order->seller?->id,
                'name' => $order->seller?->name,
            ],
        ];
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . Str::upper(Str::random(8));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}

