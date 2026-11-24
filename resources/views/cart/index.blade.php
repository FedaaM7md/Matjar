@extends('layouts.app')

@section('title', __('cart.title'))

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="container-page py-8">
            <nav aria-label="breadcrumb" class="text-xs font-semibold text-slate-500">
                <ol class="flex items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-800">{{ __('common.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-slate-800">{{ __('cart.title') }}</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-3xl font-extrabold text-brand-950">{{ __('cart.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('cart.subtitle') }}</p>
        </div>
    </div>

    @if (! $cart || $summary['is_empty'])
        <div class="container-page py-12">
            <div class="card mx-auto max-w-xl px-6 py-14 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-none bg-brand-50 text-brand-800">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h6.9a2 2 0 0 0 2-1.6L21 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
                </span>
                <h2 class="mt-4 text-xl font-extrabold text-slate-900">{{ __('cart.empty_title') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('cart.empty_body') }}</p>
                <a href="{{ route('shop.index') }}" class="btn-primary mt-6">{{ __('cart.continue_shopping') }}</a>
            </div>
        </div>
    @else
        <div class="container-page pt-8 pb-28 lg:pb-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-3">
                    @foreach ($summary['items'] as $line)
                        @php $item = $line['item']; @endphp
                        <div class="card p-4 sm:p-5">
                            <div class="flex gap-4">
                                <a href="{{ $line['product'] ? route('shop.product', $line['product']->slug) : '#' }}" class="block h-20 w-20 shrink-0 overflow-hidden rounded-none border border-slate-200 bg-brand-50 sm:h-24 sm:w-24">
                                    @if ($line['product']?->primary_image_path)
                                        <img src="{{ asset('storage/'.$line['product']->primary_image_path) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        @include('products.illustration', ['category' => $line['product']?->category?->slug, 'label' => '', 'color' => $line['variant']?->color_hex, 'class' => 'h-full w-full'])
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <a href="{{ $line['product'] ? route('shop.product', $line['product']->slug) : '#' }}" class="line-clamp-2 font-bold text-slate-900 hover:text-brand-800">{{ $line['product']?->name ?? __('cart.unavailable_item') }}</a>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $line['variant']?->label }}</p>
                                            <p class="mt-0.5 font-mono text-[11px] text-slate-400" dir="ltr">{{ $line['variant']?->sku }}</p>
                                        </div>
                                        <p class="text-sm font-extrabold text-brand-950" dir="ltr">{{ \App\Support\Money::withCurrency($line['line_total_cents']) }}</p>
                                    </div>

                                    @if ($line['issues'])
                                        <p class="mt-2 rounded-none bg-amber-50 px-2.5 py-1.5 text-[11px] font-semibold text-amber-900">
                                            {{ __('cart.stock_warning', ['available' => $line['available']]) }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-end gap-2">
                                            @csrf @method('PATCH')
                                            <div>
                                                <label for="qty-{{ $item->id }}" class="sr-only">{{ __('cart.quantity') }}</label>
                                                <input id="qty-{{ $item->id }}" name="quantity" type="number" min="1" max="20" value="{{ $line['quantity'] }}"
                                                       class="input h-9 w-20 px-2 py-1 text-sm" dir="ltr">
                                            </div>
                                            <button type="submit" class="btn-outline btn-sm h-9">{{ __('cart.update') }}</button>
                                        </form>

                                        <span class="text-xs text-slate-500" dir="ltr">{{ \App\Support\Money::withCurrency($line['unit_price_cents']) }} × {{ $line['quantity'] }}</span>

                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}" class="ms-auto">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-red-600 underline-offset-4 hover:underline">{{ __('cart.remove') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <a href="{{ route('shop.index') }}" class="btn-ghost">&larr; {{ __('cart.continue_shopping') }}</a>
                        <p class="text-xs text-slate-500">{{ __('cart.item_count', ['count' => $summary['item_count']]) }}</p>
                    </div>
                </div>

                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <div class="card p-5">
                        <h2 class="text-lg font-extrabold text-slate-900">{{ __('cart.summary') }}</h2>

                        <dl class="mt-4 space-y-2.5 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('cart.subtotal') }}</dt><dd class="font-semibold text-slate-800" dir="ltr">{{ $summary['subtotal_formatted'] }}</dd></div>

                            @if ($summary['discount_cents'] > 0)
                                <div class="flex justify-between gap-3 text-emerald-700"><dt>{{ __('cart.discount') }}</dt><dd class="font-semibold" dir="ltr">− {{ $summary['discount_formatted'] }}</dd></div>
                            @endif

                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('cart.shipping') }}</dt><dd class="text-xs font-semibold text-slate-500">{{ __('cart.shipping_at_checkout') }}</dd></div>
                        </dl>

                        <div class="mt-4 flex items-baseline justify-between border-t border-dashed border-slate-200 pt-4">
                            <span class="text-sm font-bold text-slate-900">{{ __('cart.total_before_shipping') }}</span>
                            <span class="text-xl font-extrabold text-brand-950" dir="ltr">{{ $summary['total_formatted'] }}</span>
                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-5">
                            @if ($summary['coupon'])
                                <div class="rounded-none border border-emerald-200 bg-emerald-50 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-bold text-emerald-900">{{ __('cart.applied_coupon') }}</p>
                                            <p class="font-mono text-sm font-bold text-emerald-800" dir="ltr">{{ $summary['coupon']->code }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-emerald-800 underline underline-offset-4">{{ __('cart.remove_coupon') }}</button>
                                        </form>
                                    </div>
                                    @if ($summary['coupon_error'])
                                        <p class="mt-2 text-xs font-semibold text-red-700">{{ $summary['coupon_error'] }}</p>
                                    @endif
                                </div>
                            @else
                                <form method="POST" action="{{ route('cart.coupon.apply') }}" class="space-y-2">
                                    @csrf
                                    <label for="coupon" class="label">{{ __('cart.coupon') }}</label>
                                    <div class="flex gap-2">
                                        <input id="coupon" name="coupon" value="{{ old('coupon') }}" class="input uppercase" placeholder="{{ __('cart.coupon_placeholder') }}" maxlength="40" dir="ltr">
                                        <button type="submit" class="btn-outline shrink-0">{{ __('cart.apply_coupon') }}</button>
                                    </div>
                                </form>
                            @endif
                        </div>

                        <a href="{{ url('/checkout') }}" class="btn-primary mt-5 w-full">{{ __('cart.checkout') }}</a>
                        <p class="mt-3 text-center text-[11px] leading-relaxed text-slate-500">{{ __('cart.checkout_note') }}</p>
                    </div>
                </aside>
            </div>
        </div>

        {{-- Mobile sticky checkout bar --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-accent-500 bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.12)] lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] text-stone-500">{{ __('cart.total_before_shipping') }}</p>
                    <p class="font-display text-lg font-extrabold text-price-600 num" dir="ltr">{{ $summary['total_formatted'] }}</p>
                </div>
                <a href="{{ url('/checkout') }}" class="btn-primary min-h-11 flex-1">{{ __('cart.checkout') }}</a>
            </div>
        </div>
    @endif
@endsection
