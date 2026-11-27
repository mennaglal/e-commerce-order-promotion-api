<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromotionCode;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_products' => Product::count(),

            'active_products' => Product::where('status', true)->count(),

            'low_stock_products' => Product::where('stock_quantity', '>', 0)
                ->where('stock_quantity', '<=', 5)
                ->count(),

            'out_of_stock_products' => Product::where('stock_quantity', 0)
                ->count(),

            'total_promotions' => PromotionCode::count(),

            'active_promotions' => PromotionCode::where('is_active', true)
                ->count(),

            'total_orders' => Order::count(),

            'today_orders' => Order::whereDate('created_at', today())
                ->count(),

            'total_customers' => User::where('is_admin', false)->count(),

            'paid_orders' => Order::where(
                'payment_status',
                Order::PAYMENT_PAID
            )->count(),

            'total_revenue' => Order::where(
                'payment_status',
                Order::PAYMENT_PAID
            )->sum('total'),
        ];

        $latestOrders = Order::with('user')
            ->latest()
            ->take(6)
            ->get();

        // Orders count for the last 6 months
        $monthlyOrders = [
            'labels' => [],
            'data' => [],
        ];

        $month = now()->startOfMonth()->subMonths(5);

        for ($i = 0; $i < 6; $i++) {
            $monthlyOrders['labels'][] = $month->format('M Y');

            $monthlyOrders['data'][] = Order::whereBetween(
                'created_at',
                [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ]
            )->count();

            $month->addMonth();
        }

        return view('admin.pages.dashboard.index', compact(
            'stats',
            'latestOrders',
            'monthlyOrders'
        ));
    }
}
