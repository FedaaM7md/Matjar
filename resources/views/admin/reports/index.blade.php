@extends('layouts.admin')

@section('title', __('admin.reports.title'))
@section('page_title', __('admin.reports.title'))
@section('page_subtitle', __('admin.reports.subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-wrap items-end gap-2">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="from" class="label text-xs">{{ __('admin.reports.from') }}</label>
                    <input id="from" name="from" type="date" value="{{ $from->format('Y-m-d') }}" class="input h-10 w-40 py-1.5" dir="ltr">
                </div>
                <div>
                    <label for="to" class="label text-xs">{{ __('admin.reports.to') }}</label>
                    <input id="to" name="to" type="date" value="{{ $to->format('Y-m-d') }}" class="input h-10 w-40 py-1.5" dir="ltr">
                </div>
                <button type="submit" class="btn-primary h-10">{{ __('admin.reports.apply') }}</button>
            </form>
            <div class="flex gap-1.5">
                @foreach ([7 => '7', 30 => '30', 90 => '90'] as $days => $label)
                    <a href="{{ route('admin.reports.index', ['from' => now()->subDays($days - 1)->toDateString(), 'to' => now()->toDateString()]) }}"
                       class="btn-outline btn-sm h-10">{{ __('admin.reports.days', ['count' => $label]) }}</a>
                @endforeach
            </div>
            <a href="{{ route('admin.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="btn-outline btn-sm h-10">{{ __('admin.reports.export') }}</a>
        </div>
        <p class="text-xs text-slate-500">{{ $from->format('Y-m-d') }} → {{ $to->format('Y-m-d') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.reports.revenue') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-brand-950" dir="ltr">{{ $revenueFormatted }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.reports.orders') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $orderCount }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.reports.units') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $units }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.reports.average_order') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-accent-700" dir="ltr">{{ \App\Support\Money::withCurrency($averageOrder) }}</p>
        </div>
    </div>

    <div class="mt-5 card p-5">
        <h2 class="font-bold text-slate-900">{{ __('admin.reports.daily_sales') }}</h2>
        <p class="text-xs text-slate-500">{{ __('admin.reports.daily_hint') }}</p>

        <div class="mt-6 overflow-x-auto">
            <div class="flex h-40 min-w-[480px] items-end gap-1" role="img" aria-label="{{ __('admin.reports.daily_sales') }}">
                @foreach ($daily as $point)
                    <div class="group flex h-full flex-1 flex-col items-center justify-end" title="{{ $point['date']->format('Y-m-d') }} · {{ \App\Support\Money::withCurrency($point['revenue']) }} · {{ $point['orders'] }}">
                        <div class="w-full rounded-t-sm bg-accent-500/70 transition group-hover:bg-accent-700"
                             style="height: {{ max(2, (int) round($point['revenue'] / $maxRevenue * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex min-w-[480px] justify-between text-[10px] text-slate-400">
                <span>{{ $daily[0]['label'] ?? '' }}</span>
                <span>{{ end($daily)['label'] ?? '' }}</span>
            </div>
        </div>
        @if ($revenue === 0)
            <p class="mt-2 text-center text-xs text-slate-400">{{ __('admin.reports.no_data') }}</p>
        @endif
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('admin.reports.top_products') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('orders.product') }}</th>
                            <th class="text-center">{{ __('admin.reports.units_sold') }}</th>
                            <th class="text-end">{{ __('admin.reports.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr>
                                <td>
                                    <p class="text-sm font-semibold text-slate-800">{{ $locale === 'ar' ? $product->product_name_ar : $product->product_name_en }}</p>
                                    <p class="font-mono text-[11px] text-slate-400" dir="ltr">{{ $product->sku }}</p>
                                </td>
                                <td class="text-center font-bold">{{ $product->units }}</td>
                                <td class="text-end text-sm font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency((int) $product->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('admin.reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('admin.reports.by_governorate') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th>{{ __('orders.governorate') }}</th>
                            <th class="text-center">{{ __('admin.reports.orders') }}</th>
                            <th class="text-end">{{ __('admin.reports.revenue') }}</th>
                            <th class="text-end">{{ __('admin.reports.share') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byGovernorate as $row)
                            <tr>
                                <td class="text-sm font-semibold text-slate-800">{{ $row->governorate_name }}</td>
                                <td class="text-center">{{ $row->orders }}</td>
                                <td class="text-end text-sm font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency((int) $row->revenue) }}</td>
                                <td class="text-end text-xs text-slate-500">{{ $revenue > 0 ? number_format($row->revenue / $revenue * 100, 1) : '0.0' }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('admin.reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5 card p-5">
        <h2 class="font-bold text-slate-900">{{ __('admin.reports.by_payment') }}</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            @forelse ($byPayment as $row)
                <div class="flex items-center justify-between rounded-none border border-slate-200 p-3 text-sm">
                    <span class="font-semibold text-slate-700">{{ __('orders.methods.'.$row->payment_method) }}</span>
                    <span class="text-slate-500">{{ $row->orders }} · <span class="font-bold text-slate-800" dir="ltr">{{ \App\Support\Money::withCurrency((int) $row->revenue) }}</span></span>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('admin.reports.no_data') }}</p>
            @endforelse
        </div>
    </div>

    <p class="mt-5 text-[11px] leading-relaxed text-slate-500">{{ __('admin.reports.definition_note') }}</p>
@endsection

