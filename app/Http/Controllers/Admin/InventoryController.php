<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustStockRequest;
use App\Models\ProductVariant;
use App\Services\InsufficientStockException;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $variants = ProductVariant::query()
            ->with('product')
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('sku', 'like', $like)
                        ->orWhereHas('product', fn (Builder $product) => $product
                            ->where('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like));
                });
            })
            ->when($request->boolean('low_stock'), fn (Builder $query) => $query->lowStock())
            ->when($request->boolean('out_of_stock'), fn (Builder $query) => $query->whereRaw('(stock - reserved) <= 0'))
            ->orderByRaw('(stock - reserved) <= low_stock_threshold desc')
            ->orderBy('product_id')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'variants' => ProductVariant::count(),
            'low_stock' => ProductVariant::lowStock()->count(),
            'out_of_stock' => ProductVariant::whereRaw('(stock - reserved) <= 0')->count(),
            'reserved' => (int) ProductVariant::sum('reserved'),
        ];

        return view('admin.inventory.index', [
            'variants' => $variants,
            'totals' => $totals,
            'search' => $search,
            'lowStockAlert' => $totals['low_stock'],
        ]);
    }

    public function show(Request $request, ProductVariant $variant): View
    {
        $variant->load('product');

        $movements = $variant->movements()
            ->with('user')
            ->paginate(25);

        return view('admin.inventory.show', [
            'variant' => $variant,
            'movements' => $movements,
        ]);
    }

    public function adjust(AdjustStockRequest $request, ProductVariant $variant, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (int) $data['quantity'];

        try {
            match ($data['type']) {
                'in' => $quantity >= 1
                    ? $inventory->stockIn($variant, $quantity, $request->user(), $data['reason'] ?? null)
                    : throw new RuntimeException(__('inventory.errors.positive')),
                'out' => $quantity >= 1
                    ? $inventory->stockOut($variant, $quantity, $request->user(), $data['reason'] ?? null)
                    : throw new RuntimeException(__('inventory.errors.positive')),
                'adjust' => $inventory->adjustTo($variant, $quantity, $request->user(), $data['reason'] ?? null),
            };
        } catch (InsufficientStockException $exception) {
            return back()->with('error', __('inventory.errors.insufficient', [
                'available' => max(0, $exception->available),
            ]));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', __('inventory.adjusted', ['sku' => $variant->sku]));
    }
}
