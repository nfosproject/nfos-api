<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSellerSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $owner = User::updateOrCreate(
                ['email' => 'owner@merzi.test'],
                [
                    'name' => 'MERZi Store Owner',
                    'password' => Hash::make('password123'),
                    'role' => 'admin',
                ],
            );

            $fulfilment = User::updateOrCreate(
                ['email' => 'fulfilment@merzi.test'],
                [
                    'name' => 'MERZi Fulfilment Lead',
                    'password' => Hash::make('password123'),
                    'role' => 'seller',
                ],
            );

            $demoSeller = User::updateOrCreate(
                ['email' => 'seller@nfos.com'],
                [
                    'name' => 'NFOS Demo Seller',
                    'password' => Hash::make('seller123'),
                    'role' => 'seller',
                    'email_verified_at' => now(),
                ],
            );

            $demoCustomer = User::updateOrCreate(
                ['email' => 'customer@nfos.com'],
                [
                    'name' => 'NFOS Demo Shopper',
                    'password' => Hash::make('customer123'),
                    'role' => 'customer',
                    'email_verified_at' => now(),
                ],
            );

            $customers = collect([
                ['id' => Str::uuid()->toString(), 'name' => 'Maya Shrestha', 'email' => 'maya@example.com'],
                ['id' => Str::uuid()->toString(), 'name' => 'Prakash Gautam', 'email' => 'prakash@example.com'],
                ['id' => Str::uuid()->toString(), 'name' => 'Sita Bhandari', 'email' => 'sita@example.com'],
                ['id' => Str::uuid()->toString(), 'name' => 'Bibek Regmi', 'email' => 'bibek@example.com'],
            ])->map(function ($customer) {
                return User::updateOrCreate(
                    ['email' => $customer['email']],
                    [
                        'name' => $customer['name'],
                        'password' => Hash::make('password123'),
                        'role' => 'customer',
                    ],
                );
            });

            $customers = $customers
                ->prepend($demoCustomer)
                ->unique('id')
                ->values();

            $sellerUsers = collect([$owner, $fulfilment, $demoSeller])->unique('id')->values();

            $products = Product::query()->with('seller')->take(8)->get();

            if ($products->isEmpty()) {
                $products = Product::factory()
                    ->count(4)
                    ->create([
                        'user_id' => $owner->id,
                    ]);
            }

            $demoSellerProducts = Product::query()
                ->where('user_id', $demoSeller->id)
                ->take(3)
                ->get();

            if ($demoSellerProducts->count() < 3) {
                $additionalProducts = Product::factory()
                    ->count(3 - $demoSellerProducts->count())
                    ->state(function () use ($demoSeller) {
                        return [
                            'user_id' => $demoSeller->id,
                        ];
                    })
                    ->create();

                $demoSellerProducts = $demoSellerProducts->concat($additionalProducts);
            }

            $products = $products->concat($demoSellerProducts)->unique('id')->values();

            $orders = [
                [
                    'order_number' => 'ORD-9823',
                    'status' => 'processing',
                    'stage' => 'ready_to_ship',
                    'placed_at' => Carbon::now()->subMinutes(5),
                    'subtotal' => 5100000,
                    'shipping_total' => 300000,
                    'tax_total' => 400000,
                    'grand_total' => 5400000,
                ],
                [
                    'order_number' => 'ORD-9821',
                    'status' => 'processing',
                    'stage' => 'processing',
                    'placed_at' => Carbon::now()->subHours(1),
                    'subtotal' => 1720000,
                    'shipping_total' => 84000,
                    'tax_total' => 120000,
                    'grand_total' => 1924000,
                ],
                [
                    'order_number' => 'ORD-9816',
                    'status' => 'shipped',
                    'stage' => 'shipped',
                    'placed_at' => Carbon::now()->subHours(3),
                    'subtotal' => 920000,
                    'shipping_total' => 50000,
                    'tax_total' => 26000,
                    'grand_total' => 998000,
                ],
                [
                    'order_number' => 'ORD-9814',
                    'status' => 'completed',
                    'stage' => 'completed',
                    'placed_at' => Carbon::now()->subDay(),
                    'subtotal' => 2030000,
                    'shipping_total' => 120000,
                    'tax_total' => 180000,
                    'grand_total' => 2330000,
                ],
                [
                    'order_number' => 'NFOS-5001',
                    'status' => 'processing',
                    'stage' => 'processing',
                    'placed_at' => Carbon::now()->subHours(6),
                    'subtotal' => 285000,
                    'shipping_total' => 15000,
                    'tax_total' => 22000,
                    'grand_total' => 322000,
                    'payment_status' => 'paid',
                    'payment_method' => 'esewa',
                    'customer' => $demoCustomer,
                    'seller' => $demoSeller,
                    'product' => $demoSellerProducts->first(),
                    'quantity' => 2,
                    'journey' => [
                        'pending' => Carbon::now()->subDay()->toIso8601String(),
                        'processing' => Carbon::now()->subHours(12)->toIso8601String(),
                    ],
                ],
                [
                    'order_number' => 'NFOS-5002',
                    'status' => 'ready_to_ship',
                    'stage' => 'ready_to_ship',
                    'placed_at' => Carbon::now()->subHours(30),
                    'subtotal' => 468000,
                    'shipping_total' => 20000,
                    'tax_total' => 35000,
                    'grand_total' => 523000,
                    'payment_status' => 'paid',
                    'payment_method' => 'card',
                    'customer' => $demoCustomer,
                    'seller' => $demoSeller,
                    'product' => $demoSellerProducts->get(1),
                    'quantity' => 3,
                    'journey' => [
                        'pending' => Carbon::now()->subDays(2)->toIso8601String(),
                        'processing' => Carbon::now()->subHours(40)->toIso8601String(),
                        'ready_to_ship' => Carbon::now()->subHours(10)->toIso8601String(),
                    ],
                ],
            ];

            foreach ($orders as $index => $orderData) {
                $customer = $orderData['customer'] ?? $customers[$index % $customers->count()];
                $seller = $orderData['seller'] ?? $sellerUsers[$index % $sellerUsers->count()];
                $paymentStatus = $orderData['payment_status'] ?? ($orderData['status'] === 'confirmed' ? 'paid' : 'pending');

                $order = Order::updateOrCreate(
                    ['order_number' => $orderData['order_number']],
                    [
                        'user_id' => $customer->id,
                        'seller_id' => $seller->id,
                        'status' => $this->mapStatus($orderData['status']),
                        'subtotal' => $orderData['subtotal'],
                        'shipping_total' => $orderData['shipping_total'],
                        'tax_total' => $orderData['tax_total'],
                        'discount_total' => 0,
                        'grand_total' => $orderData['grand_total'],
                        'shipping_address' => [
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'phone' => '+977-9800000000',
                            'address' => '123 Demo Street',
                            'city' => 'Kathmandu',
                            'district' => 'Kathmandu',
                        ],
                        'billing_address' => [
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'phone' => '+977-9800000000',
                            'address' => '123 Billing Lane',
                            'city' => 'Kathmandu',
                            'district' => 'Kathmandu',
                        ],
                        'metadata' => [
                            'payment_status' => $paymentStatus,
                            'payment_method' => $orderData['payment_method'] ?? 'cod',
                            'current_stage' => $orderData['stage'] ?? $this->mapStatus($orderData['status']),
                            'journey' => $orderData['journey'] ?? [
                                ($orderData['stage'] ?? $this->mapStatus($orderData['status'])) => Carbon::now()->toIso8601String(),
                            ],
                        ],
                        'placed_at' => $orderData['placed_at'],
                    ],
                );

                $product = ($orderData['product'] ?? null) instanceof Product
                    ? $orderData['product']
                    : $products[$index % $products->count()];
                $quantity = (int) ($orderData['quantity'] ?? rand(1, 4));
                $quantity = max(1, $quantity);
                $unitPrice = (int) ($orderData['unit_price'] ?? max(1, floor($orderData['subtotal'] / max(1, $quantity))));

                $order->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice * $quantity,
                        'snapshot' => [
                            'name' => $product->title,
                            'vendor' => optional($product->seller)->name ?? 'MERZi Demo Seller',
                            'image' => null,
                        ],
                    ],
                );
            }
        });
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'confirmed' => 'processing',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'completed' => 'completed',
            default => 'pending',
        };
    }
}


