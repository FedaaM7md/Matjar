<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const SALES_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_SHIPPED,
        Order::STATUS_DELIVERED,
    ];

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $orders = Order::query()
            ->whereIn('status', self::SALES_STATUSES)
            ->whereBetween('placed_at', [$from, $to]);

        $orderCount = (clone $orders)->count();
        $revenue = (int) (clone $orders)->sum('total_cents');

        $units = (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::SALES_STATUSES)
            ->whereBetween('orders.placed_at', [$from, $to])
            ->sum('order_items.quantity');

        $rawDaily = (clone $orders)
            ->selectRaw('date(placed_at) as day, sum(total_cents) as revenue, count(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $daily = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($to)) {
            $key = $cursor->toDateString();
            $daily[] = [
                'date' => $cursor->copy(),
                'label' => $cursor->format('m-d'),
                'revenue' => (int) ($rawDaily[$key]->revenue ?? 0),
                'orders' => (int) ($rawDaily[$key]->orders ?? 0),
            ];
            $cursor->addDay();
        }

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::SALES_STATUSES)
            ->whereBetween('orders.placed_at', [$from, $to])
            ->select(
                'order_items.product_name_ar',
                'order_items.product_name_en',
                'order_items.sku',
                DB::raw('sum(order_items.quantity) as units'),
                DB::raw('sum(order_items.line_total_cents) as revenue'),
            )
            ->groupBy('order_items.product_name_ar', 'order_items.product_name_en', 'order_items.sku')
            ->orderByDesc('units')
            ->limit(10)
            ->get();

        $byGovernorate = (clone $orders)
            ->select('governorate_name', DB::raw('count(*) as orders'), DB::raw('sum(total_cents) as revenue'))
            ->groupBy('governorate_name')
            ->orderByDesc('revenue')
            ->get();

        $byPayment = (clone $orders)
            ->select('payment_method', DB::raw('count(*) as orders'), DB::raw('sum(total_cents) as revenue'))
            ->groupBy('payment_method')
            ->get();

        $maxRevenue = max(1, (int) collect($daily)->max('revenue'));

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'orderCount' => $orderCount,
            'revenue' => $revenue,
            'units' => $units,
            'averageOrder' => $orderCount > 0 ? (int) round($revenue / $orderCount) : 0,
            'daily' => $daily,
            'topProducts' => $topProducts,
            'byGovernorate' => $byGovernorate,
            'byPayment' => $byPayment,
            'maxRevenue' => $maxRevenue,
            'revenueFormatted' => Money::withCurrency($revenue),
        ]);
    }

    /** Stream a formula-injection-safe CSV of orders in the selected range. */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $orders = Order::query()
            ->whereBetween('placed_at', [$from, $to])
            ->orderBy('placed_at')
            ->cursor();

        $filename = 'qoton-orders-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم الطلب', 'التاريخ', 'العميل', 'المحافظة', 'الحالة', 'طريقة الدفع', 'الإجمالي (ج.م)']);

            foreach ($orders as $order) {
                fputcsv($out, array_map(fn ($cell) => $this->safeCell($cell), [
                    $order->number,
                    $order->placed_at?->format('Y-m-d H:i'),
                    $order->customer_name,
                    $order->governorate_name,
                    $order->status_label,
                    __('orders.methods.'.$order->payment_method),
                    Money::toMajor($order->total_cents),
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::today()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366)->startOfDay();
        }

        return [$from, $to];
    }

    private function safeCell(mixed $value): string
    {
        $cell = (string) $value;

        if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$cell;
        }

        return $cell;
    }
}
