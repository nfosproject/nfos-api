<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'audience',
        'type',
        'title',
        'message',
        'link',
        'metadata',
        'read_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
    ];

    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($builder) use ($audience) {
            $builder->where('audience', $audience)->orWhere('audience', 'all');
        });
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    protected function isRead(): Attribute
    {
        return Attribute::get(fn () => $this->read_at !== null);
    }
}

