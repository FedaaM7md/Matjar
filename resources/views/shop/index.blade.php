@extends('layouts.app')

@section('title', $activeCategory ? $activeCategory->name : __('shop.title'))
@section('meta_description', $activeCategory?->description ?: __('shop.meta_description'))

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="container-page py-6 sm:py-8">
            <nav aria-label="breadcrumb" class="text-xs font-semibold text-slate-500">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-900">{{ __('common.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('shop.index') }}" class="hover:text-brand-900">{{ __('common.shop') }}</a></li>
                    @if ($activeCategory)
                        <li aria-hidden="true">/</li>
                        <li class="text-slate-800">{{ $activeCategory->name }}</li>
                    @endif
                </ol>
            </nav>
            <div class="mt-3">
                <p class="eyebrow">{{ __('shop.browse_eyebrow') }}</p>
                <h1 class="mt-2 text-2xl font-extrabold text-brand-950 sm:text-3xl">
                    {{ $activeCategory ? $activeCategory->name : __('shop.title') }}
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">
                    {{ $activeCategory && $activeCategory->description ? $activeCategory->description : __('shop.subtitle') }}
                </p>
            </div>
        </div>
    </div>

    <div class="container-page py-6 lg:py-8">
        <form method="GET" action="{{ $activeCategory ? route('shop.category', $activeCategory->slug) : route('shop.index') }}" x-data="{ filtersOpen: false }">
            {{-- Sticky mobile/tablet filter & sort trigger --}}
            <div class="sticky top-16 z-30 -mx-4 mb-4 flex items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 py-2 backdrop-blur sm:top-20 lg:static lg:mx-0 lg:mb-0 lg:border-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-none">
                <p class="text-xs font-bold text-slate-600">{{ trans_choice('shop.results_count', $products->total(), ['count' => $products->total()]) }}</p>
                <button type="button" class="btn-outline min-h-11 gap-2 px-4 lg:hidden" @click="filtersOpen = true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
                    {{ __('shop.filter_sort') }}
                </button>
            </div>

            <div class="lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8">
                {{-- Desktop sidebar --}}
                <aside class="hidden lg:block">
                    @include('shop._filters', ['prefix' => 'd', 'filters' => $filters, 'categories' => $categories, 'activeCategory' => $activeCategory])
                </aside>

                {{-- Mobile / tablet bottom sheet --}}
                <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('shop.filters_title') }}">
                    <div class="absolute inset-0 bg-brand-950/50" @click="filtersOpen = false"></div>
                    <div class="absolute inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto border-t-2 border-brand-900 bg-white p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-extrabold text-brand-950">{{ __('shop.filters_title') }}</h2>
                            <button type="button" class="btn-ghost min-h-11 min-w-11 px-2" @click="filtersOpen = false" aria-label="{{ __('common.close') }}">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
                            </button>
                        </div>
                        @include('shop._filters', ['prefix' => 'm', 'filters' => $filters, 'categories' => $categories, 'activeCategory' => $activeCategory])
                        <button type="submit" class="btn-primary mt-4 w-full min-h-11">{{ __('shop.show_results') }}</button>
                    </div>
                </div>

                {{-- Results --}}
                <div class="min-w-0">
                    <p class="mb-4 hidden text-sm text-slate-500 lg:block">{{ __('shop.showing', ['from' => $products->firstItem() ?? 0, 'to' => $products->lastItem() ?? 0, 'total' => $products->total()]) }}</p>

                    @if ($products->isEmpty())
                        <div class="card flex flex-col items-center justify-center px-6 py-16 text-center">
                            <span class="flex h-14 w-14 items-center justify-center border border-slate-200 bg-paper text-slate-400">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2" stroke-linecap="round"/></svg>
                            </span>
                            <h2 class="mt-4 text-lg font-bold text-slate-900">{{ __('shop.empty_title') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('shop.empty_body') }}</p>
                            <a href="{{ $activeCategory ? route('shop.category', $activeCategory->slug) : route('shop.index') }}" class="btn-outline mt-5 min-h-11">{{ __('shop.clear_filters') }}</a>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-2 xl:grid-cols-3">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection
