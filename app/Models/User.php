<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'referral_code',
        'referred_by',
        'payout_method',
        'payout_details',
        'payout_verified',
        'payout_threshold',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'payout_details' => 'array',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function points()
    {
        return $this->hasMany(UserPoint::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function balance()
    {
        return $this->hasOne(SellerBalance::class, 'seller_id');
    }

    public function earnings()
    {
        return $this->hasMany(SellerEarning::class, 'seller_id');
    }

    public function payoutBatchItems()
    {
        return $this->hasMany(PayoutBatchItem::class, 'seller_id');
    }

    public function payoutTransactions()
    {
        return $this->hasMany(PayoutTransaction::class, 'seller_id');
    }

    /**
     * Generate a unique referral code for the user
     */
    public function generateReferralCode(): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        // Clean name to get first 3 letters (remove spaces, special chars)
        $namePart = preg_replace('/[^A-Za-z]/', '', $this->name);
        if (strlen($namePart) < 3) {
            $namePart = str_pad($namePart, 3, 'X');
        }
        $namePart = strtoupper(substr($namePart, 0, 3));

        do {
            $code = $namePart . rand(1000, 9999);
        } while (self::where('referral_code', $code)->exists());

        $this->referral_code = $code;
        $this->save();
        
        return $code;
    }

    /**
     * Ensure user has a referral code
     */
    public function ensureReferralCode(): string
    {
        if (!$this->referral_code) {
            return $this->generateReferralCode();
        }
        return $this->referral_code;
    }

    /**
     * Boot method to auto-generate referral code
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            if (!$user->referral_code) {
                $user->generateReferralCode();
            }
        });
    }
}
