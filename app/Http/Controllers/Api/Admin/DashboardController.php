<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview()
    {
        $totalSales = (int) Order::sum('grand_total');
        $totalOrders = (int) Order::count();

        $sellerSales = Order::query()
            ->join('users as sellers', 'orders.seller_id', '=', 'sellers.id')
            ->selectRaw('orders.seller_id, SUM(orders.grand_total) as revenue, sellers.name as seller_name')
            ->groupBy('orders.seller_id', 'sellers.name')
            ->orderByDesc('revenue')
            ->get();

        $activeSellers = $sellerSales->count();
        $topSellers = $sellerSales
            ->take(5)
            ->map(static fn ($row) => [
                'seller' => $row->seller_name ?? 'Unknown seller',
                'sales' => (int) $row->revenue,
            ])
            ->values();

        $activeCustomers = (int) Order::distinct('user_id')->count('user_id');

        $windowStart = now()->copy()->startOfMonth()->subMonths(5);

        $rawRevenue = Order::query()
            ->selectRaw("DATE_FORMAT(COALESCE(placed_at, created_at), '%Y-%m') as month_key")
            ->selectRaw('SUM(grand_total) as revenue')
            ->selectRaw('COUNT(*) as order_count')
            ->where('created_at', '>=', $windowStart)
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $revenueByMonth = $rawRevenue
            ->map(static function ($row) {
                $month = $row->month_key;

                try {
                    $label = Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
                } catch (\Exception $exception) {
                    $label = $month;
                }

                return [
                    'month' => $label,
                    'revenue' => (int) $row->revenue,
                    'orders' => (int) $row->order_count,
                ];
            })
            ->values();

        $topCategories = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorised') as category")
            ->selectRaw('COUNT(*) as sales')
            ->groupBy('category')
            ->orderByDesc('sales')
            ->limit(5)
            ->get()
            ->map(static fn ($row) => [
                'category' => $row->category,
                'sales' => (int) $row->sales,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'totalSales' => $totalSales,
                'totalOrders' => $totalOrders,
                'activeSellers' => $activeSellers,
                'activeCustomers' => $activeCustomers,
                'revenueByMonth' => $revenueByMonth,
                'topCategories' => $topCategories,
                'topSellers' => $topSellers,
            ],
        ]);
    }
}

