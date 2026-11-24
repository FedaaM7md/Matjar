@extends('layouts.app')

@section('title', __('orders.title').' '.$order->number)

@php
    $statusClasses = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'shipped' => 'badge-info',
        'delivered' => 'badge-success',
        'cancelled' => 'badge-danger',
        'returned' => 'badge-neutral',
    ];
    $paymentClasses = [
        'unpaid' => 'badge-warning',
        'pending' => 'badge-warning',
        'paid' => 'badge-success',
        'failed' => 'badge-danger',
        'cancelled' => 'badge-neutral',
    ];
@endphp

@section('content')
    <div class="container-page max-w-5xl py-8 lg:py-12">
        @if ($confirmation)
            <div class="rounded-none border {{ $order->status === 'cancelled' ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }} p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $order->status === 'cancelled' ? 'bg-red-600' : 'bg-emerald-600' }} text-white">
                        @if ($order->status === 'cancelled')
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
                        @else
                            <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L4 10.4a1 1 0 0 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-extrabold text-slate-900">{{ $order->status === 'cancelled' ? __('orders.title') : __('orders.thanks_title') }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ __('orders.order_number') }}: <span class="font-mono font-bold" dir="ltr">{{ $order->number }}</span></p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('orders.thanks_guest') }}</p>
                    </div>
                    <span class="{{ $statusClasses[$order->status] ?? 'badge-neutral' }}">{{ $order->status_label }}</span>
                </div>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('orders.title') }}</p>
                <h2 class="mt-1 text-2xl font-extrabold text-brand-950" dir="ltr">{{ $order->number }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('orders.placed_at') }}: {{ $order->placed_at?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="{{ $statusClasses[$order->status] ?? 'badge-neutral' }}">{{ $order->status_label }}</span>
                <a href="{{ $invoiceUrl }}" class="btn-outline btn-sm">{{ __('orders.print_invoice') }}</a>
                @if ($canPayOnline)
                    <a href="{{ route('payments.show', $order->payment->reference) }}" class="btn-accent btn-sm">{{ __('orders.pay_now') }}</a>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-5">
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-100 p-5">
                        <h3 class="font-bold text-slate-900">{{ __('orders.items') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th>{{ __('orders.product') }}</th>
                                    <th class="text-center">{{ __('orders.qty') }}</th>
                                    <th class="text-end">{{ __('orders.unit_price') }}</th>
                                    <th class="text-end">{{ __('orders.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $item->variant_label }} · <span dir="ltr">{{ $item->sku }}</span></p>
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end text-sm" dir="ltr">{{ \App\Support\Money::withCurrency($item->unit_price_cents) }}</td>
                                        <td class="text-end text-sm font-bold" dir="ltr">{{ \App\Support\Money::withCurrency($item->line_total_cents) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <dl class="space-y-2 border-t border-slate-100 p-5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('orders.subtotal') }}</dt><dd class="font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency($order->subtotal_cents) }}</dd></div>
                        @if ($order->discount_cents > 0)
                            <div class="flex justify-between text-emerald-700"><dt>{{ __('orders.discount') }} <span class="font-mono text-[11px]">{{ $order->coupon_code }}</span></dt><dd class="font-semibold" dir="ltr">− {{ \App\Support\Money::withCurrency($order->discount_cents) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('orders.shipping') }}</dt><dd class="font-semibold" dir="ltr">{{ $order->shipping_cents === 0 ? __('checkout.free') : \App\Support\Money::withCurrency($order->shipping_cents) }}</dd></div>
                        <div class="flex justify-between border-t border-dashed border-slate-200 pt-2 text-base font-extrabold"><dt>{{ __('orders.total') }}</dt><dd class="text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</dd></div>
                    </dl>
                </div>

                <div class="card p-5">
                    <h3 class="font-bold text-slate-900">{{ __('orders.timeline') }}</h3>
                    <ol class="mt-4 space-y-4">
                        @forelse ($order->histories as $history)
                            @php
                                $noteKey = 'orders.notes.'.$history->note;
                                $noteLabel = $history->note && \Illuminate\Support\Facades\Lang::has($noteKey) ? __($noteKey) : $history->note;
                            @endphp
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-700"></span>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $history->status_label }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $history->created_at?->format('Y-m-d H:i') }}@if ($history->user) · {{ $history->user->name }}@endif</p>
                                    @if ($noteLabel)<p class="mt-0.5 text-xs text-slate-500">{{ $noteLabel }}</p>@endif
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">{{ __('orders.no_history') }}</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="card p-5">
                    <h3 class="font-bold text-slate-900">{{ __('orders.customer_details') }}</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="text-[11px] text-slate-500">{{ __('fields.name') }}</dt><dd class="font-semibold text-slate-800">{{ $order->customer_name }}</dd></div>
                        <div><dt class="text-[11px] text-slate-500">{{ __('orders.phone') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $order->customer_phone }}</dd></div>
                        @if ($order->customer_email)
                            <div><dt class="text-[11px] text-slate-500">{{ __('orders.email') }}</dt><dd class="break-all font-semibold text-slate-800" dir="ltr">{{ $order->customer_email }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="card p-5">
                    <h3 class="font-bold text-slate-900">{{ __('orders.shipping_address') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $order->address }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->governorate_name }}</p>
                    @if ($order->governorate?->zone)
                        <p class="mt-2 text-xs text-slate-500">{{ __('orders.shipping_days', ['min' => $order->governorate->zone->delivery_days_min, 'max' => $order->governorate->zone->delivery_days_max]) }}</p>
                    @endif
                    @if ($order->notes)
                        <p class="mt-3 rounded-none bg-slate-50 p-2.5 text-xs text-slate-600">{{ $order->notes }}</p>
                    @endif
                </div>

                <div class="card p-5">
                    <h3 class="font-bold text-slate-900">{{ __('orders.payment') }}</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('orders.method') }}</dt><dd class="font-semibold text-slate-800">{{ __('orders.methods.'.$order->payment_method) }}</dd></div>
                        <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">{{ __('orders.payment_status') }}</dt><dd><span class="{{ $paymentClasses[$order->payment_status] ?? 'badge-neutral' }}">{{ $order->payment_status_label }}</span></dd></div>
                        @if ($order->payment)
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('orders.reference') }}</dt><dd class="font-mono text-[11px] text-slate-600" dir="ltr">{{ $order->payment->reference }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-none bg-amber-50 p-4 text-[11px] leading-relaxed text-amber-900">{{ __('orders.demo_note') }}</div>

                <div class="flex flex-col gap-2">
                    <a href="{{ route('shop.index') }}" class="btn-outline">{{ __('orders.continue_shopping') }}</a>
                    @auth
                        <a href="{{ url('/account/orders') }}" class="text-center text-sm font-semibold text-slate-500 hover:text-brand-800">{{ __('orders.back_to_orders') }}</a>
                    @endauth
                </div>
            </aside>
        </div>
    </div>
@endsection
