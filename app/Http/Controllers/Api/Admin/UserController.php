<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->string('role')->toString() ?: 'customer';
        $perPage = (int) $request->integer('per_page', 12);
        $perPage = max(1, min($perPage, 100));

        $query = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.role',
                'users.status',
                'users.created_at',
            ])
            ->when($role, fn ($builder) => $builder->where('users.role', $role))
            ->when($request->filled('status'), fn ($builder) => $builder->where('users.status', $request->input('status')));

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        // Customer metrics
        $query->selectSub(function ($sub) use ($role) {
            $column = $role === 'seller' ? 'seller_id' : 'user_id';
            $sub->from('orders')
                ->selectRaw('COUNT(*)')
                ->whereColumn("orders.{$column}", 'users.id');
        }, 'orders_count');

        $query->selectSub(function ($sub) use ($role) {
            $column = $role === 'seller' ? 'seller_id' : 'user_id';
            $sub->from('orders')
                ->selectRaw('COALESCE(SUM(grand_total), 0)')
                ->whereColumn("orders.{$column}", 'users.id');
        }, 'orders_total');

        if ($role === 'seller') {
            $query->selectSub(function ($sub) {
                $sub->from('products')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('products.user_id', 'users.id');
            }, 'products_count');
        }

        $users = $query
            ->latest('users.created_at')
            ->paginate($perPage);

        $items = $users->getCollection()
            ->map(function (User $user) use ($role) {
                $orders = (int) ($user->orders_count ?? 0);
                $total = (int) ($user->orders_total ?? 0);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                    'joined_at' => optional($user->created_at)?->toIso8601String(),
                    'orders' => $orders,
                    'total_value' => $total,
                    'products' => $role === 'seller' ? (int) ($user->products_count ?? 0) : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'currentPage' => $users->currentPage(),
                    'perPage' => $users->perPage(),
                    'total' => $users->total(),
                    'lastPage' => $users->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be deleted from this interface.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User removed successfully.',
        ]);
    }

    protected function validateUser(Request $request, ?User $user = null): array
    {
        $id = $user?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['customer', 'seller', 'admin'])],
            'status' => ['required', Rule::in(['active', 'pending', 'suspended', 'inactive'])],
        ]);
    }

    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'joined_at' => optional($user->created_at)?->toIso8601String(),
        ];
    }
}
