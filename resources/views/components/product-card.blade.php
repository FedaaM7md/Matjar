@props(['product'])

@php
    $primaryVariant = $product->variants->firstWhere('is_active', true) ?? $product->variants->first();
    $price = $product->min_price_cents ?? $primaryVariant?->price_cents ?? 0;
    $compareAt = $product->compare_at_cents ?? null;
    $inStock = (bool) ($product->in_stock ?? false);
    $image = $product->primary_image_path ?? null;
    $productName = $product->name ?? $product->name_ar;
@endphp

<a href="{{ url('/product/'.$product->slug) }}"
   class="group flex flex-col border border-stone-200 bg-white transition duration-200 hover:border-brand-900">
    <div class="relative aspect-square overflow-hidden bg-paper">
        @if ($image)
            <img src="{{ asset('storage/'.$image) }}" alt="{{ $productName }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            @include('products.illustration', ['category' => $product->category?->slug, 'label' => $productName, 'color' => $primaryVariant?->color_hex, 'class' => 'h-full w-full'])
        @endif

        @unless ($inStock)
            <span class="absolute start-2 top-2 bg-brand-950 px-2 py-1 text-[10px] font-bold text-white sm:start-3 sm:top-3 sm:text-[11px]">{{ __('shop.out_of_stock') }}</span>
        @endunless
        @if ($product->is_featured ?? false)
            <span class="absolute end-2 top-2 bg-accent-500 px-2 py-1 text-[10px] font-bold text-white sm:end-3 sm:top-3 sm:text-[11px]">{{ __('shop.featured') }}</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3 sm:p-4">
        @if ($product->category ?? null)
            <p class="truncate text-[11px] font-bold text-accent-600">{{ $product->category->name }}</p>
        @endif
        <h3 class="mt-1 line-clamp-2 font-display text-sm font-extrabold leading-5 text-brand-950 group-hover:text-accent-600 sm:text-base sm:leading-6">{{ $productName }}</h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-3 sm:pt-4">
            <div class="min-w-0">
                <p class="font-display text-base font-extrabold text-price-600 num sm:text-lg" dir="ltr">{{ \App\Support\Money::withCurrency($price) }}</p>
                @if ($compareAt && $compareAt > $price)
                    <p class="text-xs text-stone-400 line-through num" dir="ltr">{{ \App\Support\Money::withCurrency($compareAt) }}</p>
                @endif
            </div>
            <span class="flex h-8 w-8 shrink-0 items-center justify-center bg-stone-100 text-brand-900 transition group-hover:bg-accent-500 group-hover:text-white sm:h-9 sm:w-9" aria-hidden="true">
                <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 0 1 1-1h9.6l-3.3-3.3a1 1 0 1 1 1.4-1.4l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L13.6 11H4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/></svg>
            </span>
        </div>
    </div>
</a>
