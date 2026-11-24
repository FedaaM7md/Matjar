<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Database\Seeders\GovernorateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('the demo catalog and order history are substantial and varied', function () {
    Storage::fake('public');

    $this->seed(RolePermissionSeeder::class);
    $this->seed(GovernorateSeeder::class);
    $this->seed(DemoSeeder::class);

    // A believable clothing catalogue.
    expect(Category::count())->toBe(6);
    expect(Product::count())->toBeGreaterThanOrEqual(30);
    expect(ProductImage::count())->toBeGreaterThanOrEqual(30);

    // Sizes run S–XXL on apparel variants.
    $sizes = ProductVariant::query()->pluck('sku');
    expect(ProductVariant::where('sku', 'like', 'QTN-%')->count())->toBeGreaterThan(100);
    expect($sizes->contains(fn (string $sku) => str_contains($sku, '-M')))->toBeTrue();

    // 60+ orders spread across recent months and governorates.
    expect(Order::count())->toBeGreaterThanOrEqual(60);

    $span = Carbon::parse(Order::min('placed_at'))
        ->diffInDays(Carbon::parse(Order::max('placed_at')));
    expect($span)->toBeGreaterThanOrEqual(60);

    expect(Order::distinct('governorate_id')->count('governorate_id'))->toBeGreaterThanOrEqual(10);
    expect(Order::query()->distinct('status')->count('status'))->toBeGreaterThanOrEqual(4);
});
