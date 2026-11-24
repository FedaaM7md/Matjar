@extends('layouts.admin')

@section('title', __('admin.settings.title'))
@section('page_title', __('admin.settings.title'))
@section('page_subtitle', __('admin.settings.subtitle'))

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-5">
        @csrf @method('PUT')

        <section class="card p-5">
            <h2 class="font-bold text-slate-900">{{ __('admin.settings.store_group') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="store_name_ar" class="label">{{ __('fields.store_name_ar') }} *</label>
                    <input id="store_name_ar" name="store_name_ar" value="{{ old('store_name_ar', $settings['store_name_ar']) }}" required class="input @error('store_name_ar') input-error @enderror">
                    @error('store_name_ar')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="store_name_en" class="label">{{ __('fields.store_name_en') }} *</label>
                    <input id="store_name_en" name="store_name_en" value="{{ old('store_name_en', $settings['store_name_en']) }}" required dir="ltr" class="input">
                </div>
                <div>
                    <label for="support_phone" class="label">{{ __('fields.support_phone') }} *</label>
                    <input id="support_phone" name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" required dir="ltr" class="input">
                </div>
                <div>
                    <label for="support_email" class="label">{{ __('fields.support_email') }} *</label>
                    <input id="support_email" name="support_email" type="email" value="{{ old('support_email', $settings['support_email']) }}" required dir="ltr" class="input">
                </div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="font-bold text-slate-900">{{ __('admin.settings.payments_group') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.settings.payments_hint') }}</p>
            <div class="mt-4 space-y-3">
                <label class="flex items-start gap-3 rounded-none border border-slate-200 p-3">
                    <input type="checkbox" name="cod_enabled" value="1" @checked(old('cod_enabled', \App\Models\Setting::bool('cod_enabled', true))) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                    <span>
                        <span class="block text-sm font-bold text-slate-800">{{ __('common.cod') }}</span>
                        <span class="text-xs text-slate-500">{{ __('admin.settings.cod_hint') }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-none border border-slate-200 p-3">
                    <input type="checkbox" name="fake_gateway_enabled" value="1" @checked(old('fake_gateway_enabled', \App\Models\Setting::bool('fake_gateway_enabled', true))) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                    <span>
                        <span class="block text-sm font-bold text-slate-800">{{ __('common.demo_gateway') }}</span>
                        <span class="text-xs text-slate-500">{{ __('admin.settings.gateway_hint') }}</span>
                    </span>
                </label>
                @error('cod_enabled')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card p-5">
            <h2 class="font-bold text-slate-900">{{ __('admin.settings.inventory_group') }}</h2>
            <div class="mt-4 max-w-xs">
                <label for="low_stock_threshold" class="label">{{ __('fields.low_stock_threshold') }} *</label>
                <input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" required dir="ltr" value="{{ old('low_stock_threshold', $settings['low_stock_threshold'] ?? 5) }}" class="input">
                <p class="mt-1 text-[11px] text-slate-500">{{ __('admin.settings.low_stock_hint') }}</p>
            </div>
        </section>

        <p class="rounded-none border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">{{ __('admin.settings.demo_note') }}</p>

        <button type="submit" class="btn-primary">{{ __('common.save') }}</button>
    </form>
@endsection
