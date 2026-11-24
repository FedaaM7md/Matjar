{{-- Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary. --}}
@php
    $storeName = \App\Models\Setting::get('store_name_'.$locale, __('common.app_name'));
    $cartCount = $cartCount ?? 0;
    $cartSubtotalCents = $cartSubtotalCents ?? 0;
    $navCategories = $navCategories ?? collect();
    $demoEnabled = (bool) config('matjar.demo.enabled');
    $pageTitle = trim(strip_tags($__env->yieldContent('title', '')));
    $ogTitle = ($pageTitle !== '' ? $pageTitle.' — ' : '').__('common.app_name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', __('common.meta_description'))">
    <meta name="theme-color" content="#111111">
    <title>{{ $ogTitle }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('common.app_name') }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="@yield('meta_description', __('common.meta_description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta property="og:image" content="{{ asset('og-image.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="@yield('meta_description', __('common.meta_description'))">
    <meta name="twitter:image" content="{{ asset('og-image.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:border focus:border-brand-900 focus:bg-white focus:px-4 focus:py-2 focus:text-brand-950 focus:shadow-lg">
    {{ __('common.skip_to_content') }}
</a>

<div class="bg-brand-950 text-brand-100">
    <div class="container-page flex h-9 items-center justify-between gap-3 text-[11px] sm:text-xs">
        <span class="hidden items-center gap-1.5 text-brand-200 sm:flex">{{ __('common.topbar_note') }}</span>
        <div class="flex items-center gap-3">
            <a href="tel:{{ \App\Models\Setting::get('support_phone', '01000000000') }}" class="hidden items-center gap-1 text-brand-200 hover:text-white sm:inline-flex" dir="ltr">
                {{ \App\Models\Setting::get('support_phone', '01000000000') }}
            </a>
            @if ($demoEnabled)
                <a href="{{ route('demo.enter') }}" class="font-bold text-accent-300 hover:text-white">{{ __('demo.launch') }}</a>
            @endif
            <form method="POST" action="{{ route('locale.update', $otherLocale) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1 border border-white/25 px-2 py-0.5 font-semibold transition hover:border-white/50 hover:text-white">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path d="M2.5 10h15M10 2.5c2.2 2 3.3 4.7 3.3 7.5S12.2 15.5 10 17.5C7.8 15.5 6.7 12.8 6.7 10S7.8 4.5 10 2.5Z"/></svg>
                    {{ config('matjar.locales.'.$otherLocale.'.name') }}
                </button>
            </form>
        </div>
    </div>
</div>

@auth
    @if (auth()->user()->isDemo())
        <div class="border-b border-amber-200 bg-amber-50">
            <div class="container-page flex items-center justify-center gap-2 py-1.5 text-center text-[11px] font-semibold text-amber-900">
                {{ __('demo.banner_short') }}
            </div>
        </div>
    @endif
@endauth

<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-stone-200 bg-white">
    <div class="container-page">
        <div class="flex h-16 items-center gap-3 sm:h-20 sm:gap-5">
            <button type="button" class="btn-ghost -ms-2 px-2 lg:hidden" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="mobile-nav" aria-label="{{ __('common.open_menu') }}">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 text-brand-950">
                <x-logo class="h-10 w-10 sm:h-11 sm:w-11" />
                <span class="leading-none">
                    <span class="block font-display text-xl font-extrabold text-brand-950 sm:text-2xl">{{ __('common.app_name') }}</span>
                    <span class="mt-1 block text-[11px] font-semibold text-stone-500">{{ __('common.app_tagline') }}</span>
                </span>
            </a>

            <form action="{{ url('/shop') }}" method="GET" class="relative hidden flex-1 md:block" role="search">
                <label for="desktop-search" class="sr-only">{{ __('common.search') }}</label>
                <input id="desktop-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('common.search_placeholder') }}"
                       class="input h-11 ps-11 pe-4" autocomplete="off">
                <svg class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2" stroke-linecap="round"/></svg>
            </form>

            <div class="ms-auto flex items-center gap-1 sm:gap-2">
                <a href="{{ url('/shop') }}" class="btn-ghost hidden px-3 sm:inline-flex">{{ __('common.shop') }}</a>

                @auth
                    <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                        <button type="button" class="btn-ghost px-2.5" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                            <span class="hidden max-w-24 truncate text-sm lg:inline">{{ auth()->user()->name }}</span>
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top class="absolute end-0 z-50 mt-2 w-56 border border-stone-200 bg-white py-1 shadow-xl" role="menu">
                            <a href="{{ url('/account') }}" class="block px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-paper" role="menuitem">{{ __('common.my_account') }}</a>
                            <a href="{{ url('/account/orders') }}" class="block px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-paper" role="menuitem">{{ __('common.my_orders') }}</a>
                            @if (auth()->user()->canAccessAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 text-sm font-semibold text-brand-900 hover:bg-paper" role="menuitem">{{ __('common.admin_panel') }}</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-100">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2.5 text-start text-sm font-semibold text-accent-700 hover:bg-accent-50" role="menuitem">{{ __('common.logout') }}</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost px-3">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round"/></svg>
                        <span class="hidden text-sm sm:inline">{{ __('common.login') }}</span>
                    </a>
                @endauth

                <a href="{{ url('/cart') }}" class="btn-ghost relative px-2.5" aria-label="{{ __('common.cart') }}" data-cart-count="{{ $cartCount }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h6.9a2 2 0 0 0 2-1.6L21 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute -top-0.5 end-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        <nav class="hidden h-11 items-center gap-0.5 border-t border-stone-100 text-sm font-bold text-stone-700 lg:flex" aria-label="{{ __('common.main_nav') }}">
            <a href="{{ route('home') }}" class="px-3 py-1.5 transition hover:bg-paper hover:text-brand-950">{{ __('common.home') }}</a>
            <a href="{{ url('/shop') }}" class="px-3 py-1.5 transition hover:bg-paper hover:text-brand-950">{{ __('common.all_products') }}</a>
            @foreach ($navCategories as $category)
                <a href="{{ url('/category/'.$category->slug) }}" class="px-3 py-1.5 transition hover:bg-paper hover:text-brand-950">{{ $category->name }}</a>
            @endforeach
            <a href="{{ route('pages.about') }}" class="px-3 py-1.5 transition hover:bg-paper hover:text-brand-950">{{ __('pages.about.title') }}</a>
            <span class="ms-auto inline-flex items-center gap-1.5 text-xs font-semibold text-stone-500">
                <svg class="h-4 w-4 text-accent-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 6.5A1.5 1.5 0 0 1 3.5 5h13A1.5 1.5 0 0 1 18 6.5v.6a2 2 0 0 0 0 5.8v.6A1.5 1.5 0 0 1 16.5 15h-13A1.5 1.5 0 0 1 2 13.5v-.6a2 2 0 0 0 0-5.8Z"/></svg>
                {{ __('common.nav_note') }}
            </span>
        </nav>
    </div>

    <div id="mobile-nav" x-show="mobileOpen" x-cloak x-transition.opacity class="border-t border-stone-200 bg-white lg:hidden">
        <div class="container-page space-y-3 py-4">
            <form action="{{ url('/shop') }}" method="GET" class="relative" role="search">
                <label for="mobile-search" class="sr-only">{{ __('common.search') }}</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('common.search_placeholder') }}" class="input h-11 ps-11">
                <svg class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2" stroke-linecap="round"/></svg>
            </form>
            <div class="grid gap-1 text-sm font-bold">
                <a href="{{ route('home') }}" class="px-3 py-2 hover:bg-paper">{{ __('common.home') }}</a>
                <a href="{{ url('/shop') }}" class="px-3 py-2 hover:bg-paper">{{ __('common.all_products') }}</a>
                @foreach ($navCategories as $category)
                    <a href="{{ url('/category/'.$category->slug) }}" class="px-3 py-2 hover:bg-paper">{{ $category->name }}</a>
                @endforeach
                <a href="{{ route('pages.about') }}" class="px-3 py-2 hover:bg-paper">{{ __('pages.about.title') }}</a>
                <a href="{{ route('pages.contact') }}" class="px-3 py-2 hover:bg-paper">{{ __('pages.contact.title') }}</a>
                @if ($demoEnabled)
                    <a href="{{ route('demo.enter') }}" class="px-3 py-2 font-bold text-accent-600 hover:bg-accent-50">{{ __('demo.launch') }}</a>
                @endif
            </div>
        </div>
    </div>
</header>

<main id="main" class="flex-1">
    <x-flash />
    @yield('content')
</main>

<footer class="mt-16 bg-brand-950 text-brand-100">
    <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sm:col-span-2 lg:col-span-1">
            <div class="flex items-center gap-2.5 text-white">
                <x-logo class="h-10 w-10 text-white" />
                <div>
                    <p class="font-display text-lg font-extrabold text-white">{{ __('common.app_name') }}</p>
                    <p class="text-[11px] font-semibold text-stone-400">{{ __('common.app_tagline') }}</p>
                </div>
            </div>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-200">{{ __('common.footer_about') }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="border border-white/20 px-3 py-1.5 text-xs font-semibold">{{ __('common.cod') }}</span>
                <span class="border border-white/20 px-3 py-1.5 text-xs font-semibold">{{ __('common.demo_gateway') }}</span>
            </div>
        </div>
        <div>
            <h2 class="text-sm font-bold text-white">{{ __('common.footer_shop') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ url('/shop') }}" class="text-brand-200 hover:text-white">{{ __('common.all_products') }}</a></li>
                <li><a href="{{ url('/cart') }}" class="text-brand-200 hover:text-white">{{ __('common.cart') }}</a></li>
                <li><a href="{{ url('/account/orders') }}" class="text-brand-200 hover:text-white">{{ __('common.my_orders') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold text-white">{{ __('common.footer_help') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('pages.about') }}" class="text-brand-200 hover:text-white">{{ __('pages.about.title') }}</a></li>
                <li><a href="{{ route('pages.contact') }}" class="text-brand-200 hover:text-white">{{ __('pages.contact.title') }}</a></li>
                <li><a href="{{ url('/account') }}" class="text-brand-200 hover:text-white">{{ __('common.my_account') }}</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-bold text-white">{{ __('pages.shipping.title') }}</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('pages.shipping') }}" class="text-brand-200 hover:text-white">{{ __('pages.shipping.title') }}</a></li>
                <li><a href="{{ route('pages.size-guide') }}" class="text-brand-200 hover:text-white">{{ __('pages.size_guide.title') }}</a></li>
                <li><a href="{{ route('pages.privacy') }}" class="text-brand-200 hover:text-white">{{ __('pages.privacy.title') }}</a></li>
                <li><a href="{{ route('pages.terms') }}" class="text-brand-200 hover:text-white">{{ __('pages.terms.title') }}</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col items-center justify-between gap-2 py-5 text-xs text-brand-300 sm:flex-row">
            <p>© {{ date('Y') }} {{ __('common.app_name') }}. {{ __('common.rights') }}</p>
            <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span>{{ __('common.credit_label') }} <a href="https://fedaamohamed.com" rel="noopener" class="font-semibold text-brand-100 underline-offset-4 hover:text-white hover:underline">{{ __('common.owner_name') }}</a></span>
                <span class="text-brand-400">{{ __('common.demo_footer_note') }}</span>
            </p>
        </div>
    </div>
</footer>

@if ($cartCount > 0 && ! request()->routeIs(['shop.product', 'cart.*', 'checkout.*', 'orders.*']))
    <a href="{{ url('/cart') }}"
       class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t-2 border-accent-500 bg-brand-950 px-4 py-3 text-white shadow-[0_-4px_16px_rgba(0,0,0,0.18)] md:hidden">
        <span class="flex items-center gap-2 text-sm font-bold">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h2l2.2 10.2a2 2 0 0 0 2 1.6h6.9a2 2 0 0 0 2-1.6L21 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
            {{ __('cart.title') }} · {{ trans_choice('common.products_count', $cartCount, ['count' => $cartCount]) }}
        </span>
        <span class="flex items-center gap-2">
            @if ($cartSubtotalCents > 0)
                <span class="text-sm font-extrabold num" dir="ltr">{{ \App\Support\Money::withCurrency($cartSubtotalCents) }}</span>
            @endif
            <span class="border border-white/30 px-3 py-1.5 text-xs font-bold">{{ __('common.view_all') }}</span>
        </span>
    </a>
@endif
</body>
</html>
