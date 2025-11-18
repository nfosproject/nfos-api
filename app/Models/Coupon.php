<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'title',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'usage_count',
        'is_stackable',
        'status',
        'starts_at',
        'ends_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_stackable' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('status', 'active')
            ->where(function ($builder) use ($now) {
                $builder
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($builder) use ($now) {
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->where(function ($builder) {
                $builder
                    ->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function userUsages()
    {
        return $this->hasMany(CouponUsage::class)->where('user_id', auth()->id());
    }
}

