<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'seller_id',
        'order_number',
        'status',
        'subtotal',
        'tax_total',
        'shipping_total',
        'discount_total',
        'grand_total',
        'shipping_address',
        'billing_address',
        'metadata',
        'placed_at',
        'delivery_date',
        'delivery_time',
        'payout_eligible',
        'payout_eligible_at',
        'return_window_ends_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
        'placed_at' => 'datetime',
        'delivery_date' => 'date',
        'payout_eligible' => 'boolean',
        'payout_eligible_at' => 'datetime',
        'return_window_ends_at' => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function earnings()
    {
        return $this->hasMany(SellerEarning::class);
    }
}
