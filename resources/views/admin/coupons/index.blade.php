@extends('layouts.admin')

@section('title', __('admin.coupons.title'))
@section('page_title', __('admin.coupons.title'))
@section('page_subtitle', __('admin.coupons.subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.coupons.index') }}" class="flex items-end gap-2">
            <div>
                <label for="q" class="label text-xs">{{ __('common.search') }}</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('admin.coupons.search_placeholder') }}" class="input h-10 w-56 uppercase">
            </div>
            <button type="submit" class="btn-outline h-10">{{ __('common.apply') }}</button>
        </form>
        @can('coupons.manage')
            <a href="{{ route('admin.coupons.create') }}" class="btn-primary">{{ __('admin.coupons.add') }}</a>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('admin.coupons.code') }}</th>
                    <th>{{ __('admin.coupons.type') }}</th>
                    <th>{{ __('admin.coupons.value') }}</th>
                    <th class="text-center">{{ __('admin.coupons.usage') }}</th>
                    <th>{{ __('admin.coupons.validity') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="font-mono text-sm font-extrabold text-brand-900" dir="ltr">{{ $coupon->code }}</td>
                        <td class="text-sm text-slate-600">{{ $coupon->type_label }}</td>
                        <td class="text-sm font-semibold" dir="ltr">{{ $coupon->value_label }}</td>
                        <td class="text-center text-sm">{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}</td>
                        <td class="text-[11px] text-slate-500">
                            {{ $coupon->starts_at?->format('Y-m-d') ?? '—' }} → {{ $coupon->ends_at?->format('Y-m-d') ?? '∞' }}
                        </td>
                        <td><span class="{{ $coupon->status_label === __('coupons.statuses.active') ? 'badge-success' : 'badge-neutral' }}">{{ $coupon->status_label }}</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @can('coupons.manage')
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn-outline btn-sm">{{ __('common.edit') }}</a>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" x-data="confirmForm" data-message="{{ __('admin.coupons.confirm_delete') }}" @submit="onConfirm($event)">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">{{ __('common.delete') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('admin.coupons.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $coupons->links() }}</div>
@endsection
