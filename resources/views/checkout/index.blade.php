@extends('layouts.app')

@section('title', __('checkout.title'))

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="container-page py-8">
            <nav aria-label="breadcrumb" class="text-xs font-semibold text-slate-500">
                <ol class="flex items-center gap-1.5">
                    <li><a href="{{ route('cart.index') }}" class="hover:text-brand-800">{{ __('cart.title') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-slate-800">{{ __('checkout.title') }}</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-3xl font-extrabold text-brand-950">{{ __('checkout.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('checkout.subtitle') }}</p>
        </div>
    </div>

    <div class="container-page py-8"
         x-data="checkoutForm"
         data-config="{{ json_encode([
            'zones' => $zones,
            'subtotal' => $summary['subtotal_cents'],
            'discount' => $summary['discount_cents'],
            'currency' => $currencyLabel,
            'governorateId' => (int) old('governorate_id', 0),
            'paymentMethod' => old('payment_method', $defaultPayment),
         ]) }}">
        <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" class="grid gap-6 pb-24 lg:grid-cols-[minmax(0,1fr)_380px] lg:pb-0">
            @csrf
            <div class="space-y-5">
                <section class="card p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-900 text-sm font-extrabold text-white">1</span>
                        <h2 class="text-lg font-extrabold text-slate-900">{{ __('checkout.contact_title') }}</h2>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="label">{{ __('checkout.customer_name') }} *</label>
                            <input id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required autocomplete="name" class="input @error('customer_name') input-error @enderror">
                            @error('customer_name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="customer_phone" class="label">{{ __('checkout.customer_phone') }} *</label>
                            <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone', auth()->user()?->phone) }}" required autocomplete="tel" dir="ltr" class="input @error('customer_phone') input-error @enderror" placeholder="{{ config('matjar.phone.example') }}">
                            <p class="mt-1 text-[11px] text-slate-500">{{ __('checkout.phone_hint') }}</p>
                            @error('customer_phone')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="customer_email" class="label">{{ __('checkout.customer_email') }} <span class="font-normal text-slate-400">({{ __('common.optional') }})</span></label>
                            <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', auth()->user()?->email) }}" autocomplete="email" dir="ltr" class="input @error('customer_email') input-error @enderror">
                            @error('customer_email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="card p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-900 text-sm font-extrabold text-white">2</span>
                        <h2 class="text-lg font-extrabold text-slate-900">{{ __('checkout.shipping_title') }}</h2>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="governorate_id" class="label">{{ __('checkout.governorate') }} *</label>
                            <select id="governorate_id" name="governorate_id" x-model.number="governorateId" required class="input @error('governorate_id') input-error @enderror">
                                <option value="">{{ __('checkout.choose_governorate') }}</option>
                                @foreach ($governorates as $governorate)
                                    <option value="{{ $governorate->id }}">{{ $governorate->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500" x-show="zone" x-cloak>
                                {{ __('checkout.delivery_days') }}: <span x-text="zone ? (zone.delivery_days_min + '–' + zone.delivery_days_max) : ''"></span>
                            </p>
                            @error('governorate_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="address" class="label">{{ __('checkout.address') }} *</label>
                            <textarea id="address" name="address" rows="3" required autocomplete="street-address" class="input @error('address') input-error @enderror" placeholder="{{ __('checkout.address_placeholder') }}">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="label">{{ __('checkout.notes') }} <span class="font-normal text-slate-400">({{ __('common.optional') }})</span></label>
                            <textarea id="notes" name="notes" rows="2" maxlength="500" class="input" placeholder="{{ __('checkout.notes_placeholder') }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="card p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-900 text-sm font-extrabold text-white">3</span>
                        <h2 class="text-lg font-extrabold text-slate-900">{{ __('checkout.payment_title') }}</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        @if ($codEnabled)
                            <label class="flex cursor-pointer items-start gap-3 rounded-none border border-slate-200 p-4 transition"
                                   :class="paymentMethod === 'cod' ? 'border-brand-700 bg-brand-50 ring-1 ring-brand-700' : 'hover:border-brand-300'">
                                <input type="radio" name="payment_method" value="cod" x-model="paymentMethod" class="mt-0.5 h-4 w-4 border-slate-300 text-brand-800 focus:ring-brand-600" required>
                                <span>
                                    <span class="block font-bold text-slate-900">{{ __('checkout.cod') }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ __('checkout.cod_desc') }}</span>
                                </span>
                            </label>
                        @endif

                        @if ($gatewayEnabled)
                            <label class="flex cursor-pointer items-start gap-3 rounded-none border border-slate-200 p-4 transition"
                                   :class="paymentMethod === 'test_gateway' ? 'border-accent-600 bg-accent-50 ring-1 ring-accent-600' : 'hover:border-accent-300'">
                                <input type="radio" name="payment_method" value="test_gateway" x-model="paymentMethod" class="mt-0.5 h-4 w-4 border-slate-300 text-accent-700 focus:ring-accent-600" required>
                                <span>
                                    <span class="flex flex-wrap items-center gap-2 font-bold text-slate-900">{{ __('checkout.gateway') }} <span class="badge-warning">{{ __('common.demo_badge') }}</span></span>
                                    <span class="mt-1 block text-xs leading-relaxed text-slate-500">{{ __('checkout.gateway_desc') }}</span>
                                </span>
                            </label>
                            <p x-show="paymentMethod === 'test_gateway'" x-cloak class="rounded-none bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-900">{{ __('checkout.gateway_demo_note') }}</p>
                        @endif

                        @error('payment_method')<p class="text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('checkout')<p class="rounded-none bg-red-50 p-3 text-xs font-semibold text-red-700">{{ $message }}</p>@enderror
                        @error('coupon')<p class="rounded-none bg-red-50 p-3 text-xs font-semibold text-red-700">{{ $message }}</p>@enderror
                    </div>
                </section>

                <p class="rounded-none border border-slate-200 bg-white p-4 text-xs leading-relaxed text-slate-500">{{ __('checkout.demo_note') }}</p>
            </div>

            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="card p-5">
                    <h2 class="text-lg font-extrabold text-slate-900">{{ __('checkout.summary') }}</h2>

                    <ul class="mt-4 space-y-3 border-b border-slate-100 pb-4">
                        @foreach ($summary['items'] as $line)
                            <li class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-none bg-brand-50 text-[11px] font-extrabold text-brand-800">{{ $line['quantity'] }}×</span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-xs font-semibold text-slate-800">{{ $line['product']?->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $line['variant']?->label }}</p>
                                </div>
                                <span class="text-xs font-bold text-slate-700" dir="ltr">{{ \App\Support\Money::withCurrency($line['line_total_cents']) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('cart.subtotal') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $summary['subtotal_formatted'] }}</dd></div>
                        @if ($summary['discount_cents'] > 0)
                            <div class="flex justify-between gap-3 text-emerald-700"><dt>{{ __('cart.discount') }} <span class="font-mono text-[11px]">{{ $summary['coupon']?->code }}</span></dt><dd class="font-semibold" dir="ltr">− {{ $summary['discount_formatted'] }}</dd></div>
                        @endif
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('cart.shipping') }}</dt>
                            <dd class="font-semibold text-slate-800" dir="ltr">
                                <template x-if="shippingCents === null"><span class="text-xs text-slate-400">{{ __('checkout.choose_governorate') }}</span></template>
                                <template x-if="shippingCents !== null && freeShipping"><span class="text-emerald-700">{{ __('checkout.free') }}</span></template>
                                <template x-if="shippingCents !== null && !freeShipping"><span x-text="format(shippingCents)"></span></template>
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-2 text-[11px] text-emerald-700" x-show="remainingForFree > 0" x-cloak>
                        {{ __('checkout.add_for_free') }} <span x-text="format(remainingForFree)"></span>
                    </p>

                    <div class="mt-4 flex items-baseline justify-between border-t border-dashed border-slate-200 pt-4">
                        <span class="text-sm font-bold text-slate-900">{{ __('cart.total') }}</span>
                        <span class="text-xl font-extrabold text-brand-950" dir="ltr" x-text="format(total)"></span>
                    </div>

                    <button type="submit" class="btn-primary mt-5 w-full" :disabled="!paymentMethod">
                        {{ __('checkout.place_order') }}
                    </button>
                    <p class="mt-3 text-center text-[11px] leading-relaxed text-slate-500">{{ __('checkout.secure_note') }}</p>
                    <a href="{{ route('cart.index') }}" class="mt-3 block text-center text-xs font-semibold text-slate-500 hover:text-brand-800">{{ __('checkout.back_to_cart') }}</a>
                </div>
            </aside>
        </form>

        {{-- Mobile sticky place-order bar --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-accent-500 bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.12)] lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] text-stone-500">{{ __('cart.total') }}</p>
                    <p class="font-display text-lg font-extrabold text-price-600 num" dir="ltr" x-text="format(total)"></p>
                </div>
                <button type="submit" form="checkout-form" class="btn-primary min-h-11 flex-1" :disabled="!paymentMethod">
                    {{ __('checkout.place_order') }}
                </button>
            </div>
        </div>
    </div>
@endsection
