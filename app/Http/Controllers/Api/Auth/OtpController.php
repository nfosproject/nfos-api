<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    /**
     * Send OTP to phone number
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[+]?[0-9]{10,15}$/'],
            'purpose' => ['sometimes', 'string', 'in:login,signup,reset'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $purpose = $validated['purpose'] ?? 'login';

        // Clean up expired OTPs for this phone
        Otp::where('phone', $phone)
            ->where('expires_at', '<', now())
            ->delete();

        // Check rate limiting (max 3 OTPs per phone per 15 minutes)
        $recentOtpCount = Otp::where('phone', $phone)
            ->where('created_at', '>', now()->subMinutes(15))
            ->count();

        if ($recentOtpCount >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Too many OTP requests. Please try again after 15 minutes.',
            ], 429);
        }

        // Generate 6-digit OTP
        $code = str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create OTP record (expires in 10 minutes)
        $otp = Otp::create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ]);

        // TODO: Send OTP via SMS service (Twilio, Nexmo, etc.)
        // For now, we'll log it (remove in production)
        Log::info('OTP generated', [
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
        ]);

        // In development, return the OTP code for testing
        if (app()->environment('local')) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'otp' => $code, // Remove in production
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your phone',
        ]);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[+]?[0-9]{10,15}$/'],
            'code' => ['required', 'string', 'size:6'],
            'purpose' => ['sometimes', 'string', 'in:login,signup,reset'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $code = $validated['code'];
        $purpose = $validated['purpose'] ?? 'login';

        // Find valid OTP
        $otp = Otp::where('phone', $phone)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired OTP.'],
            ]);
        }

        // Mark OTP as verified
        $otp->markAsVerified();

        // Invalidate other OTPs for this phone and purpose
        Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('id', '!=', $otp->id)
            ->where('verified', false)
            ->update(['verified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'verified' => true,
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
}
