@extends('layouts.admin')

@section('title', __('admin.dashboard_title'))
@section('page_title', __('admin.dashboard_title'))
@section('page_subtitle', __('admin.panel_subtitle'))

@section('content')
    <div class="border-b-4 border-accent-500 bg-brand-950 p-6 text-white sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-accent-300">{{ __('admin.dashboard_eyebrow') }}</p>
                <h1 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">{{ __('admin.dashboard_greeting', ['name' => auth()->user()->name]) }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-brand-200">{{ __('admin.dashboard_intro') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('orders.view')<a href="{{ route('admin.orders.index') }}" class="rounded-none bg-white/10 px-4 py-2 text-sm font-bold ring-1 ring-white/15 hover:bg-white/15">{{ __('admin.nav_orders') }}</a>@endcan
                @can('reports.view')<a href="{{ route('admin.reports.index') }}" class="rounded-none bg-accent-600 px-4 py-2 text-sm font-bold hover:bg-accent-700">{{ __('admin.nav_reports') }}</a>@endcan
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.dashboard.revenue_month') }}</p>
            <p class="mt-1.5 text-2xl font-extrabold text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency($revenueMonth) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ __('admin.dashboard.revenue_hint') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.dashboard.orders_today') }}</p>
            <p class="mt-1.5 text-2xl font-extrabold text-slate-900">{{ $ordersToday }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ __('admin.dashboard.orders_today_hint') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.dashboard.pending_orders') }}</p>
            <p class="mt-1.5 text-2xl font-extrabold text-amber-700">{{ $pendingOrders }}</p>
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="mt-1 inline-block text-[11px] font-bold text-brand-800 hover:underline">{{ __('admin.dashboard.review_now') }}</a>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.dashboard.low_stock') }}</p>
            <p class="mt-1.5 text-2xl font-extrabold text-red-700">{{ $lowStockCount }}</p>
            <a href="{{ route('admin.inventory.index', ['low_stock' => 1]) }}" class="mt-1 inline-block text-[11px] font-bold text-brand-800 hover:underline">{{ __('admin.dashboard.open_inventory') }}</a>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="card p-5">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="font-bold text-slate-900">{{ __('admin.dashboard.sales_chart') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('admin.dashboard.sales_chart_hint') }}</p>
                </div>
                @can('reports.view')<a href="{{ route('admin.reports.index') }}" class="link text-xs">{{ __('common.view_all') }}</a>@endcan
            </div>

            <div class="mt-6 flex h-44 items-end gap-1.5" role="img" aria-label="{{ __('admin.dashboard.sales_chart') }}">
                @foreach ($salesSeries as $point)
                    <div class="group flex h-full flex-1 flex-col items-center justify-end">
                        <div class="relative w-full rounded-t-md bg-brand-200 transition group-hover:bg-brand-700"
                             style="height: {{ max(2, (int) round($point['revenue'] / $maxRevenue * 100)) }}%"
                             title="{{ $point['date']->format('Y-m-d') }} · {{ \App\Support\Money::withCurrency($point['revenue']) }}"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-slate-400">
                <span>{{ $salesSeries[0]['label'] ?? '' }}</span>
                <span>{{ $salesSeries[13]['label'] ?? '' }}</span>
            </div>
            @if (collect($salesSeries)->sum('revenue') === 0)
                <p class="mt-2 text-center text-xs text-slate-400">{{ __('admin.dashboard.no_sales') }}</p>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="font-bold text-slate-900">{{ __('admin.dashboard.status_breakdown') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach (\App\Models\Order::statuses() as $status => $label)
                    <li class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-2"><x-order-status :status="$status" /></span>
                        <span class="font-bold text-slate-800">{{ (int) ($statusCounts[$status] ?? 0) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-5 border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">{{ __('admin.dashboard.customers') }}</span>
                    <span class="font-extrabold text-brand-900">{{ $customersCount }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('admin.dashboard.recent_orders') }}</h2>
                <a href="{{ route('admin.orders.index') }}" class="link text-xs">{{ __('common.view_all') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('admin.orders.number') }}</th>
                            <th>{{ __('admin.orders.customer') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end">{{ __('admin.orders.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-bold text-brand-900 hover:underline" dir="ltr">{{ $order->number }}</a></td>
                                <td class="text-sm text-slate-700">{{ $order->customer_name }}</td>
                                <td><x-order-status :status="$order->status" /></td>
                                <td class="text-end text-sm font-bold" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('admin.orders.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('admin.dashboard.low_stock_title') }}</h2>
                <a href="{{ route('admin.inventory.index') }}" class="link text-xs">{{ __('common.view_all') }}</a>
            </div>
            @if ($lowStock->isEmpty())
                <p class="p-6 text-sm text-slate-500">{{ __('admin.dashboard.all_good') }}</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($lowStock as $variant)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $variant->product?->name_ar }}</p>
                                <p class="font-mono text-[11px] text-slate-400" dir="ltr">{{ $variant->sku }}</p>
                            </div>
                            <span class="badge-{{ $variant->available_stock <= 0 ? 'danger' : 'warning' }}">{{ __('admin.variants.available_short', ['count' => $variant->available_stock]) }}</span>
                            <a href="{{ route('admin.inventory.show', $variant) }}" class="text-xs font-bold text-brand-800 hover:underline">{{ __('inventory.manage') }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="mt-5 rounded-none border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">
        <span class="font-bold">{{ __('admin.demo_notice_title') }}:</span> {{ __('admin.demo_notice_body') }}
    </div>
@endsection

