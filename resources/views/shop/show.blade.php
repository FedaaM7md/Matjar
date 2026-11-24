@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150) ?: $product->name)

@section('content')
    <div class="border-b border-stone-200 bg-white">
        <div class="container-page py-4">
            <nav aria-label="breadcrumb" class="text-xs font-semibold text-stone-500">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-900">{{ __('common.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('shop.index') }}" class="hover:text-brand-900">{{ __('common.shop') }}</a></li>
                    @if ($product->category)
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('shop.category', $product->category->slug) }}" class="hover:text-brand-900">{{ $product->category->name }}</a></li>
                    @endif
                    <li aria-hidden="true">/</li>
                    <li class="max-w-52 truncate text-stone-800">{{ $product->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container-page pt-8 pb-28 lg:py-12" x-data="productBuy" data-variants="{{ json_encode($variants) }}">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-12">
            <div x-data="{ active: 0 }" class="space-y-3">
                <div class="relative aspect-square overflow-hidden border border-stone-200 bg-paper">
                    @forelse ($product->images as $index => $image)
                        <img x-show="active === {{ $index }}" src="{{ $image->url }}"
                             alt="{{ $image->{'alt_'.$locale} ?: $product->name }}"
                             class="h-full w-full object-cover" @if ($index > 0) loading="lazy" @endif>
                    @empty
                        @include('products.illustration', ['category' => $product->category?->slug, 'label' => $product->name, 'color' => $product->variants->first()?->color_hex, 'class' => 'h-full w-full'])
                    @endforelse

                    @if (! $product->variants->contains(fn ($variant) => $variant->available_stock > 0))
                        <span class="absolute start-4 top-4 bg-brand-950 px-3 py-1 text-[11px] font-bold text-white">{{ __('shop.out_of_stock') }}</span>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('shop.gallery') }}">
                        @foreach ($product->images as $index => $image)
                            <button type="button" @click="active = {{ $index }}"
                                    :class="active === {{ $index }} ? 'border-brand-900 ring-2 ring-brand-900/20' : 'border-stone-200'"
                                    class="h-16 w-16 overflow-hidden border bg-white"
                                    :aria-selected="active === {{ $index }} ? 'true' : 'false'" role="tab">
                                <img src="{{ $image->url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                <span class="sr-only">{{ __('shop.view_image', ['number' => $index + 1]) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="min-w-0">
                @if ($product->category)
                    <a href="{{ route('shop.category', $product->category->slug) }}" class="eyebrow hover:text-accent-600">{{ $product->category->name }}</a>
                @endif
                <h1 class="mt-3 font-display text-3xl font-extrabold leading-tight text-brand-950 sm:text-4xl">{{ $product->name }}</h1>
                @if ($product->brand)
                    <p class="mt-2 text-sm text-stone-500">{{ __('shop.brand') }}: <span class="font-semibold text-stone-700">{{ $product->brand }}</span></p>
                @endif
                <p class="mt-2 text-xs font-semibold text-stone-500" dir="ltr">{{ __('fields.sku') }}: <span x-text="selected ? selected.sku : '—'"></span></p>

                <div class="mt-5 flex flex-wrap items-baseline gap-3">
                    <p class="font-display text-3xl font-extrabold text-price-600 num" dir="ltr" x-text="selected ? selected.price_formatted : '—'"></p>
                    <template x-if="selected && selected.compare_at_formatted">
                        <p class="text-lg text-stone-400 line-through num" dir="ltr" x-text="selected.compare_at_formatted"></p>
                    </template>
                </div>

                <div class="mt-4">
                    <template x-if="selected && selected.available > selected.threshold">
                        <p class="inline-flex items-center gap-1.5 border border-price-500/30 bg-price-50 px-3 py-1 text-xs font-bold text-price-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-price-500"></span>
                            {{ __('shop.in_stock') }}
                            <span dir="ltr" x-text="'(' + selected.available + ')'"></span>
                        </p>
                    </template>
                    <template x-if="selected && selected.available > 0 && selected.available <= selected.threshold">
                        <p class="inline-flex items-center gap-1.5 border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-900">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ __('shop.low_stock') }}
                            <span dir="ltr" x-text="'(' + selected.available + ')'"></span>
                        </p>
                    </template>
                    <template x-if="!selected || selected.available < 1">
                        <p class="inline-flex items-center gap-1.5 border border-accent-500/30 bg-accent-50 px-3 py-1 text-xs font-bold text-accent-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>{{ __('shop.out_of_stock') }}
                        </p>
                    </template>
                </div>

                @if ($variants->count() > 1)
                    <fieldset class="mt-6">
                        <legend class="label">{{ __('shop.choose_variant') }}</legend>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="variant in variants" :key="variant.id">
                                <button type="button" @click="select(variant.id)"
                                        :class="selected && selected.id === variant.id ? 'border-brand-900 bg-brand-900 text-white ring-1 ring-brand-900' : 'border-stone-300 bg-white text-stone-700 hover:border-brand-900'"
                                        :disabled="variant.available < 1"
                                        class="flex min-h-11 items-center gap-2 border px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-40">
                                    <span class="h-3.5 w-3.5 rounded-full border border-black/10" :style="variant.color_hex ? 'background-color:' + variant.color_hex : 'display:none'"></span>
                                    <span x-text="variant.label"></span>
                                    <span class="text-[11px] text-stone-400" x-text="variant.available > 0 ? '' : '•'"></span>
                                </button>
                            </template>
                        </div>
                    </fieldset>
                @elseif ($variants->count() === 1)
                    <p class="mt-5 text-sm font-semibold text-stone-700">{{ __('shop.variant') }}: <span class="font-normal text-stone-600">{{ $variants->first()['label'] }}</span></p>
                @endif

                <form method="POST" action="{{ url('/cart/add') }}" id="add-to-cart" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="variant_id" :value="selected ? selected.id : ''">

                    <div class="flex flex-wrap items-center gap-4">
                        <div>
                            <label for="quantity" class="label">{{ __('shop.quantity') }}</label>
                            <div class="inline-flex items-center border border-stone-300 bg-white">
                                <button type="button" class="min-h-11 min-w-11 px-3 py-2.5 text-stone-600 hover:text-brand-950" @click="decrement()" aria-label="{{ __('shop.decrease') }}">−</button>
                                <input id="quantity" name="quantity" type="number" inputmode="numeric" min="1" :max="max" x-model.number="qty"
                                       class="w-14 border-0 bg-transparent p-0 text-center text-sm font-bold text-brand-950 focus:ring-0 num" aria-label="{{ __('shop.quantity') }}">
                                <button type="button" class="min-h-11 min-w-11 px-3 py-2.5 text-stone-600 hover:text-brand-950" @click="increment()" aria-label="{{ __('shop.increase') }}">+</button>
                            </div>
                        </div>
                        <div class="flex-1">
                            <span class="label opacity-0" aria-hidden="true">&nbsp;</span>
                            <button type="submit" class="btn-primary w-full" :disabled="!selected || selected.available < 1 || qty < 1">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h6.9a2 2 0 0 0 2-1.6L21 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
                                {{ __('shop.add_to_cart') }}
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-8 grid gap-px border border-stone-200 bg-stone-200 sm:grid-cols-3">
                    <div class="bg-paper p-3 text-center">
                        <p class="text-xs font-bold text-brand-950">{{ __('common.cod') }}</p>
                        <p class="mt-0.5 text-[11px] text-stone-600">{{ __('shop.pay_on_delivery_hint') }}</p>
                    </div>
                    <div class="bg-paper p-3 text-center">
                        <p class="text-xs font-bold text-brand-950">{{ __('shop.nationwide_shipping') }}</p>
                        <p class="mt-0.5 text-[11px] text-stone-600">{{ __('shop.nationwide_shipping_hint') }}</p>
                    </div>
                    <div class="bg-paper p-3 text-center">
                        <p class="text-xs font-bold text-brand-950">{{ __('shop.printable_invoice') }}</p>
                        <p class="mt-0.5 text-[11px] text-stone-600">{{ __('shop.printable_invoice_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <section class="border border-stone-200 bg-white p-6">
                <h2 class="font-display text-lg font-extrabold text-brand-950">{{ __('shop.description') }}</h2>
                <div class="mt-3 space-y-3 text-sm leading-relaxed text-stone-600">
                    @if ($product->description)
                        {!! nl2br(e($product->description)) !!}
                    @else
                        <p>{{ __('shop.no_description') }}</p>
                    @endif
                </div>
            </section>
            <aside class="border border-stone-200 bg-white p-6">
                <h2 class="font-display text-lg font-extrabold text-brand-950">{{ __('shop.specs') }}</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-stone-500">{{ __('shop.brand') }}</dt><dd class="font-semibold text-stone-800">{{ $product->brand ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone-500">SKU</dt><dd class="font-semibold text-stone-800" dir="ltr" x-text="selected ? selected.sku : '—'"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone-500">{{ __('shop.variant') }}</dt><dd class="font-semibold text-stone-800" x-text="selected ? selected.label : '—'"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-stone-500">{{ __('shop.availability') }}</dt><dd class="font-semibold text-stone-800" x-text="selected ? (selected.available > 0 ? '{{ __('shop.available') }}' : '{{ __('shop.out_of_stock') }}') : '—'"></dd></div>
                </dl>
                <p class="mt-4 border-s-2 border-amber-400 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-900">{{ __('shop.demo_product_note') }}</p>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="section-title">{{ __('shop.related') }}</h2>
                    <a href="{{ route('shop.index') }}" class="text-sm font-bold text-brand-900 underline-offset-4 hover:text-accent-600 hover:underline">{{ __('common.view_all') }}</a>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Mobile sticky add-to-cart --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t-2 border-accent-500 bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.12)] lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0">
                    <p class="font-display text-lg font-extrabold text-price-600 num" dir="ltr" x-text="selected ? selected.price_formatted : '—'"></p>
                    <p class="truncate text-[11px] text-stone-500" x-text="selected ? selected.label : ''"></p>
                </div>
                <button type="submit" form="add-to-cart" class="btn-primary min-h-11 flex-1" :disabled="!selected || selected.available < 1 || qty < 1">
                    {{ __('shop.add_to_cart') }}
                </button>
            </div>
        </div>
    </div>
@endsection
