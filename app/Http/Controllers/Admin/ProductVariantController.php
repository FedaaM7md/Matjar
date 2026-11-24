<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VariantRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    public function store(VariantRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        DB::transaction(function () use ($request, $product, $inventory) {
            $data = $request->variantData();
            $initialStock = (int) ($request->input('stock') ?? 0);

            $variant = $product->variants()->create([
                ...$data,
                'stock' => 0,
                'position' => $data['position'] ?: ((int) $product->variants()->max('position')) + 1,
            ]);

            if ($initialStock > 0) {
                $inventory->stockIn($variant, $initialStock, $request->user(), 'initial_stock');
            }
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('status', __('admin.variants.created'));
    }

    public function update(VariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $variant->update($request->variantData());

        return redirect()->route('admin.products.edit', $product)
            ->with('status', __('admin.variants.updated'));
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        if ($variant->reserved > 0) {
            return back()->with('error', __('admin.variants.has_reservations'));
        }

        if (OrderItem::query()->where('variant_id', $variant->id)->exists()) {
            return back()->with('error', __('admin.variants.has_orders'));
        }

        if ($product->variants()->count() <= 1) {
            return back()->with('error', __('admin.variants.last_variant'));
        }

        $variant->delete();

        return redirect()->route('admin.products.edit', $product)
            ->with('status', __('admin.variants.deleted'));
    }
}
