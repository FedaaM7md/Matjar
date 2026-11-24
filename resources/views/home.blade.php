@extends('layouts.app')

@section('title', __('home.meta_title'))
@section('meta_description', __('home.meta_description'))

@php
    $heroProduct = $featuredProducts->first();
@endphp

@section('content')
    <section class="border-b border-stone-200 bg-white">
        <div class="container-page grid items-center gap-8 py-10 sm:py-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12 lg:py-20">
            <div>
                <p class="eyebrow">{{ __('home.hero_eyebrow') }}</p>
                <h1 class="mt-5 font-display text-4xl font-extrabold leading-[1.05] text-brand-950 sm:text-5xl lg:text-6xl">
                    {{ __('home.hero_title_1') }}
                    <span class="block text-accent-500">{{ __('home.hero_title_2') }}</span>
                </h1>
                <p class="mt-5 max-w-xl text-base leading-relaxed text-stone-600">{{ __('home.hero_subtitle') }}</p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ url('/shop') }}" class="btn-primary btn-lg">
                        {{ __('home.cta_shop') }}
                        <svg class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="{{ url('/shop') }}" class="btn-outline btn-lg">{{ __('home.cta_categories') }}</a>
                </div>

                <dl class="mt-10 grid max-w-lg grid-cols-3 divide-x divide-stone-200 border-y border-stone-200 rtl:divide-x-reverse">
                    <div class="px-4 py-4 first:ps-0">
                        <dd class="font-display text-2xl font-extrabold text-brand-950 num" dir="ltr">27</dd>
                        <dt class="mt-1 text-xs font-semibold text-stone-500">{{ __('home.stat_governorates') }}</dt>
                    </div>
                    <div class="px-4 py-4">
                        <dd class="font-display text-lg font-extrabold leading-8 text-brand-950">{{ __('common.cod') }}</dd>
                        <dt class="mt-1 text-xs font-semibold text-stone-500">{{ __('home.stat_payment') }}</dt>
                    </div>
                    <div class="px-4 py-4">
                        <dd class="font-display text-2xl font-extrabold text-brand-950">{{ __('common.egp') }}</dd>
                        <dt class="mt-1 text-xs font-semibold text-stone-500">{{ __('home.stat_currency') }}</dt>
                    </div>
                </dl>
            </div>

            <div class="relative">
                @if ($heroProduct)
                    <a href="{{ route('shop.product', $heroProduct->slug) }}" class="group block border border-stone-200 bg-white">
                        <div class="relative aspect-4/3 overflow-hidden bg-paper">
                            @if ($heroProduct->primary_image_path)
                                <img src="{{ asset('storage/'.$heroProduct->primary_image_path) }}" alt="{{ $heroProduct->name }}"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                            @else
                                @include('products.illustration', ['category' => $heroProduct->category?->slug, 'label' => $heroProduct->name, 'color' => $heroProduct->variants->first()?->color_hex, 'class' => 'h-full w-full'])
                            @endif
                            <span class="absolute start-4 top-4 bg-brand-950 px-3 py-1 text-[11px] font-bold text-white">{{ __('home.editorial_kicker') }}</span>
                        </div>
                        <div class="flex items-end justify-between gap-4 p-5">
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold text-accent-600">{{ $heroProduct->category?->name }}</p>
                                <h2 class="mt-1 truncate font-display text-lg font-extrabold text-brand-950">{{ $heroProduct->name }}</h2>
                                <p class="mt-2 text-sm text-stone-600">{{ __('home.editorial_price_label') }}
                                    <span class="font-extrabold text-price-600 num" dir="ltr">{{ \App\Support\Money::withCurrency((int) $heroProduct->min_price_cents) }}</span>
                                </p>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-brand-950 text-white transition group-hover:bg-accent-500" aria-hidden="true">
                                <svg class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                            </span>
                        </div>
                    </a>
                @else
                    <div class="flex aspect-4/3 items-center justify-center bg-brand-950 text-white">
                        <x-logo class="h-28 w-28" />
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($homeCategories->isNotEmpty())
        <section class="border-b border-stone-200">
            <div class="container-page py-12">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('home.categories_eyebrow') }}</p>
                        <h2 class="section-title mt-2">{{ __('home.categories_title') }}</h2>
                    </div>
                    <a href="{{ url('/shop') }}" class="hidden text-sm font-bold text-brand-900 underline-offset-4 hover:text-accent-600 hover:underline sm:inline-flex">{{ __('common.view_all') }}</a>
                </div>
                <div class="mt-6 grid gap-px border border-stone-200 bg-stone-200 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($homeCategories as $category)
                        <a href="{{ route('shop.category', $category->slug) }}" class="group flex items-start justify-between gap-4 bg-white p-6 transition hover:bg-brand-50">
                            <div class="min-w-0">
                                <span class="font-display text-xs font-extrabold text-accent-500 num" dir="ltr">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="mt-2 truncate font-display text-lg font-extrabold text-brand-950 group-hover:text-accent-600">{{ $category->name }}</h3>
                                <p class="mt-1 text-xs font-semibold text-stone-500">{{ trans_choice('common.products_count', $category->products_count, ['count' => $category->products_count]) }}</p>
                            </div>
                            <svg class="mt-1 h-5 w-5 shrink-0 text-stone-400 transition group-hover:text-accent-500 rtl:-scale-x-100" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 4l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <section class="py-12">
            <div class="container-page">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('home.featured_eyebrow') }}</p>
                        <h2 class="section-title mt-2">{{ __('home.featured_title') }}</h2>
                    </div>
                    <a href="{{ url('/shop') }}" class="text-sm font-bold text-brand-900 underline-offset-4 hover:text-accent-600 hover:underline">{{ __('common.view_all') }}</a>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                    <aside class="flex flex-col justify-between border border-stone-200 bg-brand-950 p-6 text-white sm:p-8">
                        <div>
                            <p class="text-[11px] font-bold text-accent-300">{{ __('home.featured_eyebrow') }}</p>
                            <h3 class="mt-3 font-display text-2xl font-extrabold leading-tight text-white">{{ __('home.delivery_title') }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-brand-200">{{ __('home.delivery_body') }}</p>
                        </div>
                        <a href="{{ url('/shop') }}" class="mt-6 inline-flex items-center gap-2 self-start border border-white/30 px-4 py-2.5 text-sm font-bold text-white transition hover:border-white hover:bg-white hover:text-brand-950">
                            {{ __('home.cta_shop') }}
                            <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
                        </a>
                    </aside>

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($featuredProducts->take(4) as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="container-page py-12">
        <div class="grid gap-px border border-stone-200 bg-stone-200 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([1, 2, 3, 4] as $feature)
                <div class="bg-white p-5">
                    <span class="font-display text-sm font-extrabold text-accent-500 num" dir="ltr">{{ str_pad($feature, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="mt-3 font-display text-base font-extrabold text-brand-950">{{ __('home.feature_'.$feature.'_title') }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-stone-600">{{ __('home.feature_'.$feature.'_body') }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container-page pb-12">
        <div class="grid gap-8 bg-paper p-6 sm:p-10 lg:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)] lg:items-center">
            <div>
                <p class="eyebrow">{{ __('home.steps_eyebrow') }}</p>
                <h2 class="mt-2 font-display text-2xl font-extrabold text-brand-950 sm:text-3xl">{{ __('home.steps_title') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ __('home.steps_subtitle') }}</p>
            </div>
            <ol class="grid gap-px bg-stone-300 sm:grid-cols-3">
                @foreach ([1, 2, 3] as $step)
                    <li class="bg-white p-5">
                        <span class="flex h-9 w-9 items-center justify-center bg-brand-950 font-display text-sm font-extrabold text-white num">{{ $step }}</span>
                        <p class="mt-3 font-display text-sm font-extrabold text-brand-950">{{ __('home.step_'.$step.'_title') }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-stone-600">{{ __('home.step_'.$step.'_body') }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
