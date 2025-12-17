<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Register with phone and OTP
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[+]?[0-9]{10,15}$/', 'unique:users,phone'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $code = $validated['otp'];

        // Verify OTP
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('purpose', 'signup')
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        // Mark OTP as verified
        $otp->markAsVerified();

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'email' => null, // Email is optional now
            'password' => Hash::make(uniqid()), // Random password since we use OTP
            'role' => 'customer',
            'phone_verified_at' => now(),
        ]);

        // Ensure referral code exists
        $user->ensureReferralCode();
        $user->refresh();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Login with phone/OTP or email/password
     */
    public function login(Request $request)
    {
        // Check if it's phone/OTP or email/password login
        if ($request->has('phone') && $request->has('otp')) {
            // Phone/OTP login
            $validated = $request->validate([
                'phone' => ['required', 'string', 'regex:/^[+]?[0-9]{10,15}$/'],
                'otp' => ['required', 'string', 'size:6'],
            ]);

            $phone = $this->normalizePhone($validated['phone']);
            $code = $validated['otp'];

            // Verify OTP
            $otp = Otp::where('phone', $phone)
                ->where('code', $code)
                ->where('purpose', 'login')
                ->where('verified', false)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if (!$otp) {
                throw ValidationException::withMessages([
                    'otp' => ['Invalid or expired OTP.'],
                ]);
            }

            // Mark OTP as verified
            $otp->markAsVerified();

            // Find user
            $user = User::where('phone', $phone)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'phone' => ['No account found with this phone number. Please sign up first.'],
                ]);
            }

            // Mark phone as verified
            if (!$user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        } else {
            // Email/password login
            $validated = $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
        }

        // Ensure referral code exists
        $user->ensureReferralCode();
        $user->refresh();

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Normalize phone number format
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If it doesn't start with +, assume it's a local number
        if (!str_starts_with($phone, '+')) {
            // Remove leading zeros
            $phone = ltrim($phone, '0');
        }

        return $phone;
    }

    public function me(Request $request)
    {
        $user = $request->user();
        // Ensure referral code exists
        $user->ensureReferralCode();
        $user->refresh();
        
        return new UserResource($user);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
