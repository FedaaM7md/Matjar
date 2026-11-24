{{-- Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary. --}}
@php
    $storeName = \App\Models\Setting::get('store_name_'.$locale, __('common.app_name'));
    $user = auth()->user();
    $demoEnabled = (bool) config('matjar.demo.enabled');

    $navGroups = [
        'main' => [
            ['route' => 'admin.dashboard', 'permission' => 'dashboard.view', 'icon' => 'dashboard', 'label' => 'admin.nav_dashboard'],
        ],
        'commerce' => [
            ['route' => 'admin.products.index', 'permission' => 'products.view', 'icon' => 'products', 'label' => 'admin.nav_products'],
            ['route' => 'admin.categories.index', 'permission' => 'products.view', 'icon' => 'inventory', 'label' => 'admin.nav_categories'],
            ['route' => 'admin.inventory.index', 'permission' => 'inventory.view', 'icon' => 'inventory', 'label' => 'admin.nav_inventory'],
            ['route' => 'admin.orders.index', 'permission' => 'orders.view', 'icon' => 'orders', 'label' => 'admin.nav_orders'],
            ['route' => 'admin.customers.index', 'permission' => 'customers.view', 'icon' => 'customers', 'label' => 'admin.nav_customers'],
        ],
        'growth' => [
            ['route' => 'admin.coupons.index', 'permission' => 'coupons.view', 'icon' => 'coupons', 'label' => 'admin.nav_coupons'],
            ['route' => 'admin.shipping.index', 'permission' => 'shipping.view', 'icon' => 'shipping', 'label' => 'admin.nav_shipping'],
            ['route' => 'admin.reports.index', 'permission' => 'reports.view', 'icon' => 'reports', 'label' => 'admin.nav_reports'],
            ['route' => 'admin.settings.edit', 'permission' => 'settings.view', 'icon' => 'settings', 'label' => 'admin.nav_settings'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#111111">
    <title>@yield('title', __('admin.dashboard_title')) — {{ __('common.app_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper" x-data="adminShell">
<a href="#admin-main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:border focus:border-brand-900 focus:bg-white focus:px-4 focus:py-2 focus:text-brand-950 focus:shadow-lg">
    {{ __('common.skip_to_content') }}
</a>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-brand-950/60 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

<aside id="admin-sidebar" class="admin-sidebar fixed inset-y-0 start-0 z-50 flex w-64 flex-col bg-brand-950 transition-transform duration-200"
       :class="{ 'is-open': sidebarOpen }"
       :inert="!sidebarOpen && !desktop ? true : null"
       :aria-hidden="!sidebarOpen && !desktop ? 'true' : 'false'">
    <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
        <x-logo class="h-9 w-9 text-white" />
        <div class="min-w-0">
            <p class="truncate font-display text-sm font-extrabold text-white">{{ __('common.app_name') }}</p>
            <p class="text-[10px] font-semibold text-accent-300">{{ __('admin.panel') }}</p>
        </div>
        <button type="button" class="ms-auto text-white/70 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="{{ __('common.close') }}">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="{{ __('admin.nav_label') }}">
        @foreach ($navGroups as $group => $items)
            @php
                $visibleItems = collect($items)->filter(fn ($item) => Route::has($item['route']) && $user->can($item['permission']));
            @endphp
            @if ($visibleItems->isNotEmpty())
                <div>
                    <p class="px-3 pb-2 text-[10px] font-bold text-brand-400">{{ __('admin.nav_group_'.$group) }}</p>
                    <div class="space-y-0.5">
                        @foreach ($visibleItems as $item)
                            <a href="{{ route($item['route']) }}"
                               class="admin-nav-link {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'admin-nav-link-active' : '' }}">
                                <x-admin.nav-icon :name="$item['icon']" />
                                <span>{{ __($item['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        <a href="{{ route('home') }}" class="admin-nav-link">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 11 12 4l8 7v9H4v-9Z" stroke-linejoin="round"/><path d="M9 20v-6h6v6" stroke-linejoin="round"/></svg>
            <span>{{ __('admin.view_store') }}</span>
        </a>
        <div class="mt-2 flex items-center gap-3 border border-white/10 bg-white/5 p-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center bg-accent-500 text-sm font-extrabold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white">{{ $user->name }}</p>
                <p class="truncate text-[11px] text-brand-300">{{ $user->role?->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="text-brand-300 hover:text-white" title="{{ __('common.logout') }}" aria-label="{{ __('common.logout') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 8V5H5v14h9v-3M10 12h11m0 0-3-3m3 3-3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="lg:ps-64">
    @if ($demoEnabled)
        <div class="border-b border-amber-200 bg-amber-50">
            <div class="flex items-center justify-center gap-2 px-4 py-1.5 text-center text-[11px] font-semibold text-amber-900 sm:text-xs">
                {{ __('demo.banner') }}
            </div>
        </div>
    @endif
    <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-stone-200 bg-white px-4 sm:px-6">
        <button type="button" class="btn-ghost -ms-2 px-2 lg:hidden" @click="sidebarOpen = true" aria-label="{{ __('common.open_menu') }}">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
        </button>
        <div class="min-w-0">
            <p class="truncate font-display text-sm font-extrabold text-brand-950">@yield('page_title', __('admin.dashboard_title'))</p>
            <p class="hidden truncate text-xs text-stone-500 sm:block">@yield('page_subtitle', __('admin.panel_subtitle'))</p>
        </div>
        <div class="ms-auto flex items-center gap-2">
            <span class="badge-neutral hidden sm:inline-flex">{{ $user->role?->name }}</span>
            <form method="POST" action="{{ route('locale.update', $otherLocale) }}">
                @csrf
                <button type="submit" class="btn-outline btn-sm">{{ config('matjar.locales.'.$otherLocale.'.name') }}</button>
            </form>
        </div>
    </header>

    <main id="admin-main" class="p-4 sm:p-6 lg:p-8">
        <x-flash />
        @yield('content')
    </main>
</div>
</body>
</html>
