<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayoutBatchItem extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payout_batch_id',
        'seller_id',
        'amount',
        'status',
        'payout_method',
        'transaction_id',
        'error_message',
        'payout_details',
        'processed_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'processed_at' => 'datetime',
    ];

    public function payoutBatch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(PayoutTransaction::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(SellerEarning::class);
    }
}
