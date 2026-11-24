@php
    $isEdit = $product->exists;
    $action = $isEdit ? route('admin.products.update', $product) : route('admin.products.store');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="card p-5 sm:p-6">
        <h2 class="font-bold text-slate-900">{{ __('admin.products.basic_info') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name_ar" class="label">{{ __('fields.name_ar') }} *</label>
                <input id="name_ar" name="name_ar" type="text" value="{{ old('name_ar', $product->name_ar) }}" required
                       class="input @error('name_ar') input-error @enderror">
                @error('name_ar')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="name_en" class="label">{{ __('fields.name_en') }} *</label>
                <input id="name_en" name="name_en" type="text" value="{{ old('name_en', $product->name_en) }}" required dir="ltr"
                       class="input @error('name_en') input-error @enderror">
                @error('name_en')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="slug" class="label">{{ __('fields.slug') }}</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug', $product->slug) }}" dir="ltr"
                       class="input @error('slug') input-error @enderror" placeholder="auto-from-english-name">
                @error('slug')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="category_id" class="label">{{ __('fields.category') }}</label>
                <select id="category_id" name="category_id" class="input @error('category_id') input-error @enderror">
                    <option value="">{{ __('admin.products.no_category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="brand" class="label">{{ __('fields.brand') }}</label>
                <input id="brand" name="brand" type="text" value="{{ old('brand', $product->brand) }}" class="input">
            </div>
            <div class="flex items-end gap-6 pb-1">
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                    {{ __('common.active') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-600">
                    {{ __('shop.featured') }}
                </label>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="description_ar" class="label">{{ __('fields.description_ar') }}</label>
                <textarea id="description_ar" name="description_ar" rows="5" class="input">{{ old('description_ar', $product->description_ar) }}</textarea>
            </div>
            <div>
                <label for="description_en" class="label">{{ __('fields.description_en') }}</label>
                <textarea id="description_en" name="description_en" rows="5" dir="ltr" class="input">{{ old('description_en', $product->description_en) }}</textarea>
            </div>
        </div>
    </div>

    <div class="card p-5 sm:p-6">
        <h2 class="font-bold text-slate-900">{{ __('fields.images') }}</h2>
        <p class="mt-1 text-xs text-slate-500">{{ __('admin.products.images_hint') }}</p>
        <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="input mt-3 file:me-3 file:rounded-none file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
        @error('images.*')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>

    @unless ($isEdit)
        <div class="card p-5 sm:p-6">
            <h2 class="font-bold text-slate-900">{{ __('admin.variants.first_title') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('admin.variants.first_hint') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label for="variant_sku" class="label">{{ __('fields.sku') }} *</label>
                    <input id="variant_sku" name="variant[sku]" type="text" value="{{ old('variant.sku') }}" required dir="ltr"
                           class="input @error('variant.sku') input-error @enderror" placeholder="FDM-001">
                    @error('variant.sku')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="variant_price" class="label">{{ __('fields.price') }} *</label>
                    <input id="variant_price" name="variant[price]" type="number" step="0.01" min="0" value="{{ old('variant.price') }}" required dir="ltr"
                           class="input @error('variant.price') input-error @enderror">
                    @error('variant.price')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="variant_compare" class="label">{{ __('fields.compare_at_price') }}</label>
                    <input id="variant_compare" name="variant[compare_at_price]" type="number" step="0.01" min="0" value="{{ old('variant.compare_at_price') }}" dir="ltr" class="input">
                </div>
                <div>
                    <label for="variant_stock" class="label">{{ __('fields.stock') }} *</label>
                    <input id="variant_stock" name="variant[stock]" type="number" min="0" value="{{ old('variant.stock', 0) }}" required dir="ltr" class="input">
                </div>
                <div>
                    <label for="variant_threshold" class="label">{{ __('fields.low_stock_threshold') }}</label>
                    <input id="variant_threshold" name="variant[low_stock_threshold]" type="number" min="0" value="{{ old('variant.low_stock_threshold', config('matjar.inventory.low_stock_threshold')) }}" dir="ltr" class="input">
                </div>
                <div>
                    <label for="variant_size" class="label">{{ __('fields.size') }}</label>
                    <input id="variant_size" name="variant[size]" type="text" value="{{ old('variant.size') }}" class="input">
                </div>
                <div>
                    <label for="variant_color" class="label">{{ __('fields.color') }}</label>
                    <input id="variant_color" name="variant[color]" type="text" value="{{ old('variant.color') }}" class="input">
                </div>
                <div>
                    <label for="variant_color_hex" class="label">{{ __('fields.color_hex') }}</label>
                    <input id="variant_color_hex" name="variant[color_hex]" type="text" value="{{ old('variant.color_hex') }}" dir="ltr" class="input" placeholder="#1E3A8A">
                </div>
            </div>
            <p class="mt-3 rounded-none bg-brand-50 p-2.5 text-xs text-brand-900">{{ __('admin.variants.initial_stock_note') }}</p>
        </div>
    @endunless

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="btn-primary">{{ $isEdit ? __('common.save') : __('admin.products.create_product') }}</button>
        <a href="{{ route('admin.products.index') }}" class="btn-ghost">{{ __('common.cancel') }}</a>
    </div>
</form>
