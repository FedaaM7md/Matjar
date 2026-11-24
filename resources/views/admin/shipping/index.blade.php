@extends('layouts.admin')

@section('title', __('admin.shipping.title'))
@section('page_title', __('admin.shipping.title'))
@section('page_subtitle', __('admin.shipping.subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm leading-relaxed text-slate-500">{{ __('admin.shipping.intro') }}</p>
        <span class="badge-info">{{ __('admin.shipping.active_count', ['count' => $activeCount]) }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($governorates as $governorate)
            @php $zone = $governorate->zone; @endphp
            <form method="POST" action="{{ route('admin.shipping.update', $governorate) }}" class="card p-4">
                @csrf
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="font-bold text-slate-900">{{ $governorate->name }}</p>
                        <p class="text-[11px] text-slate-400" dir="ltr">{{ $governorate->code }}</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                        <input type="checkbox" name="is_active" value="1" @checked($zone?->is_active) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                        {{ __('common.active') }}
                    </label>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div>
                        <label for="rate-{{ $governorate->id }}" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('fields.shipping_rate') }} *</label>
                        <input id="rate-{{ $governorate->id }}" name="rate" type="number" step="0.01" min="0" required dir="ltr"
                               value="{{ old('rate', $zone ? \App\Support\Money::toMajor($zone->rate_cents) : '0.00') }}" class="input h-9 px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label for="threshold-{{ $governorate->id }}" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('fields.free_threshold') }}</label>
                        <input id="threshold-{{ $governorate->id }}" name="free_threshold" type="number" step="0.01" min="0" dir="ltr"
                               value="{{ old('free_threshold', $zone?->free_threshold_cents !== null ? \App\Support\Money::toMajor($zone->free_threshold_cents) : '') }}" class="input h-9 px-2 py-1 text-sm" placeholder="—">
                    </div>
                    <div>
                        <label for="min-{{ $governorate->id }}" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('fields.delivery_days_min') }}</label>
                        <input id="min-{{ $governorate->id }}" name="delivery_days_min" type="number" min="1" max="60" required dir="ltr"
                               value="{{ old('delivery_days_min', $zone?->delivery_days_min ?? 1) }}" class="input h-9 px-2 py-1 text-sm">
                    </div>
                    <div>
                        <label for="max-{{ $governorate->id }}" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('fields.delivery_days_max') }}</label>
                        <input id="max-{{ $governorate->id }}" name="delivery_days_max" type="number" min="1" max="60" required dir="ltr"
                               value="{{ old('delivery_days_max', $zone?->delivery_days_max ?? 5) }}" class="input h-9 px-2 py-1 text-sm">
                    </div>
                </div>

                <button type="submit" class="btn-outline btn-sm mt-3 w-full">{{ __('common.save') }}</button>
            </form>
        @endforeach
    </div>

    <p class="mt-5 rounded-none border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">{{ __('admin.shipping.demo_note') }}</p>
@endsection
