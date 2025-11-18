<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutBatch extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'batch_number',
        'status',
        'total_amount',
        'seller_count',
        'successful_count',
        'failed_count',
        'processed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayoutBatchItem::class);
    }
}
