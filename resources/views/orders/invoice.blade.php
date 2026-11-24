@extends('layouts.print')

@section('title', __('orders.invoice').' '.$order->number)

@section('actions')
    <a href="{{ url('/') }}" class="btn-ghost btn-sm">{{ __('common.home') }}</a>
@endsection

@section('content')
    @php
        $storeName = \App\Models\Setting::get('store_name_'.$locale, __('common.app_name'));
    @endphp
    <header class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-brand-900 pb-5">
        <div class="flex items-center gap-3">
            <x-logo class="h-12 w-12" />
            <div>
                <p class="text-lg font-extrabold text-brand-950">{{ $storeName }}</p>
                <p class="text-[11px] font-semibold text-accent-700">{{ __('common.app_tagline') }}</p>
                <p class="mt-1 text-xs text-slate-500" dir="ltr">{{ \App\Models\Setting::get('support_phone', '01000000000') }}</p>
            </div>
        </div>
        <div class="text-end">
            <p class="text-xl font-extrabold text-slate-900">{{ __('orders.invoice') }}</p>
            <p class="mt-1 font-mono text-sm font-bold text-brand-900" dir="ltr">{{ $order->number }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $order->placed_at?->format('Y-m-d H:i') }}</p>
            <div class="mt-2 flex flex-wrap justify-end gap-1.5">
                <span class="badge-info">{{ $order->status_label }}</span>
                <span class="{{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ $order->payment_status_label }}</span>
            </div>
        </div>
    </header>

    <section class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
        <div>
            <h2 class="text-[11px] font-bold text-slate-500">{{ __('orders.customer_details') }}</h2>
            <p class="mt-1.5 font-bold text-slate-900">{{ $order->customer_name }}</p>
            <p class="text-slate-600" dir="ltr">{{ $order->customer_phone }}</p>
            @if ($order->customer_email)<p class="break-all text-slate-600" dir="ltr">{{ $order->customer_email }}</p>@endif
        </div>
        <div>
            <h2 class="text-[11px] font-bold text-slate-500">{{ __('orders.shipping_address') }}</h2>
            <p class="mt-1.5 leading-relaxed text-slate-700">{{ $order->address }}</p>
            <p class="font-semibold text-slate-900">{{ $order->governorate_name }}</p>
        </div>
    </section>

    <table class="mt-6 w-full border-collapse text-sm">
        <thead>
            <tr class="border-y border-slate-200 bg-slate-50 text-start">
                <th class="px-2 py-2 text-start text-[11px] font-bold text-slate-500">{{ __('orders.product') }}</th>
                <th class="px-2 py-2 text-center text-[11px] font-bold text-slate-500">{{ __('orders.qty') }}</th>
                <th class="px-2 py-2 text-end text-[11px] font-bold text-slate-500">{{ __('orders.unit_price') }}</th>
                <th class="px-2 py-2 text-end text-[11px] font-bold text-slate-500">{{ __('orders.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr class="border-b border-slate-100">
                    <td class="px-2 py-2.5">
                        <p class="font-semibold text-slate-800">{{ $item->name }}</p>
                        <p class="text-[10px] text-slate-400">{{ $item->variant_label }} · {{ $item->sku }}</p>
                    </td>
                    <td class="px-2 py-2.5 text-center">{{ $item->quantity }}</td>
                    <td class="px-2 py-2.5 text-end" dir="ltr">{{ \App\Support\Money::withCurrency($item->unit_price_cents) }}</td>
                    <td class="px-2 py-2.5 text-end font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency($item->line_total_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <section class="mt-5 flex justify-end">
        <dl class="w-full max-w-xs space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('orders.subtotal') }}</dt><dd class="font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency($order->subtotal_cents) }}</dd></div>
            @if ($order->discount_cents > 0)
                <div class="flex justify-between text-emerald-700"><dt>{{ __('orders.discount') }} ({{ $order->coupon_code }})</dt><dd class="font-semibold" dir="ltr">− {{ \App\Support\Money::withCurrency($order->discount_cents) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('orders.shipping') }}</dt><dd class="font-semibold" dir="ltr">{{ $order->shipping_cents === 0 ? __('checkout.free') : \App\Support\Money::withCurrency($order->shipping_cents) }}</dd></div>
            <div class="flex justify-between border-t-2 border-brand-900 pt-2 text-base font-extrabold"><dt>{{ __('orders.total') }}</dt><dd class="text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</dd></div>
            <div class="flex justify-between text-xs"><dt class="text-slate-500">{{ __('orders.method') }}</dt><dd class="font-semibold">{{ __('orders.methods.'.$order->payment_method) }}</dd></div>
        </dl>
    </section>

    @if ($order->notes)
        <section class="mt-5 rounded-none border border-slate-200 p-3 text-xs text-slate-600">
            <p class="font-bold text-slate-700">{{ __('fields.notes') }}</p>
            <p class="mt-1">{{ $order->notes }}</p>
        </section>
    @endif

    <footer class="mt-8 border-t border-slate-200 pt-4 text-center text-[10px] leading-relaxed text-slate-400">
        <p>{{ __('orders.demo_note') }}</p>
        <p class="mt-1">{{ $storeName }} · {{ $order->number }}</p>
    </footer>
@endsection
