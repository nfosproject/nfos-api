<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutTransaction extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payout_batch_item_id',
        'seller_id',
        'amount',
        'transaction_id',
        'status',
        'payout_method',
        'payout_details',
        'error_message',
        'response_data',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'response_data' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function payoutBatchItem(): BelongsTo
    {
        return $this->belongsTo(PayoutBatchItem::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
