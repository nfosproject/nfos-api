<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Get a specific address
     */
    public function show(Request $request, Address $address)
    {
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Create a new address
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        return DB::transaction(function () use ($user, $validated) {
            // If this is set as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $user->addresses()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Address added successfully.',
                'data' => new AddressResource($address),
            ], 201);
        });
    }

    /**
     * Update an address
     */
    public function update(Request $request, Address $address)
    {
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'required', 'string'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'district' => ['sometimes', 'required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        return DB::transaction(function () use ($address, $user, $validated) {
            // If setting as default, unset other defaults
            if (isset($validated['is_default']) && $validated['is_default']) {
                $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($validated);
            $address->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully.',
                'data' => new AddressResource($address),
            ]);
        });
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, Address $address)
    {
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    /**
     * Set an address as default
     */
    public function setDefault(Request $request, Address $address)
    {
        $user = $request->user();

        if ($address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], 404);
        }

        DB::transaction(function () use ($address, $user) {
            $user->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Default address updated successfully.',
            'data' => new AddressResource($address->fresh()),
        ]);
    }
}
