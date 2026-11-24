@extends('layouts.admin')

@section('title', __('admin.orders.title'))
@section('page_title', __('admin.orders.title'))
@section('page_subtitle', __('admin.orders.subtitle'))

@section('content')
    <div class="mb-5" x-data="{ open: false }">
        <button type="button" class="btn-outline mb-3 w-full min-h-11 gap-2 md:hidden" @click="open = !open">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
            {{ __('admin.filters') }}
        </button>
        <form method="GET" action="{{ route('admin.orders.index') }}" x-show="open" x-cloak class="flex flex-wrap items-end gap-3 md:!flex">
            <div class="min-w-0 flex-1 sm:flex-none">
                <label for="q" class="label text-xs">{{ __('common.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ __('admin.orders.search_placeholder') }}" class="input h-11 w-full sm:w-64">
            </div>
            <div>
                <label for="status" class="label text-xs">{{ __('common.status') }}</label>
                <select id="status" name="status" class="input h-11 w-40 py-1.5">
                    <option value="">{{ __('admin.orders.all_statuses') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment" class="label text-xs">{{ __('orders.payment_status') }}</label>
                <select id="payment" name="payment" class="input h-11 w-44 py-1.5">
                    <option value="">{{ __('admin.orders.all_payments') }}</option>
                    @foreach (['unpaid', 'pending', 'paid', 'failed', 'cancelled'] as $value)
                        <option value="{{ $value }}" @selected(request('payment') === $value)>{{ __('orders.payment_statuses.'.$value) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-11">{{ __('common.apply') }}</button>
            @if ($search !== '' || request()->filled('status') || request()->filled('payment'))
                <a href="{{ route('admin.orders.index') }}" class="btn-outline h-11">{{ __('common.reset') }}</a>
            @endif
        </form>
    </div>

    {{-- Mobile: stacked cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($orders as $order)
            <div class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-extrabold text-brand-900" dir="ltr">{{ $order->number }}</a>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $order->placed_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    <x-order-status :status="$order->status" />
                </div>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $order->customer_name }}</p>
                        <p class="text-[11px] text-slate-400" dir="ltr">{{ $order->customer_phone }} · {{ $order->items_count }} {{ __('admin.orders.items') }}</p>
                    </div>
                    <p class="text-sm font-bold text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</p>
                </div>
                <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <x-payment-status :status="$order->payment_status" />
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline btn-sm min-h-11">{{ __('admin.orders.view') }}</a>
                </div>
            </div>
        @empty
            <p class="card p-6 text-center text-sm text-slate-500">{{ __('admin.orders.empty') }}</p>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="table-wrap hidden md:block">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('admin.orders.number') }}</th>
                    <th>{{ __('admin.orders.customer') }}</th>
                    <th class="text-center">{{ __('admin.orders.items') }}</th>
                    <th>{{ __('orders.payment_status') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('admin.orders.total') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-extrabold text-brand-900 hover:underline" dir="ltr">{{ $order->number }}</a>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $order->placed_at?->format('Y-m-d H:i') }}</p>
                        </td>
                        <td>
                            <p class="text-sm font-semibold text-slate-800">{{ $order->customer_name }}</p>
                            <p class="text-[11px] text-slate-400" dir="ltr">{{ $order->customer_phone }}</p>
                        </td>
                        <td class="text-center text-sm">{{ $order->items_count }}</td>
                        <td><x-payment-status :status="$order->payment_status" /></td>
                        <td><x-order-status :status="$order->status" /></td>
                        <td class="text-end text-sm font-bold" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline btn-sm">{{ __('admin.orders.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('admin.orders.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
