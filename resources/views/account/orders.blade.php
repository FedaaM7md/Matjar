@extends('layouts.app')

@section('title', __('account.orders_title'))

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="container-page py-8">
            <nav aria-label="breadcrumb" class="text-xs font-semibold text-slate-500">
                <ol class="flex items-center gap-1.5">
                    <li><a href="{{ route('account.index') }}" class="hover:text-brand-800">{{ __('account.title') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-slate-800">{{ __('account.orders_title') }}</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-3xl font-extrabold text-brand-950">{{ __('account.orders_title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('account.orders_subtitle') }}</p>
        </div>
    </div>

    <div class="container-page py-8">
        <div class="flex flex-wrap gap-2" role="navigation" aria-label="{{ __('account.filter_label') }}">
            <a href="{{ route('account.orders') }}" class="rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $status === '' ? 'border-brand-900 bg-brand-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300' }}">{{ __('account.filter_all') }}</a>
            @foreach (\App\Models\Order::statuses() as $value => $label)
                <a href="{{ route('account.orders', ['status' => $value]) }}" class="rounded-full border px-3.5 py-1.5 text-xs font-bold transition {{ $status === $value ? 'border-brand-900 bg-brand-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if ($orders->isEmpty())
            <div class="card mt-6 px-6 py-14 text-center">
                <h2 class="text-lg font-extrabold text-slate-900">{{ __('account.no_orders_filtered') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('account.no_orders_filtered_hint') }}</p>
                <a href="{{ route('shop.index') }}" class="btn-primary mt-5">{{ __('cart.continue_shopping') }}</a>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($orders as $order)
                    <div class="card p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-mono text-sm font-extrabold text-brand-950" dir="ltr">{{ $order->number }}</p>
                                    <x-order-status :status="$order->status" />
                                    <x-payment-status :status="$order->payment_status" />
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $order->placed_at?->format('Y-m-d H:i') }} · {{ trans_choice('account.items_choice', $order->items_count, ['count' => $order->items_count]) }}
                                </p>
                            </div>
                            <div class="text-end">
                                <p class="text-base font-extrabold text-slate-900" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</p>
                                <p class="text-[11px] text-slate-500">{{ __('orders.methods.'.$order->payment_method) }}</p>
                            </div>
                            <a href="{{ route('orders.show', $order->number) }}" class="btn-outline btn-sm">{{ __('account.view') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
