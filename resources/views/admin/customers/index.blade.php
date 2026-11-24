@extends('layouts.admin')

@section('title', __('admin.customers.title'))
@section('page_title', __('admin.customers.title'))
@section('page_subtitle', __('admin.customers.subtitle'))

@section('content')
    <div class="mb-5" x-data="{ open: false }">
        <button type="button" class="btn-outline mb-3 w-full min-h-11 gap-2 md:hidden" @click="open = !open">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
            {{ __('admin.filters') }}
        </button>
        <form method="GET" action="{{ route('admin.customers.index') }}" x-show="open" x-cloak class="flex flex-wrap items-end gap-3 md:!flex">
            <div class="min-w-0 flex-1 sm:flex-none">
                <label for="q" class="label text-xs">{{ __('common.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ __('admin.customers.search_placeholder') }}" class="input h-11 w-full sm:w-72">
            </div>
            <button type="submit" class="btn-primary h-11">{{ __('common.apply') }}</button>
            @if ($search !== '')
                <a href="{{ route('admin.customers.index') }}" class="btn-outline h-11">{{ __('common.reset') }}</a>
            @endif
        </form>
    </div>

    {{-- Mobile: stacked cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($customers as $customer)
            <div class="card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $customer->name }}</p>
                        <p class="truncate text-xs text-slate-500" dir="ltr">{{ $customer->email }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500" dir="ltr">{{ $customer->phone ?? '—' }}</p>
                    </div>
                    <span class="badge-neutral shrink-0">{{ $customer->orders_count }} {{ __('admin.customers.orders') }}</span>
                </div>
                <div class="mt-3 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                    <div>
                        <p class="text-[11px] text-slate-400">{{ __('admin.customers.spent') }}</p>
                        <p class="text-sm font-bold text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency((int) $customer->paid_total) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ __('admin.customers.joined') }}: {{ $customer->created_at?->format('Y-m-d') }}</p>
                    </div>
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline btn-sm min-h-11">{{ __('admin.customers.view') }}</a>
                </div>
            </div>
        @empty
            <p class="card p-6 text-center text-sm text-slate-500">{{ __('admin.customers.empty') }}</p>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="table-wrap hidden md:block">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('admin.customers.customer') }}</th>
                    <th>{{ __('fields.phone') }}</th>
                    <th class="text-center">{{ __('admin.customers.orders') }}</th>
                    <th class="text-end">{{ __('admin.customers.spent') }}</th>
                    <th>{{ __('admin.customers.joined') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $customer->name }}</p>
                            <p class="text-xs text-slate-500" dir="ltr">{{ $customer->email }}</p>
                        </td>
                        <td class="font-mono text-xs text-slate-600" dir="ltr">{{ $customer->phone ?? '—' }}</td>
                        <td class="text-center font-semibold">{{ $customer->orders_count }}</td>
                        <td class="text-end text-sm font-semibold" dir="ltr">{{ \App\Support\Money::withCurrency((int) $customer->paid_total) }}</td>
                        <td class="whitespace-nowrap text-xs text-slate-500">{{ $customer->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end"><a href="{{ route('admin.customers.show', $customer) }}" class="btn-outline btn-sm">{{ __('admin.customers.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('admin.customers.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $customers->links() }}</div>
@endsection
