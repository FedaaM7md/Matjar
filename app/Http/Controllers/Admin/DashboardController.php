<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SALES_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_SHIPPED,
        Order::STATUS_DELIVERED,
    ];

    public function __invoke(): View
    {
        $monthStart = Carbon::now()->startOfMonth();

        $revenueMonth = (int) Order::query()
            ->whereIn('status', self::SALES_STATUSES)
            ->where('placed_at', '>=', $monthStart)
            ->sum('total_cents');

        $ordersToday = Order::query()->whereDate('placed_at', Carbon::today())->count();
        $pendingOrders = Order::query()->where('status', Order::STATUS_PENDING)->count();
        $lowStockCount = ProductVariant::query()->lowStock()->count();
        $customersCount = User::query()->whereHas('role', fn ($query) => $query->where('slug', 'customer'))->count();

        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = Order::query()->withCount('items')->latest('id')->limit(6)->get();

        $lowStock = ProductVariant::query()
            ->lowStock()
            ->with('product')
            ->orderByRaw('(stock - reserved) asc')
            ->limit(6)
            ->get();

        $start = Carbon::today()->subDays(13);
        $rawSales = Order::query()
            ->whereIn('status', self::SALES_STATUSES)
            ->where('placed_at', '>=', $start->copy()->startOfDay())
            ->selectRaw('date(placed_at) as day, sum(total_cents) as revenue, count(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $salesSeries = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $start->copy()->addDays($i);
            $salesSeries[] = [
                'date' => $date,
                'label' => $date->format('m-d'),
                'revenue' => (int) ($rawSales[$date->toDateString()]->revenue ?? 0),
                'orders' => (int) ($rawSales[$date->toDateString()]->orders ?? 0),
            ];
        }

        $maxRevenue = max(1, (int) collect($salesSeries)->max('revenue'));

        return view('admin.dashboard', [
            'revenueMonth' => $revenueMonth,
            'ordersToday' => $ordersToday,
            'pendingOrders' => $pendingOrders,
            'lowStockCount' => $lowStockCount,
            'customersCount' => $customersCount,
            'statusCounts' => $statusCounts,
            'recentOrders' => $recentOrders,
            'lowStock' => $lowStock,
            'salesSeries' => $salesSeries,
            'maxRevenue' => $maxRevenue,
        ]);
    }
}
