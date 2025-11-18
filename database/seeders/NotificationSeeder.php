<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $records = [
            [
                'id' => (string) Str::uuid(),
                'audience' => 'customer',
                'type' => 'order_status',
                'title' => 'Order confirmed',
                'message' => 'Your order #ORD-9823 has been confirmed and is moving to fulfilment.',
                'link' => '/orders',
                'metadata' => ['order_id' => 'ORD-9823'],
                'created_at' => $now->copy()->subMinutes(20),
            ],
            [
                'id' => (string) Str::uuid(),
                'audience' => 'customer',
                'type' => 'marketing',
                'title' => 'Holiday mega sale',
                'message' => 'Take 25% off winter essentials this weekend only. Use code WINTER25 at checkout.',
                'link' => '/store',
                'metadata' => ['coupon' => 'WINTER25'],
                'created_at' => $now->copy()->subHours(3),
            ],
            [
                'id' => (string) Str::uuid(),
                'audience' => 'admin',
                'type' => 'low_stock',
                'title' => 'Low stock alert',
                'message' => '“Classic Heritage Blazer” (Style District) is below the safety threshold with 12 units remaining.',
                'link' => '/dashboard/products',
                'metadata' => ['product_id' => 'placeholder-product-id'],
                'created_at' => $now->copy()->subMinutes(5),
            ],
            [
                'id' => (string) Str::uuid(),
                'audience' => 'admin',
                'type' => 'product_approval',
                'title' => 'Product approval requested',
                'message' => 'Urban Vibe submitted “Rugged Terrain Boots” for approval.',
                'link' => '/dashboard/products',
                'metadata' => ['seller' => 'Urban Vibe'],
                'created_at' => $now->copy()->subMinutes(30),
            ],
            [
                'id' => (string) Str::uuid(),
                'audience' => 'admin',
                'type' => 'order_status',
                'title' => 'High value order received',
                'message' => 'Order ORD-10543 placed by Sarah Williams for रू 54,000 is awaiting fulfilment.',
                'link' => '/dashboard/orders',
                'metadata' => ['order_id' => 'ORD-10543'],
                'created_at' => $now->copy()->subHour(),
            ],
            [
                'id' => (string) Str::uuid(),
                'audience' => 'customer',
                'type' => 'order_status',
                'title' => 'Order shipped',
                'message' => 'Your order #ORD-9823 has shipped. Track your parcel for live updates.',
                'link' => '/track-order',
                'metadata' => ['order_id' => 'ORD-9823'],
                'created_at' => $now->copy()->subDay(),
                'read_at' => $now->copy()->subHours(4),
            ],
        ];

        foreach ($records as $record) {
            Notification::query()->updateOrCreate(
                ['id' => $record['id']],
                $record + ['updated_at' => $record['updated_at'] ?? ($record['created_at'] ?? $now)]
            );
        }
    }
}

