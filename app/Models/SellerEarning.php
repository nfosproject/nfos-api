<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerEarning extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'seller_id',
        'order_id',
        'amount',
        'platform_fee',
        'net_amount',
        'status',
        'eligible_at',
        'paid_at',
        'payout_batch_item_id',
    ];

    protected $casts = [
        'eligible_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payoutBatchItem(): BelongsTo
    {
        return $this->belongsTo(PayoutBatchItem::class);
    }
}
