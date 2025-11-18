<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact.first_name' => ['required', 'string', 'max:120'],
            'contact.last_name' => ['required', 'string', 'max:120'],
            'contact.email' => ['required', 'email:rfc,dns', 'max:255'],
            'contact.phone' => ['required', 'string', 'max:30'],

            'shipping.address' => ['required', 'string', 'max:500'],
            'shipping.city' => ['required', 'string', 'max:120'],
            'shipping.district' => ['required', 'string', 'max:120'],
            'shipping.notes' => ['nullable', 'string', 'max:500'],

            'billing' => ['nullable', 'array'],
            'billing.address' => ['nullable', 'string', 'max:500'],
            'billing.city' => ['nullable', 'string', 'max:120'],
            'billing.district' => ['nullable', 'string', 'max:120'],

            'payment.method' => ['required', 'string', 'in:khalti,esewa,card,cod'],
            'payment.status' => ['nullable', 'string', 'in:pending,paid,failed'],
            'payment.reference' => ['nullable', 'string', 'max:255'],

            'totals.subtotal' => ['required', 'integer', 'min:0'],
            'totals.discount' => ['required', 'integer', 'min:0'],
            'totals.shipping' => ['required', 'integer', 'min:0'],
            'totals.tax' => ['required', 'integer', 'min:0'],
            'totals.total' => ['required', 'integer', 'min:0'],

            'coupon' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],

            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'delivery_time' => ['nullable', 'string', 'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ];
    }
}
