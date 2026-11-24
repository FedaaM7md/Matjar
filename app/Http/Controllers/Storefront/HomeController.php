<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $homeCategories = Category::query()
            ->active()
            ->withCount(['products' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('position')
            ->orderBy('name_ar')
            ->limit(4)
            ->get();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with([
                'category',
                'images',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            ])
            ->withMin(['variants as min_price_cents' => fn ($query) => $query->where('is_active', true)], 'price_cents')
            ->withExists(['variants as in_stock' => fn ($query) => $query->where('is_active', true)->whereColumn('stock', '>', 'reserved')])
            ->latest('id')
            ->limit(4)
            ->get();

        return view('home', [
            'homeCategories' => $homeCategories,
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
