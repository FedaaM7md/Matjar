<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $isDemo = (bool) $request->user()?->isDemo();
        $maxKb = $isDemo ? (int) config('matjar.demo.upload_max_kb', 2048) : 4096;

        $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ]);

        $existing = $product->images()->count();
        $position = $existing;

        foreach (array_slice($request->file('images', []), 0, max(0, 8 - $existing)) as $file) {
            $path = $file->store('products/'.$product->id, 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt_ar' => $product->name_ar,
                'alt_en' => $product->name_en,
                'position' => $position++,
            ]);
        }

        return back()->with('status', __('admin.products.images_added'));
    }

    public function destroy(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('status', __('admin.products.image_removed'));
    }
}
