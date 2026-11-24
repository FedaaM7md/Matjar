@extends('layouts.admin')

@section('title', __('admin.orders.show_title', ['number' => $order->number]))
@section('page_title', __('admin.orders.show_title', ['number' => $order->number]))
@section('page_subtitle', $order->customer_name)

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('admin.orders.back') }}</a>
        <div class="flex flex-wrap items-center gap-2">
            <x-order-status :status="$order->status" />
            <x-payment-status :status="$order->payment_status" />
            <a href="{{ $invoiceUrl }}" target="_blank" class="btn-outline btn-sm">{{ __('orders.print_invoice') }}</a>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="space-y-5">
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ __('orders.items') }}</h2>
                        <p class="text-xs text-slate-500">{{ __('admin.orders.placed_at') }}: {{ $order->placed_at?->format('Y-m-d H:i') }}</p>
                    </div>
                    <p class="font-mono text-sm font-extrabold text-brand-900" dir="ltr">{{ $order->number }}</p>
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
                                        <p class="text-sm font-semibold text-slate-900">{{ $item->name }}</p>
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
                <h2 class="font-bold text-slate-900">{{ __('orders.timeline') }}</h2>
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

        <div class="space-y-5">
            @can('orders.manage')
                @if (! empty($allowedTransitions))
                    <div class="card p-5">
                        <h2 class="font-bold text-slate-900">{{ __('admin.orders.transition_title') }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.orders.transition_hint') }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($allowedTransitions as $nextStatus)
                                @continue($nextStatus === 'cancelled')
                                <form method="POST" action="{{ route('admin.orders.transition', $order) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $nextStatus }}">
                                    <button type="submit" class="{{ $nextStatus === 'returned' ? 'btn-outline' : 'btn-primary' }} btn-sm">{{ __('admin.orders.actions.'.$nextStatus) }}</button>
                                </form>
                            @endforeach
                        </div>

                        @if (in_array('cancelled', $allowedTransitions, true))
                            <form method="POST" action="{{ route('admin.orders.transition', $order) }}" class="mt-4 space-y-2 border-t border-stone-100 pt-4" x-data="confirmForm" data-message="{{ __('admin.orders.confirm_cancel') }}" @submit="onConfirm($event)">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <label for="reason" class="label text-xs">{{ __('admin.orders.cancel_reason') }}</label>
                                <input id="reason" name="reason" maxlength="190" class="input h-10 py-1.5 text-sm" placeholder="{{ __('admin.orders.cancel_reason_placeholder') }}">
                                <button type="submit" class="btn-danger btn-sm w-full">{{ __('admin.orders.actions.cancelled') }}</button>
                                <p class="text-[11px] text-slate-500">{{ __('admin.orders.stock_note') }}</p>
                            </form>
                        @endif

                        @error('status')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            @endcan

            <div class="card p-5">
                <h2 class="font-bold text-slate-900">{{ __('orders.customer_details') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div><dt class="text-[11px] text-slate-500">{{ __('fields.name') }}</dt><dd class="font-semibold text-slate-800">{{ $order->customer_name }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">{{ __('orders.phone') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $order->customer_phone }}</dd></div>
                    @if ($order->customer_email)<div><dt class="text-[11px] text-slate-500">{{ __('orders.email') }}</dt><dd class="break-all font-semibold text-slate-800" dir="ltr">{{ $order->customer_email }}</dd></div>@endif
                    @if ($order->user)
                        <div><a href="{{ route('admin.customers.show', $order->user) }}" class="link text-xs">{{ __('admin.orders.open_customer') }}</a></div>
                    @endif
                </dl>
            </div>

            <div class="card p-5">
                <h2 class="font-bold text-slate-900">{{ __('orders.shipping_address') }}</h2>
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
                <h2 class="font-bold text-slate-900">{{ __('orders.payment') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('orders.method') }}</dt><dd class="font-semibold text-slate-800">{{ __('orders.methods.'.$order->payment_method) }}</dd></div>
                    <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">{{ __('orders.payment_status') }}</dt><dd><x-payment-status :status="$order->payment_status" /></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('admin.orders.stock_state') }}</dt><dd class="font-semibold text-slate-800">{{ __('admin.orders.stock_states.'.$order->stock_state) }}</dd></div>
                    @if ($order->payment)
                        <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('orders.reference') }}</dt><dd class="font-mono text-[11px] text-slate-600" dir="ltr">{{ $order->payment->reference }}</dd></div>
                    @endif
                </dl>
            </div>

            @if ($order->cancellation_reason)
                <div class="rounded-none border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                    <p class="font-bold">{{ __('orders.cancelled_reason') }}</p>
                    <p class="mt-1 text-xs">{{ $order->cancellation_reason }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
