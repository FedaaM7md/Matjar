<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    Storage::fake('public');
});

test('staff can open the product admin screens', function () {
    $staff = $this->userWithRole(Role::STAFF);

    $this->actingAs($staff)->get('/admin/products')->assertOk();
    $this->actingAs($staff)->get('/admin/products/create')->assertOk();
});

test('customers cannot open the product admin screens', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);

    $this->actingAs($customer)->get('/admin/products')->assertForbidden();
    $this->actingAs($customer)->post('/admin/products', [])->assertForbidden();
});

test('a product is created together with its first variant and initial ledger movement', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)->post('/admin/products', [
        'name_ar' => 'قميص جديد',
        'name_en' => 'New Shirt',
        'category_id' => $category->id,
        'description_ar' => 'وصف',
        'description_en' => 'Description',
        'is_active' => '1',
        'is_featured' => '1',
        'variant' => [
            'sku' => 'NEW-SHIRT-001',
            'price' => '425.50',
            'stock' => 12,
            'size' => 'M',
            'color' => 'أزرق',
            'low_stock_threshold' => 4,
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $product = Product::where('name_en', 'New Shirt')->first();
    expect($product)->not->toBeNull();

    $response->assertRedirect(route('admin.products.edit', $product));

    $variant = $product->variants()->first();
    expect($variant->sku)->toBe('NEW-SHIRT-001');
    expect($variant->price_cents)->toBe(42550);
    expect($variant->stock)->toBe(12);

    $movement = StockMovement::where('variant_id', $variant->id)->first();
    expect($movement->type)->toBe('in');
    expect($movement->stock_change)->toBe(12);
    expect($movement->stock_after)->toBe(12);
});

test('product creation validates required fields and unique SKUs', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    ProductVariant::factory()->create(['sku' => 'TAKEN-SKU']);

    $response = $this->actingAs($admin)->from('/admin/products/create')->post('/admin/products', [
        'name_ar' => '',
        'name_en' => '',
        'variant' => ['sku' => 'TAKEN-SKU', 'price' => '-2', 'stock' => -1],
    ]);

    $response->assertSessionHasErrors(['name_ar', 'name_en', 'variant.sku', 'variant.price', 'variant.stock']);
    expect(Product::count())->toBe(1); // only the factory product owning the SKU
});

test('slugs are generated from the English name and kept unique', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    Product::factory()->create(['slug' => 'duplicate-name']);

    $this->actingAs($admin)->post('/admin/products', [
        'name_ar' => 'منتج أول',
        'name_en' => 'Duplicate Name',
        'is_active' => '1',
        'variant' => ['sku' => 'DUP-1', 'price' => '100', 'stock' => 1],
    ])->assertRedirect();

    $created = Product::where('name_en', 'Duplicate Name')->first();
    expect($created->slug)->toBe('duplicate-name-2');
});

test('a product can be updated', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create(['name_ar' => 'اسم قديم']);
    ProductVariant::factory()->for($product)->create();

    $this->actingAs($admin)->put('/admin/products/'.$product->slug, [
        'name_ar' => 'اسم جديد',
        'name_en' => 'Updated Product',
        'is_active' => '1',
    ])->assertRedirect(route('admin.products.edit', $product));

    expect($product->fresh()->name_ar)->toBe('اسم جديد');
});

test('adding a variant records its initial stock in the ledger', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();

    $this->actingAs($admin)->post('/admin/products/'.$product->slug.'/variants', [
        'sku' => 'EXTRA-SKU',
        'price' => '199.99',
        'stock' => 6,
        'is_active' => '1',
    ])->assertRedirect(route('admin.products.edit', $product));

    $variant = ProductVariant::where('sku', 'EXTRA-SKU')->first();
    expect($variant->stock)->toBe(6);
    expect($variant->price_cents)->toBe(19999);
    expect(StockMovement::where('variant_id', $variant->id)->where('type', 'in')->exists())->toBeTrue();
});

test('variants with reserved stock cannot be deleted', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();
    $reserved = ProductVariant::factory()->for($product)->create(['stock' => 5, 'reserved' => 2]);

    $this->actingAs($admin)
        ->delete('/admin/products/'.$product->slug.'/variants/'.$reserved->id)
        ->assertSessionHas('error');

    expect($reserved->fresh())->not->toBeNull();
});

test('image uploads validate the mime type and store accepted images', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create();

    $this->actingAs($admin)->post('/admin/products/'.$product->slug.'/images', [
        'images' => [UploadedFile::fake()->image('shirt.jpg', 800, 800)],
    ])->assertSessionHasNoErrors();

    expect($product->images()->count())->toBe(1);
    Storage::disk('public')->assertExists($product->images()->first()->path);

    $this->actingAs($admin)->post('/admin/products/'.$product->slug.'/images', [
        'images' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
    ])->assertSessionHasErrors('images.0');

    expect($product->images()->count())->toBe(1);
});

test('categories can be created and cannot be deleted while products reference them', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->post('/admin/categories', [
        'name_ar' => 'عطور',
        'name_en' => 'Perfumes',
        'is_active' => '1',
    ])->assertRedirect();

    $category = Category::where('name_en', 'Perfumes')->first();
    expect($category)->not->toBeNull();

    $product = Product::factory()->for($category)->create();
    ProductVariant::factory()->for($product)->create();

    $this->actingAs($admin)->delete('/admin/categories/'.$category->slug)->assertSessionHas('error');
    expect($category->fresh())->not->toBeNull();
});

test('products and variants referenced by orders cannot be deleted', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $order = Order::factory()->create();
    $order->items()->create([
        'variant_id' => $variant->id,
        'product_id' => $product->id,
        'product_name_ar' => $product->name_ar,
        'product_name_en' => $product->name_en,
        'variant_label' => $variant->label,
        'sku' => $variant->sku,
        'unit_price_cents' => $variant->price_cents,
        'quantity' => 1,
        'line_total_cents' => $variant->price_cents,
    ]);

    $this->actingAs($admin)
        ->delete('/admin/products/'.$product->slug.'/variants/'.$variant->id)
        ->assertSessionHas('error');
    expect($variant->fresh())->not->toBeNull();

    $this->actingAs($admin)
        ->delete('/admin/products/'.$product->slug)
        ->assertSessionHas('error');
    expect($product->fresh())->not->toBeNull();
});

test('the product admin list shows stock and status', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $product = Product::factory()->create(['name_ar' => 'منتج للإدارة']);
    ProductVariant::factory()->for($product)->create(['stock' => 21]);

    $this->actingAs($admin)->get('/admin/products')
        ->assertOk()
        ->assertSee('منتج للإدارة')
        ->assertSee('21');
});
