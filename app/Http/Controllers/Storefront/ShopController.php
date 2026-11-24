<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $category);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'images',
            'variants' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
        ]);

        $variants = $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'label' => $variant->label,
            'size' => $variant->size,
            'color' => $variant->color,
            'color_hex' => $variant->color_hex,
            'sku' => $variant->sku,
            'price' => $variant->price_cents,
            'price_formatted' => Money::withCurrency($variant->price_cents),
            'compare_at' => $variant->compare_at_cents,
            'compare_at_formatted' => $variant->compare_at_cents ? Money::withCurrency($variant->compare_at_cents) : null,
            'available' => $variant->available_stock,
            'threshold' => $variant->low_stock_threshold,
        ])->values();

        $related = Product::query()
            ->active()
            ->whereKeyNot($product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->with(['category', 'images', 'variants' => fn ($query) => $query->where('is_active', true)])
            ->withMin(['variants as min_price_cents' => fn ($query) => $query->where('is_active', true)], 'price_cents')
            ->withExists(['variants as in_stock' => fn ($query) => $query->where('is_active', true)->whereColumn('stock', '>', 'reserved')])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('shop.show', [
            'product' => $product,
            'variants' => $variants,
            'related' => $related,
        ]);
    }

    private function listing(Request $request, ?Category $category): View
    {
        $search = trim((string) $request->query('q'));
        $sort = (string) $request->query('sort', 'featured');
        $min = is_numeric($request->query('min_price')) ? Money::toMinor($request->query('min_price')) : null;
        $max = is_numeric($request->query('max_price')) ? Money::toMinor($request->query('max_price')) : null;
        $inStockOnly = $request->boolean('in_stock');

        $query = Product::query()
            ->active()
            ->with([
                'category',
                'images',
                'variants' => fn ($variant) => $variant->where('is_active', true)->orderBy('position'),
            ])
            ->withMin(['variants as min_price_cents' => fn ($variant) => $variant->where('is_active', true)], 'price_cents')
            ->withExists(['variants as in_stock' => fn ($variant) => $variant->where('is_active', true)->whereColumn('stock', '>', 'reserved')])
            ->search($search);

        if ($category) {
            $query->where('category_id', $category->id);
        }

        if ($request->filled('category') && ! $category) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $request->query('category')));
        }

        if ($min !== null) {
            $query->whereHas('variants', fn (Builder $q) => $q->where('is_active', true)->where('price_cents', '>=', $min));
        }

        if ($max !== null) {
            $query->whereHas('variants', fn (Builder $q) => $q->where('is_active', true)->where('price_cents', '<=', $max));
        }

        if ($inStockOnly) {
            $query->whereHas('variants', fn (Builder $q) => $q->where('is_active', true)->whereColumn('stock', '>', 'reserved'));
        }

        match ($sort) {
            'price_asc' => $query->orderBy('min_price_cents')->orderBy('id'),
            'price_desc' => $query->orderByDesc('min_price_cents')->orderByDesc('id'),
            'newest' => $query->latest('id'),
            'name' => $query->orderBy('name_'.app()->getLocale()),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        return view('shop.index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()->active()->withCount([
                'products' => fn (Builder $q) => $q->where('is_active', true),
            ])->orderBy('position')->orderBy('name_ar')->get(),
            'activeCategory' => $category,
            'filters' => [
                'q' => $search,
                'category' => $category?->slug ?? (string) $request->query('category'),
                'min_price' => $request->query('min_price'),
                'max_price' => $request->query('max_price'),
                'in_stock' => $inStockOnly,
                'sort' => $sort,
            ],
        ]);
    }
}
