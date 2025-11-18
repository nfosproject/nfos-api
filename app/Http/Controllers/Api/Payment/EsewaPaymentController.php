<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Notifications\OrderNotificationService;
use App\Services\Payment\EsewaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EsewaPaymentController extends Controller
{
    public function __construct(
        private EsewaPaymentService $esewaService,
        private OrderNotificationService $orderNotifications
    ) {
    }

    public function initiate(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
        ]);

        $order = Order::with('items.product')
            ->where('id', $validated['order_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $amount = (int) ($order->subtotal ?? 0);
        $taxAmount = (int) ($order->tax_total ?? 0);
        $shippingAmount = (int) ($order->shipping_total ?? 0);
        $totalAmount = (int) $order->grand_total;

        // Build product name from order items
        $productNames = [];
        foreach ($order->items as $item) {
            $productName = $item->snapshot['title'] ?? $item->snapshot['name'] ?? null;
            
            // Fallback to product relationship if snapshot doesn't have name
            if (!$productName && $item->product) {
                $productName = $item->product->title;
            }
            
            // Fallback to generic name
            if (!$productName) {
                $productName = 'Product';
            }
            
            $quantity = $item->quantity ?? 1;
            $unitPrice = $item->unit_price ?? 0;
            
            // Format: "Product Name (Qty: 2, Price: Rs 1000)"
            $productNames[] = sprintf(
                '%s (Qty: %d, Price: Rs %s)',
                $productName,
                $quantity,
                number_format($unitPrice, 0, '.', ',')
            );
        }
        
        // Combine all product names, truncate if too long (eSewa has limits)
        $productNameString = implode('; ', $productNames);
        if (strlen($productNameString) > 255) {
            $productNameString = substr($productNameString, 0, 252) . '...';
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:3001');
        // Redirect to order confirmation page like COD payment
        $successUrl = "{$frontendUrl}/order-confirmation?order={$order->order_number}&payment=esewa";
        $failureUrl = "{$frontendUrl}/payment-failure?order={$order->order_number}&transaction_uuid={$order->id}&payment=esewa";

        $formData = $this->esewaService->generatePaymentFormData([
            'transaction_uuid' => $order->id,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'product_service_charge' => 0,
            'product_delivery_charge' => $shippingAmount,
            'product_name' => $productNameString,
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
        ]);

        $metadata = $order->metadata ?? [];
        $metadata['esewa_payment_initiated'] = now()->toIso8601String();
        $metadata['esewa_transaction_uuid'] = $formData['transaction_uuid'];
        $order->metadata = $metadata;
        $order->save();

        return response()->json([
            'success' => true,
            'data' => [
                'form_data' => $formData,
                'form_action' => $this->esewaService->getApiUrl(),
                'order_number' => $order->order_number,
            ],
        ]);
    }

    public function callback(Request $request)
    {
        $data = $request->all();

        Log::info('eSewa callback received', $data);

        if (!$this->esewaService->verifySignature($data)) {
            Log::warning('eSewa callback signature verification failed', $data);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $transactionUuid = $data['transaction_uuid'] ?? null;
        $status = $data['status'] ?? 'FAILURE';

        if (!$transactionUuid) {
            return response()->json(['error' => 'Transaction UUID missing'], 400);
        }

        $order = Order::find($transactionUuid);

        if (!$order) {
            Log::warning('eSewa callback: Order not found', ['transaction_uuid' => $transactionUuid]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        $originalStatus = $order->status;

        DB::transaction(function () use ($order, $data, $status) {
            $metadata = $order->metadata ?? [];
            $metadata['esewa_callback_received'] = now()->toIso8601String();
            $metadata['esewa_response'] = $data;

            if ($status === 'COMPLETE' || $status === 'SUCCESS') {
                $order->status = 'paid';
                $metadata['payment_status'] = 'paid';
                $metadata['payment_method'] = 'esewa';
                $metadata['payment_reference'] = $data['transaction_code'] ?? $data['transaction_uuid'] ?? null;
            } else {
                // Mark order as cancelled when payment fails - order was never successfully placed
                $order->status = 'cancelled';
                $metadata['payment_status'] = 'failed';
                $metadata['payment_method'] = 'esewa';
                $metadata['payment_error'] = $data['failure_message'] ?? 'Payment failed';
                $metadata['cancelled_at'] = now()->toIso8601String();
                $metadata['cancellation_reason'] = 'Payment failed';
            }

            $order->metadata = $metadata;
            $order->save();
        });

        // Send notifications if status changed
        if ($order->status !== $originalStatus) {
            $this->orderNotifications->sendOrderStatusChanged($order, $originalStatus);
        }

        $frontendUrl = config('app.frontend_url', 'http://localhost:3001');

        if ($status === 'COMPLETE' || $status === 'SUCCESS') {
            // Redirect to order confirmation page like COD payment
            return redirect("{$frontendUrl}/order-confirmation?order={$order->order_number}&payment=esewa");
        }

        // Redirect to payment failure page (styled like order confirmation)
        return redirect("{$frontendUrl}/payment-failure?order={$order->order_number}&transaction_uuid={$transactionUuid}&payment=esewa");
    }
}

