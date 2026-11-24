@extends('layouts.admin')

@section('title', $product->name_ar)
@section('page_title', $product->name_ar)
@section('page_subtitle', __('admin.products.edit_subtitle'))

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-brand-800">
            <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a1 1 0 0 1-1 1H7.4l3.3 3.3a1 1 0 0 1-1.4 1.4l-5-5a1 1 0 0 1 0-1.4l5-5a1 1 0 0 1 1.4 1.4L7.4 9H16a1 1 0 0 1 1 1Z" clip-rule="evenodd"/></svg>
            {{ __('common.back') }}
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('shop.product', $product->slug) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">{{ __('admin.products.preview') }}</a>
            @if (Route::has('admin.inventory.index'))
                <a href="{{ route('admin.inventory.index', ['variant' => $product->variants->first()?->id]) }}" class="btn-outline btn-sm">{{ __('admin.nav_inventory') }}</a>
            @endif
        </div>
    </div>

    @include('admin.products._form')

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
        <div class="card p-5 sm:p-6">
            <h2 class="font-bold text-slate-900">{{ __('admin.products.existing_images') }}</h2>
            @if ($product->images->isEmpty())
                <p class="mt-3 rounded-none bg-slate-50 p-4 text-sm text-slate-500">{{ __('admin.products.no_images') }}</p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($product->images as $image)
                        <div class="group relative overflow-hidden rounded-none border border-slate-200">
                            <img src="{{ $image->url }}" alt="{{ $image->{'alt_'.$locale} }}" class="aspect-square w-full object-cover">
                            @can('products.manage')
                                <form method="POST" action="{{ route('admin.images.destroy', [$product, $image]) }}" class="absolute end-1.5 top-1.5" x-data="confirmForm" data-message="{{ __('admin.products.confirm_image_delete') }}" @submit="onConfirm($event)">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-full bg-red-600 text-white shadow-sm hover:bg-red-700" aria-label="{{ __('common.delete') }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5 5 6.3 8.7 10 5 13.7 6.3 15 10 11.3 13.7 15 15 13.7 11.3 10 15 6.3 13.7 5 10 8.7 6.3 5Z"/></svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif

            @can('products.manage')
                <form method="POST" action="{{ route('admin.images.store', $product) }}" enctype="multipart/form-data" class="mt-4 border-t border-slate-100 pt-4">
                    @csrf
                    <label for="extra_images" class="label">{{ __('admin.products.add_images') }}</label>
                    <input id="extra_images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="input file:me-3 file:rounded-none file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
                    <button type="submit" class="btn-outline btn-sm mt-2">{{ __('admin.products.upload_images') }}</button>
                </form>
            @endcan
        </div>

        <div class="space-y-6">
            <div class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">{{ __('admin.variants.title') }}</h2>
                    <span class="badge-neutral">{{ $product->variants->count() }}</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($product->variants as $variant)
                        <details class="rounded-none border border-slate-200 bg-slate-50/50 p-4" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2 text-sm">
                                <div class="flex items-center gap-2">
                                    @if ($variant->color_hex)
                                        <span class="h-3.5 w-3.5 rounded-full border border-black/10" style="background-color: {{ $variant->color_hex }}"></span>
                                    @endif
                                    <span class="font-bold text-slate-800" dir="ltr">{{ $variant->sku }}</span>
                                    <span class="text-slate-500">{{ $variant->label !== $variant->sku ? $variant->label : '' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="badge-{{ $variant->available_stock <= 0 ? 'danger' : ($variant->isLowStock() ? 'warning' : 'success') }}">
                                        {{ __('admin.variants.available_short', ['count' => $variant->available_stock]) }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-600" dir="ltr">{{ \App\Support\Money::withCurrency($variant->price_cents) }}</span>
                                </div>
                            </summary>

                            @can('products.manage')
                                <form method="POST" action="{{ route('admin.variants.update', [$product, $variant]) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="label text-xs" for="sku-{{ $variant->id }}">{{ __('fields.sku') }}</label>
                                        <input id="sku-{{ $variant->id }}" name="sku" value="{{ $variant->sku }}" required dir="ltr" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="price-{{ $variant->id }}">{{ __('fields.price') }}</label>
                                        <input id="price-{{ $variant->id }}" name="price" type="number" step="0.01" min="0" value="{{ \App\Support\Money::toMajor($variant->price_cents) }}" required dir="ltr" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="size-{{ $variant->id }}">{{ __('fields.size') }}</label>
                                        <input id="size-{{ $variant->id }}" name="size" value="{{ $variant->size }}" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="color-{{ $variant->id }}">{{ __('fields.color') }}</label>
                                        <input id="color-{{ $variant->id }}" name="color" value="{{ $variant->color }}" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="hex-{{ $variant->id }}">{{ __('fields.color_hex') }}</label>
                                        <input id="hex-{{ $variant->id }}" name="color_hex" value="{{ $variant->color_hex }}" dir="ltr" class="input h-10 py-1.5 text-sm" placeholder="#1E3A8A">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="threshold-{{ $variant->id }}">{{ __('fields.low_stock_threshold') }}</label>
                                        <input id="threshold-{{ $variant->id }}" name="low_stock_threshold" type="number" min="0" value="{{ $variant->low_stock_threshold }}" dir="ltr" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="compare-{{ $variant->id }}">{{ __('fields.compare_at_price') }}</label>
                                        <input id="compare-{{ $variant->id }}" name="compare_at_price" type="number" step="0.01" min="0" value="{{ $variant->compare_at_cents ? \App\Support\Money::toMajor($variant->compare_at_cents) : '' }}" dir="ltr" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="label text-xs" for="position-{{ $variant->id }}">{{ __('fields.position') }}</label>
                                        <input id="position-{{ $variant->id }}" name="position" type="number" min="0" value="{{ $variant->position }}" dir="ltr" class="input h-10 py-1.5 text-sm">
                                    </div>
                                    <div class="flex items-center gap-4 sm:col-span-2">
                                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" @checked($variant->is_active) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                                            {{ __('common.active') }}
                                        </label>
                                        <button type="submit" class="btn-primary btn-sm">{{ __('common.save') }}</button>
                                        <button type="submit" form="delete-variant-{{ $variant->id }}" class="btn-danger btn-sm ms-auto" x-data="confirmForm" data-message="{{ __('admin.variants.confirm_delete') }}" @click="onConfirm($event)">{{ __('common.delete') }}</button>
                                    </div>
                                </form>
                                <form id="delete-variant-{{ $variant->id }}" method="POST" action="{{ route('admin.variants.destroy', [$product, $variant]) }}">
                                    @csrf @method('DELETE')
                                </form>
                            @endcan
                        </details>
                    @endforeach
                </div>
            </div>

            @can('products.manage')
                <div class="card p-5 sm:p-6">
                    <h2 class="font-bold text-slate-900">{{ __('admin.variants.add') }}</h2>
                    <form method="POST" action="{{ route('admin.variants.store', $product) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="new_sku" class="label text-xs">{{ __('fields.sku') }} *</label>
                            <input id="new_sku" name="sku" required dir="ltr" value="{{ old('sku') }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_price" class="label text-xs">{{ __('fields.price') }} *</label>
                            <input id="new_price" name="price" type="number" step="0.01" min="0" required dir="ltr" value="{{ old('price') }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_size" class="label text-xs">{{ __('fields.size') }}</label>
                            <input id="new_size" name="size" value="{{ old('size') }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_color" class="label text-xs">{{ __('fields.color') }}</label>
                            <input id="new_color" name="color" value="{{ old('color') }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_stock" class="label text-xs">{{ __('fields.stock') }} *</label>
                            <input id="new_stock" name="stock" type="number" min="0" required dir="ltr" value="{{ old('stock', 0) }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_threshold" class="label text-xs">{{ __('fields.low_stock_threshold') }}</label>
                            <input id="new_threshold" name="low_stock_threshold" type="number" min="0" dir="ltr" value="{{ old('low_stock_threshold', config('matjar.inventory.low_stock_threshold')) }}" class="input h-10 py-1.5 text-sm">
                        </div>
                        <div>
                            <label for="new_hex" class="label text-xs">{{ __('fields.color_hex') }}</label>
                            <input id="new_hex" name="color_hex" dir="ltr" value="{{ old('color_hex') }}" class="input h-10 py-1.5 text-sm" placeholder="#1E3A8A">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="btn-primary btn-sm w-full">{{ __('admin.variants.add') }}</button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    @can('products.manage')
        <div class="mt-8 rounded-none border border-red-200 bg-red-50 p-5">
            <h2 class="font-bold text-red-900">{{ __('admin.products.danger_zone') }}</h2>
            <p class="mt-1 text-sm text-red-800">{{ __('admin.products.delete_hint') }}</p>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-3" x-data="confirmForm" data-message="{{ __('admin.products.confirm_delete') }}" @submit="onConfirm($event)">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">{{ __('admin.products.delete_product') }}</button>
            </form>
        </div>
    @endcan
@endsection
