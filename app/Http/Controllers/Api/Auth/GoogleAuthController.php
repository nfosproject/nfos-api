<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect()
    {
        // For now, we'll use a simple approach
        // In production, use Laravel Socialite
        $clientId = env('GOOGLE_CLIENT_ID');
        $redirectUri = env('GOOGLE_REDIRECT_URI', url('/api/auth/google/callback'));
        $scope = 'openid email profile';
        
        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        $code = $request->query('code');
        
        if (!$code) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/auth?error=google_auth_failed');
        }

        try {
            // Exchange code for token
            $tokenResponse = $this->getAccessToken($code);
            
            if (!isset($tokenResponse['access_token'])) {
                throw new \Exception('Failed to get access token');
            }

            // Get user info from Google
            $userInfo = $this->getUserInfo($tokenResponse['access_token']);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                throw new \Exception('Failed to get user info');
            }

            // Find or create user
            $user = User::where('email', $userInfo['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $userInfo['name'] ?? $userInfo['email'],
                    'email' => $userInfo['email'],
                    'phone' => null,
                    'password' => Hash::make(Str::random(32)), // Random password
                    'role' => 'customer',
                    'email_verified_at' => now(),
                ]);
            } else {
                // Update email verification if not already verified
                if (!$user->email_verified_at) {
                    $user->update(['email_verified_at' => now()]);
                }
            }

            // Ensure referral code exists
            $user->ensureReferralCode();
            $user->refresh();

            // Create token
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/google/callback?token=' . $token);

        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/auth?error=google_auth_failed&message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function getAccessToken(string $code): array
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        $redirectUri = env('GOOGLE_REDIRECT_URI', url('/api/auth/google/callback'));

        $response = \Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        return $response->json();
    }

    /**
     * Get user info from Google
     */
    private function getUserInfo(string $accessToken): array
    {
        $response = \Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

        return $response->json();
    }
}

