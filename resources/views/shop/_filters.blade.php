{{-- Shared storefront filter fields. Rendered twice (desktop sidebar + mobile
     sheet) so the controls keep working without JavaScript positioning tricks. --}}
@php
    $pid = $prefix ?? 'f';
    $resetUrl = $activeCategory ? route('shop.category', $activeCategory->slug) : route('shop.index');
@endphp

<div class="space-y-4">
    <div class="card p-4">
        <label for="{{ $pid }}-q" class="text-sm font-bold text-slate-900">{{ __('common.search') }}</label>
        <div class="relative mt-2">
            <input id="{{ $pid }}-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('common.search_placeholder') }}" class="input pe-9">
        </div>
    </div>

    @unless ($activeCategory)
        <div class="card p-4">
            <label for="{{ $pid }}-category" class="text-sm font-bold text-slate-900">{{ __('shop.category') }}</label>
            <select id="{{ $pid }}-category" name="category" class="input mt-2">
                <option value="">{{ __('shop.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->name }} ({{ $category->products_count }})</option>
                @endforeach
            </select>
        </div>
    @else
        <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
    @endunless

    <div class="card p-4">
        <h2 class="text-sm font-bold text-slate-900">{{ __('shop.price_range') }}</h2>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <div>
                <label for="{{ $pid }}-min" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('shop.from') }}</label>
                <input id="{{ $pid }}-min" type="number" inputmode="decimal" min="0" step="0.01" name="min_price" value="{{ $filters['min_price'] }}" class="input px-2" placeholder="0">
            </div>
            <div>
                <label for="{{ $pid }}-max" class="mb-1 block text-[11px] font-semibold text-slate-500">{{ __('shop.to') }}</label>
                <input id="{{ $pid }}-max" type="number" inputmode="decimal" min="0" step="0.01" name="max_price" value="{{ $filters['max_price'] }}" class="input px-2" placeholder="5000">
            </div>
        </div>
        <p class="mt-1.5 text-[11px] text-slate-500">{{ __('shop.price_currency_hint') }}</p>
    </div>

    <div class="card p-4">
        <label class="flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock']) class="h-5 w-5 rounded-none border-slate-300 text-brand-900 focus:ring-brand-900">
            {{ __('shop.in_stock_only') }}
        </label>
    </div>

    <div class="card p-4">
        <label for="{{ $pid }}-sort" class="text-sm font-bold text-slate-900">{{ __('shop.sort_by') }}</label>
        <select id="{{ $pid }}-sort" name="sort" class="input mt-2">
            <option value="featured" @selected($filters['sort'] === 'featured')>{{ __('shop.sort_featured') }}</option>
            <option value="newest" @selected($filters['sort'] === 'newest')>{{ __('shop.sort_newest') }}</option>
            <option value="price_asc" @selected($filters['sort'] === 'price_asc')>{{ __('shop.sort_price_asc') }}</option>
            <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('shop.sort_price_desc') }}</option>
            <option value="name" @selected($filters['sort'] === 'name')>{{ __('shop.sort_name') }}</option>
        </select>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="btn-primary min-h-11 flex-1">{{ __('common.apply') }}</button>
        <a href="{{ $resetUrl }}" class="btn-outline min-h-11">{{ __('common.reset') }}</a>
    </div>
</div>
