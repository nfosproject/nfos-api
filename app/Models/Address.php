<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'label',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'notes',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set this address as default and unset others for the user
     */
    public function setAsDefault(): void
    {
        $this->user->addresses()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Get formatted address string
     */
    public function getFormattedAddressAttribute(): string
    {
        return "{$this->address}, {$this->city}, {$this->district}";
    }
}
