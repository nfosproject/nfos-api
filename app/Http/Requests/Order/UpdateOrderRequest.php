<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'])],
            'payment.status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'payment.reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
