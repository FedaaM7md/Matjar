@extends('layouts.admin')

@section('title', __('inventory.title'))
@section('page_title', __('inventory.title'))
@section('page_subtitle', __('inventory.subtitle'))

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.totals.variants') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-brand-950">{{ $totals['variants'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.totals.low_stock') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ $totals['low_stock'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.totals.out_of_stock') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-red-700">{{ $totals['out_of_stock'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-500">{{ __('inventory.totals.reserved') }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $totals['reserved'] }}</p>
        </div>
    </div>

    @if ($totals['low_stock'] > 0)
        <div class="mt-5 flex flex-wrap items-center gap-3 rounded-none border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <svg class="h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3.2a1.7 1.7 0 0 1 3 0l6 10.8A1.7 1.7 0 0 1 16 16.5H4a1.7 1.7 0 0 1-1.5-2.5l6-10.8ZM10 7a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V8a1 1 0 0 1 1-1Zm0 7.5a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2Z" clip-rule="evenodd"/></svg>
            <p class="flex-1 font-semibold">{{ __('inventory.low_stock_alert', ['count' => $totals['low_stock']]) }}</p>
            <a href="{{ route('admin.inventory.index', ['low_stock' => 1]) }}" class="font-bold underline underline-offset-4">{{ __('inventory.show_low_stock') }}</a>
        </div>
    @endif

    <div class="mt-5 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="q" class="label text-xs">{{ __('common.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ __('inventory.search_placeholder') }}" class="input h-10 w-64">
            </div>
            <label class="inline-flex h-10 items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                {{ __('inventory.filters.low_stock') }}
            </label>
            <label class="inline-flex h-10 items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="out_of_stock" value="1" @checked(request()->boolean('out_of_stock')) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                {{ __('inventory.filters.out_of_stock') }}
            </label>
            <button type="submit" class="btn-primary h-10">{{ __('common.apply') }}</button>
            @if ($search !== '' || request()->boolean('low_stock') || request()->boolean('out_of_stock'))
                <a href="{{ route('admin.inventory.index') }}" class="btn-outline h-10">{{ __('common.reset') }}</a>
            @endif
        </form>
    </div>

    <div class="mt-4 table-wrap">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('inventory.product') }}</th>
                    <th>{{ __('inventory.sku') }}</th>
                    <th class="text-center">{{ __('inventory.on_hand') }}</th>
                    <th class="text-center">{{ __('inventory.reserved') }}</th>
                    <th class="text-center">{{ __('inventory.available') }}</th>
                    <th class="text-center">{{ __('inventory.threshold') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($variants as $variant)
                    <tr class="{{ $variant->available_stock <= 0 ? 'bg-red-50/40' : ($variant->isLowStock() ? 'bg-amber-50/30' : '') }}">
                        <td>
                            <a href="{{ route('admin.products.edit', $variant->product) }}" class="font-semibold text-slate-900 hover:text-brand-800">{{ $variant->product->name_ar }}</a>
                            @if ($variant->label !== $variant->sku)
                                <p class="text-xs text-slate-500">{{ $variant->label }}</p>
                            @endif
                        </td>
                        <td class="font-mono text-xs text-slate-600" dir="ltr">{{ $variant->sku }}</td>
                        <td class="text-center font-semibold">{{ $variant->stock }}</td>
                        <td class="text-center text-amber-700">{{ $variant->reserved }}</td>
                        <td class="text-center font-extrabold {{ $variant->available_stock <= 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $variant->available_stock }}</td>
                        <td class="text-center text-sm text-slate-500">{{ $variant->low_stock_threshold }}</td>
                        <td>
                            @if ($variant->available_stock <= 0)
                                <span class="badge-danger">{{ __('inventory.status_out') }}</span>
                            @elseif ($variant->isLowStock())
                                <span class="badge-warning">{{ __('inventory.status_low') }}</span>
                            @else
                                <span class="badge-success">{{ __('inventory.status_ok') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.inventory.show', $variant) }}" class="btn-outline btn-sm">{{ __('inventory.manage') }}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('inventory.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $variants->links() }}</div>
@endsection
