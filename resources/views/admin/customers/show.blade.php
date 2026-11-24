@extends('layouts.admin')

@section('title', $customer->name)
@section('page_title', $customer->name)
@section('page_subtitle', __('admin.customers.show_subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.customers.index') }}" class="text-sm font-semibold text-slate-500 hover:text-brand-800">&larr; {{ __('admin.customers.back') }}</a>
        <span class="badge-info">{{ $customer->role?->name }}</span>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.customers.orders') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-brand-950">{{ $ordersTotal }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.customers.spent') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-700" dir="ltr">{{ \App\Support\Money::withCurrency($paidTotal) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('admin.customers.joined') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $customer->created_at?->format('Y-m-d') }}</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <div class="card p-5">
            <h2 class="font-bold text-slate-900">{{ __('account.details') }}</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('fields.name') }}</dt><dd class="font-semibold text-slate-800">{{ $customer->name }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('fields.email') }}</dt><dd class="break-all font-semibold text-slate-800" dir="ltr">{{ $customer->email }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('fields.phone') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $customer->phone ?? '—' }}</dd></div>
                <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ __('common.status') }}</dt><dd><span class="{{ $customer->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $customer->is_active ? __('common.active') : __('common.inactive') }}</span></dd></div>
            </dl>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-bold text-slate-900">{{ __('admin.customers.order_history') }}</h2>
            </div>
            @if ($orders->isEmpty())
                <p class="p-8 text-center text-sm text-slate-500">{{ __('admin.customers.no_orders') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>{{ __('admin.orders.number') }}</th>
                                <th>{{ __('account.date') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th class="text-end">{{ __('admin.orders.total') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="font-mono text-xs font-bold text-brand-900" dir="ltr">{{ $order->number }}</td>
                                    <td class="whitespace-nowrap text-xs text-slate-500">{{ $order->placed_at?->format('Y-m-d') }}</td>
                                    <td><x-order-status :status="$order->status" /></td>
                                    <td class="text-end text-sm font-bold" dir="ltr">{{ \App\Support\Money::withCurrency($order->total_cents) }}</td>
                                    <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="link text-xs">{{ __('admin.orders.view') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
@endsection
