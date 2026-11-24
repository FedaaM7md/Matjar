@extends('layouts.admin')

@section('title', __('admin.products.title'))
@section('page_title', __('admin.products.title'))
@section('page_subtitle', __('admin.products.subtitle'))

@section('content')
    <div class="mb-5" x-data="{ open: false }">
        <div class="flex items-center gap-2 md:hidden">
            <button type="button" class="btn-outline min-h-11 flex-1 gap-2" @click="open = !open">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke-linecap="round"/></svg>
                {{ __('admin.filters') }}
            </button>
            @can('products.manage')
                <a href="{{ route('admin.products.create') }}" class="btn-primary min-h-11">{{ __('admin.products.add') }}</a>
            @endcan
        </div>

        <div class="md:flex md:items-end md:justify-between md:gap-3">
            <form method="GET" action="{{ route('admin.products.index') }}" x-show="open" x-cloak class="mt-3 flex flex-wrap items-end gap-2 md:mt-0 md:!flex">
                <div class="min-w-0 flex-1 sm:flex-none">
                    <label for="q" class="label text-xs">{{ __('common.search') }}</label>
                    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="{{ __('admin.products.search_placeholder') }}" class="input h-11 w-full sm:w-56">
                </div>
                <div>
                    <label for="category" class="label text-xs">{{ __('fields.category') }}</label>
                    <select id="category" name="category" class="input h-11 w-40 py-1.5">
                        <option value="">{{ __('admin.products.all_categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="label text-xs">{{ __('common.status') }}</label>
                    <select id="status" name="status" class="input h-11 w-32 py-1.5">
                        <option value="">{{ __('admin.products.all_statuses') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('common.active') }}</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary h-11">{{ __('common.apply') }}</button>
                @if ($search !== '' || request()->filled('category') || request()->filled('status'))
                    <a href="{{ route('admin.products.index') }}" class="btn-outline h-11">{{ __('common.reset') }}</a>
                @endif
            </form>

            @can('products.manage')
                <a href="{{ route('admin.products.create') }}" class="btn-primary hidden md:inline-flex">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                    {{ __('admin.products.add') }}
                </a>
            @endcan
        </div>
    </div>

    {{-- Mobile: stacked cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($products as $product)
            <div class="card flex gap-3 p-3">
                <div class="h-16 w-16 shrink-0 overflow-hidden border border-slate-200">
                    @if ($product->primary_image_path)
                        <img src="{{ asset('storage/'.$product->primary_image_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        @include('products.illustration', ['category' => $product->category?->slug, 'color' => $product->variants->first()?->color_hex, 'class' => 'h-full w-full'])
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-slate-900">{{ $product->name_ar }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $product->name_en }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-accent-600">{{ $product->category?->name ?? '—' }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        @if ($product->is_active)
                            <span class="badge-success">{{ __('common.active') }}</span>
                        @else
                            <span class="badge-neutral">{{ __('common.inactive') }}</span>
                        @endif
                        <span class="font-semibold text-slate-700" dir="ltr">{{ $product->min_price ? \App\Support\Money::withCurrency((int) $product->min_price) : '—' }}</span>
                        <span class="text-slate-500">{{ $product->variants_count }} {{ __('admin.products.table_variants') }} · {{ (int) $product->total_stock }} {{ __('fields.stock') }}</span>
                    </div>
                    @can('products.manage')
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn-outline btn-sm mt-2 min-h-11">{{ __('common.edit') }}</a>
                    @endcan
                </div>
            </div>
        @empty
            <p class="card p-6 text-center text-sm text-slate-500">{{ __('admin.products.empty') }}</p>
        @endforelse
    </div>

    {{-- Desktop: table --}}
    <div class="table-wrap hidden md:block">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('admin.products.table_product') }}</th>
                    <th>{{ __('fields.category') }}</th>
                    <th class="text-center">{{ __('admin.products.table_variants') }}</th>
                    <th class="text-center">{{ __('fields.stock') }}</th>
                    <th>{{ __('admin.products.table_price') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($product->primary_image_path)
                                    <img src="{{ asset('storage/'.$product->primary_image_path) }}" alt="" class="h-11 w-11 rounded-none object-cover">
                                @else
                                    <span class="block h-11 w-11 overflow-hidden">
                                        @include('products.illustration', ['category' => $product->category?->slug, 'color' => $product->variants->first()?->color_hex])
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="max-w-72 truncate font-bold text-slate-900">{{ $product->name_ar }}</p>
                                    <p class="max-w-72 truncate text-xs text-slate-500">{{ $product->name_en }}</p>
                                    <p class="text-[11px] text-slate-400" dir="ltr">{{ $product->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                        <td class="text-center font-semibold">{{ $product->variants_count }}</td>
                        <td class="text-center">
                            <span class="font-semibold text-slate-800">{{ (int) $product->total_stock }}</span>
                            @if ((int) $product->total_reserved > 0)
                                <span class="block text-[11px] text-amber-700">{{ __('admin.products.reserved', ['count' => (int) $product->total_reserved]) }}</span>
                            @endif
                        </td>
                        <td class="text-sm font-semibold" dir="ltr">
                            {{ $product->min_price ? \App\Support\Money::withCurrency((int) $product->min_price) : '—' }}
                        </td>
                        <td>
                            @if ($product->is_active)
                                <span class="badge-success">{{ __('common.active') }}</span>
                            @else
                                <span class="badge-neutral">{{ __('common.inactive') }}</span>
                            @endif
                            @if ($product->is_featured)
                                <span class="badge-info mt-1">{{ __('shop.featured') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @can('products.manage')
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn-outline btn-sm">{{ __('common.edit') }}</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('admin.products.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $products->links() }}</div>
@endsection
