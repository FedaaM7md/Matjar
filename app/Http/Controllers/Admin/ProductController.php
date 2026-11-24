<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\InventoryService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $products = Product::query()
            ->with('category')
            ->withCount(['variants', 'images'])
            ->withSum('variants as total_stock', 'stock')
            ->withSum('variants as total_reserved', 'reserved')
            ->withMin('variants as min_price', 'price_cents')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name_ar')->get(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => Category::orderBy('name_ar')->get(),
            'product' => new Product,
        ]);
    }

    public function store(ProductRequest $request, InventoryService $inventory): RedirectResponse
    {
        $product = DB::transaction(function () use ($request, $inventory) {
            $data = $request->productData();
            $product = Product::create([
                ...$data,
                'slug' => Slug::unique(Product::class, $data['slug'] ?: $data['name_en'], null, 'product'),
            ]);

            $variantData = $request->firstVariantData();
            $variantData['stock'] = 0;
            $variantData['position'] = 0;
            $variant = $product->variants()->create($variantData);

            $initialStock = (int) ($request->input('variant.stock') ?? 0);
            if ($initialStock > 0) {
                $inventory->stockIn($variant, $initialStock, $request->user(), 'initial_stock');
            }

            $this->storeUploadedImages($product, $request->file('images', []));

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('status', __('admin.products.created'));
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants' => fn ($query) => $query->orderBy('position')]);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name_ar')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->productData();

            if ($data['slug']) {
                $data['slug'] = Slug::unique(Product::class, $data['slug'], $product->id, 'product');
            } else {
                unset($data['slug']);
            }

            $product->update($data);

            $this->storeUploadedImages($product, $request->file('images', []));
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('status', __('admin.products.updated'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        if ($product->variants()->where('reserved', '>', 0)->exists()) {
            return back()->with('error', __('admin.products.has_reservations'));
        }

        if (OrderItem::query()->where('product_id', $product->id)->exists()) {
            return back()->with('error', __('admin.products.has_orders'));
        }

        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $product->delete();
        });

        return redirect()->route('admin.products.index')->with('status', __('admin.products.deleted'));
    }

    /** @param array<int, UploadedFile> $files */
    private function storeUploadedImages(Product $product, array $files): void
    {
        $existing = $product->images()->count();
        $position = $existing;

        foreach (array_slice($files, 0, max(0, 8 - $existing)) as $file) {
            $path = $file->store('products/'.$product->id, 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt_ar' => $product->name_ar,
                'alt_en' => $product->name_en,
                'position' => $position++,
            ]);
        }
    }
}
