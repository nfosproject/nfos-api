<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Notifications\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

const PRINT_LABEL_LIMIT = 20;

class OrderController extends Controller
{
    private const SELLER_STATUSES = ['processing', 'ready_to_ship', 'shipped', 'completed', 'cancelled'];
    private const STATUS_TO_INTERNAL = [
        'processing' => 'processing',
        'ready_to_ship' => 'processing',
        'shipped' => 'shipped',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];
    private const INTERNAL_TO_DISPLAY = [
        'pending' => 'pending',
        'paid' => 'paid',
        'processing' => 'processing',
        'shipped' => 'shipped',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'refunded' => 'cancelled',
    ];

    public function __construct(private OrderNotificationService $orderNotifications)
    {
    }

    public function overview(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'seller') {
            abort(403, 'Seller access only.');
        }

        $baseQuery = Order::query()->where('seller_id', $user->id);

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $ordersToday = (clone $baseQuery)
            ->whereDate('placed_at', $today)
            ->count();

        $ordersYesterday = (clone $baseQuery)
            ->whereDate('placed_at', $yesterday)
            ->count();

        $awaitingFulfilment = (clone $baseQuery)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $readyToShip = (clone $baseQuery)
            ->whereIn('status', ['processing', 'paid'])
            ->count();

        $delayed = (clone $baseQuery)
            ->whereIn('status', ['cancelled', 'refunded'])
            ->count();

        $latestOrders = (clone $baseQuery)
            ->with(['buyer:id,name,email', 'items'])
            ->orderByDesc('placed_at')
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(function (Order $order) {
                $metadata = $order->metadata ?? [];
                $journey = $metadata['journey'] ?? [];
                $displayStatus = $metadata['current_stage'] ?? self::INTERNAL_TO_DISPLAY[$order->status] ?? $order->status;
                $status = strtolower($displayStatus);
                $bookCourierAllowed = in_array($status, ['processing', 'ready_to_ship', 'paid']);
                $printLabelAllowed = in_array($status, ['processing', 'ready_to_ship', 'shipped']) && filled($order->shipping_address);

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->buyer?->name,
                    'customer_email' => $order->buyer?->email,
                    'status' => $status,
                    'journey' => $journey,
                    'items' => $order->items->map(fn (OrderItem $item) => [
                        'name' => $item->snapshot['name'] ?? null,
                        'quantity' => (int) $item->quantity,
                    ])->values(),
                    'items_count' => (int) ($order->items_count ?? 0),
                    'total_amount' => (int) $order->grand_total,
                    'placed_at' => optional($order->placed_at ?? $order->created_at)?->toIso8601String(),
                    'shipping_address_ready' => filled($order->shipping_address),
                    'actions' => [
                        'print_label' => $printLabelAllowed,
                        'book_courier' => $bookCourierAllowed,
                    ],
                ];
            });

        $readyForLabels = $latestOrders
            ->filter(fn (array $order) => $order['actions']['print_label'])
            ->take(PRINT_LABEL_LIMIT)
            ->map(fn (array $order) => $order['id'])
            ->values();

        $completedOrders = (clone $baseQuery)->whereIn('status', ['shipped', 'completed'])->count();
        $refundedOrders = (clone $baseQuery)->whereIn('status', ['refunded'])->count();
        $totalOrders = (clone $baseQuery)->count();

        $metrics = [
            'ordersToday' => $ordersToday,
            'ordersDelta' => $ordersYesterday > 0
                ? round((($ordersToday - $ordersYesterday) / $ordersYesterday) * 100, 1)
                : null,
            'awaitingFulfilment' => $awaitingFulfilment,
            'readyToShip' => $readyToShip,
            'delayed' => $delayed,
        ];

        $performance = [
            'onTimeDispatchRate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0.0,
            'returnRate' => $totalOrders > 0 ? round(($refundedOrders / $totalOrders) * 100, 1) : 0.0,
            'completedOrders' => $completedOrders,
            'totalOrders' => $totalOrders,
            'grossSales' => (int) (clone $baseQuery)->sum('grand_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'pipeline' => $latestOrders,
                'performance' => $performance,
                'bulkActions' => [
                    'printLabelsAvailable' => $readyForLabels->isNotEmpty(),
                    'printLabelOrderIds' => $readyForLabels,
                    'printLabelLimit' => PRINT_LABEL_LIMIT,
                    'bookCourierAvailable' => $latestOrders->contains(fn (array $order) => $order['actions']['book_courier']),
                ],
            ],
        ]);
    }

    public function printLabels(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'seller') {
            abort(403, 'Seller access only.');
        }

        $validator = Validator::make($request->all(), [
            'orderIds' => ['nullable', 'array'],
            'orderIds.*' => ['uuid'],
        ]);

        $validator->validate();

        $orderIds = collect($request->input('orderIds', []))
            ->filter()
            ->unique()
            ->values();

        $query = Order::query()
            ->where('seller_id', $user->id)
            ->with(['buyer:id,name,email', 'items']);

        if ($orderIds->isNotEmpty()) {
            $query->whereIn('id', $orderIds);
        }

        $orders = $query
            ->orderByDesc('placed_at')
            ->orderByDesc('created_at')
            ->take(PRINT_LABEL_LIMIT)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => [],
                    'message' => 'No orders available for label printing.',
                ],
            ]);
        }

        $labels = $orders->map(function (Order $order) {
            $shipping = $order->shipping_address ?? [];

            return [
                'orderId' => $order->id,
                'orderNumber' => $order->order_number,
                'placedAt' => optional($order->placed_at ?? $order->created_at)?->toIso8601String(),
                'customer' => [
                    'name' => $order->buyer?->name,
                    'email' => $order->buyer?->email,
                ],
                'shippingAddress' => [
                    'name' => $shipping['name'] ?? $order->buyer?->name,
                    'phone' => $shipping['phone'] ?? null,
                    'email' => $shipping['email'] ?? $order->buyer?->email,
                    'address' => $shipping['address'] ?? null,
                    'city' => $shipping['city'] ?? null,
                    'district' => $shipping['district'] ?? null,
                ],
                'totals' => [
                    'subtotal' => (int) $order->subtotal,
                    'shipping' => (int) $order->shipping_total,
                    'tax' => (int) $order->tax_total,
                    'grandTotal' => (int) $order->grand_total,
                ],
                'items' => $order->items()->get()->map(fn (OrderItem $item) => [
                    'name' => $item->snapshot['name'] ?? 'Item',
                    'quantity' => (int) $item->quantity,
                    'unitPrice' => (int) $item->unit_price,
                    'lineTotal' => (int) $item->line_total,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'seller' || $order->seller_id !== $user->id) {
            abort(403, 'You are not allowed to update this order.');
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', self::SELLER_STATUSES)],
        ]);

        $displayStatus = strtolower($data['status']);
        $internalStatus = self::STATUS_TO_INTERNAL[$displayStatus] ?? $order->status;

        $originalStatus = $order->status;
        $order->status = $internalStatus;
        $metadata = $order->metadata ?? [];
        $journey = $metadata['journey'] ?? [];
        $journey[$displayStatus] = now()->toIso8601String();
        $metadata['journey'] = $journey;
        $metadata['current_stage'] = $displayStatus;
        $order->metadata = $metadata;
        $order->save();

        // Send notifications if status changed
        if ($order->status !== $originalStatus) {
            $this->orderNotifications->sendOrderStatusChanged($order, $originalStatus);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'status' => $displayStatus,
            ],
        ]);
    }
}


