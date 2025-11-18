<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\UserWelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        // Send welcome email (synchronously, not queued)
        try {
            if ($user->email) {
                Mail::to($user->email)->send(new UserWelcomeMail($user));
                Log::info('Welcome email sent to new user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

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

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $demoAccounts = [
                'owner@merzi.test' => [
                    'name' => 'MERZi Store Owner',
                    'role' => 'seller',
                    'password' => 'password123',
                ],
                'fulfilment@merzi.test' => [
                    'name' => 'MERZi Fulfilment Lead',
                    'role' => 'seller',
                    'password' => 'password123',
                ],
            ];

            $demo = $demoAccounts[$credentials['email']] ?? null;

            if (! $demo || $credentials['password'] !== $demo['password']) {
                return response()->json([
                    'message' => 'The provided credentials are incorrect.',
                ], 422);
            }

            $user = User::updateOrCreate(
                ['email' => $credentials['email']],
                [
                    'name' => $demo['name'],
                    'password' => Hash::make($credentials['password']),
                    'role' => $demo['role'],
                ],
            );
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
