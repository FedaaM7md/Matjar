@php
    $isEdit = $coupon->exists;
    $action = $isEdit ? route('admin.coupons.update', $coupon) : route('admin.coupons.store');
    $percentValue = $isEdit && $coupon->isPercent() ? rtrim(rtrim(number_format($coupon->value / 100, 2, '.', ''), '0'), '.') : '';
    $fixedValue = $isEdit && ! $coupon->isPercent() ? \App\Support\Money::toMajor($coupon->value) : '';
@endphp

<form method="POST" action="{{ $action }}" class="card max-w-3xl space-y-4 p-5 sm:p-6" x-data="{ type: '{{ old('type', $coupon->type) }}' }">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="code" class="label">{{ __('fields.coupon_code') }} *</label>
            <input id="code" name="code" value="{{ old('code', $coupon->code) }}" required maxlength="40" dir="ltr" class="input uppercase @error('code') input-error @enderror" placeholder="WELCOME10">
            @error('code')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="type" class="label">{{ __('fields.coupon_type') }} *</label>
            <select id="type" name="type" x-model="type" class="input">
                <option value="percent">{{ __('coupons.types.percent') }}</option>
                <option value="fixed">{{ __('coupons.types.fixed') }}</option>
            </select>
        </div>
        <div>
            <label for="value" class="label">
                <span x-text="type === 'percent' ? '{{ __('fields.discount_percent') }}' : '{{ __('fields.coupon_value_fixed') }}'"></span> *
            </label>
            <input id="value" name="value" type="number" step="0.01" min="0.01" required dir="ltr"
                   value="{{ old('value', $coupon->isPercent() ? $percentValue : $fixedValue) }}"
                   class="input @error('value') input-error @enderror">
            <p class="mt-1 text-[11px] text-slate-500" x-text="type === 'percent' ? '{{ __('admin.coupons.percent_hint') }}' : '{{ __('admin.coupons.fixed_hint') }}'"></p>
            @error('value')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="min_subtotal" class="label">{{ __('fields.min_subtotal') }}</label>
            <input id="min_subtotal" name="min_subtotal" type="number" step="0.01" min="0" dir="ltr"
                   value="{{ old('min_subtotal', $isEdit ? \App\Support\Money::toMajor($coupon->min_subtotal_cents) : '') }}"
                   class="input" placeholder="0.00">
        </div>
        <div x-show="type === 'percent'" x-cloak>
            <label for="max_discount" class="label">{{ __('fields.max_discount') }}</label>
            <input id="max_discount" name="max_discount" type="number" step="0.01" min="0" dir="ltr"
                   value="{{ old('max_discount', $coupon->max_discount_cents ? \App\Support\Money::toMajor($coupon->max_discount_cents) : '') }}"
                   class="input" placeholder="{{ __('admin.coupons.max_discount_hint') }}">
        </div>
        <div>
            <label for="usage_limit" class="label">{{ __('fields.usage_limit') }}</label>
            <input id="usage_limit" name="usage_limit" type="number" min="1" dir="ltr" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="input" placeholder="{{ __('admin.coupons.unlimited') }}">
        </div>
        <div>
            <label for="per_user_limit" class="label">{{ __('fields.per_user_limit') }}</label>
            <input id="per_user_limit" name="per_user_limit" type="number" min="1" dir="ltr" value="{{ old('per_user_limit', $coupon->per_user_limit ?? 1) }}" class="input">
        </div>
        <div>
            <label for="starts_at" class="label">{{ __('fields.starts_at') }}</label>
            <input id="starts_at" name="starts_at" type="date" dir="ltr" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}" class="input">
        </div>
        <div>
            <label for="ends_at" class="label">{{ __('fields.ends_at') }}</label>
            <input id="ends_at" name="ends_at" type="date" dir="ltr" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d')) }}" class="input">
            @error('ends_at')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end pb-2">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                {{ __('common.active') }}
            </label>
        </div>
    </div>

    <p class="rounded-none bg-brand-50 p-3 text-[11px] leading-relaxed text-brand-900">{{ __('admin.coupons.demo_note') }}</p>

    <div class="flex items-center gap-3">
        <button type="submit" class="btn-primary">{{ $isEdit ? __('common.save') : __('common.create') }}</button>
        <a href="{{ route('admin.coupons.index') }}" class="btn-ghost">{{ __('common.cancel') }}</a>
    </div>
</form>
