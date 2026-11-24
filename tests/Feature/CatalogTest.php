<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('shop lists only active products', function () {
    $visible = Product::factory()->create(['name_ar' => 'منتج ظاهر']);
    ProductVariant::factory()->for($visible)->create(['stock' => 5]);
    $hidden = Product::factory()->inactive()->create(['name_ar' => 'منتج مخفي']);

    $this->get('/shop')
        ->assertOk()
        ->assertSee('منتج ظاهر')
        ->assertDontSee('منتج مخفي');
});

test('shop search matches arabic names and variant SKUs', function () {
    $shirt = Product::factory()->create(['name_ar' => 'قميص قطني']);
    ProductVariant::factory()->for($shirt)->create(['sku' => 'FDM-UNIQUE-SKU']);
    $other = Product::factory()->create(['name_ar' => 'حقيبة']);
    ProductVariant::factory()->for($other)->create(['sku' => 'OTHER-SKU']);

    $this->get('/shop?q=قميص')->assertOk()->assertSee('قميص قطني')->assertDontSee('حقيبة');
    $this->get('/shop?q=UNIQUE-SKU')->assertOk()->assertSee('قميص قطني')->assertDontSee('حقيبة');
});

test('category pages only show products in that category', function () {
    $fashion = Category::factory()->create(['name_ar' => 'أزياء']);
    $home = Category::factory()->create(['name_ar' => 'منزل']);
    $shirt = Product::factory()->for($fashion)->create(['name_ar' => 'قميص']);
    ProductVariant::factory()->for($shirt)->create();
    $cup = Product::factory()->for($home)->create(['name_ar' => 'كوب']);
    ProductVariant::factory()->for($cup)->create();

    $this->get('/category/'.$fashion->slug)
        ->assertOk()
        ->assertSee('قميص')
        ->assertDontSee('كوب');
});

test('inactive categories are not browsable', function () {
    $category = Category::factory()->create(['is_active' => false]);

    $this->get('/category/'.$category->slug)->assertNotFound();
});

test('price filters exclude variants outside the range', function () {
    $cheap = Product::factory()->create(['name_ar' => 'رخيص']);
    ProductVariant::factory()->for($cheap)->create(['price_cents' => 10000]);
    $expensive = Product::factory()->create(['name_ar' => 'غالي']);
    ProductVariant::factory()->for($expensive)->create(['price_cents' => 50000]);

    $this->get('/shop?min_price=200&max_price=600')
        ->assertOk()
        ->assertSee('غالي')
        ->assertDontSee('رخيص');
});

test('products can be sorted by price ascending', function () {
    $high = Product::factory()->create(['name_ar' => 'باهظ']);
    ProductVariant::factory()->for($high)->create(['price_cents' => 90000]);
    $low = Product::factory()->create(['name_ar' => 'زهيد']);
    ProductVariant::factory()->for($low)->create(['price_cents' => 10000]);

    $this->get('/shop?sort=price_asc')
        ->assertOk()
        ->assertSeeInOrder(['زهيد', 'باهظ']);
});

test('the in-stock filter hides products whose variants are fully reserved', function () {
    $available = Product::factory()->create(['name_ar' => 'متاح للبيع']);
    ProductVariant::factory()->for($available)->create(['stock' => 5, 'reserved' => 0]);
    $reserved = Product::factory()->create(['name_ar' => 'محجوز كليًا']);
    ProductVariant::factory()->for($reserved)->create(['stock' => 5, 'reserved' => 5]);

    $this->get('/shop?in_stock=1')
        ->assertOk()
        ->assertSee('متاح للبيع')
        ->assertDontSee('محجوز كليًا');
});

test('the product page shows variant prices, SKU and availability', function () {
    $product = Product::factory()->create(['name_ar' => 'حقيبة ظهر']);
    ProductVariant::factory()->for($product)->create([
        'sku' => 'BAG-001',
        'size' => 'M',
        'color' => 'كحلي',
        'price_cents' => 54900,
        'stock' => 7,
    ]);

    $this->get('/product/'.$product->slug)
        ->assertOk()
        ->assertSee('حقيبة ظهر')
        ->assertSee('BAG-001')
        ->assertSee('549.00')
        ->assertSee('كحلي');
});

test('inactive products return a 404 on the storefront', function () {
    $product = Product::factory()->inactive()->create();

    $this->get('/product/'.$product->slug)->assertNotFound();
});

test('home page renders featured products and active categories', function () {
    $category = Category::factory()->create(['name_ar' => 'فئة مميزة']);
    $featured = Product::factory()->featured()->for($category)->create(['name_ar' => 'منتج مختار']);
    ProductVariant::factory()->for($featured)->create();
    $normal = Product::factory()->for($category)->create(['name_ar' => 'منتج عادي']);

    $this->get('/')
        ->assertOk()
        ->assertSee('فئة مميزة')
        ->assertSee('منتج مختار')
        ->assertDontSee('منتج عادي');
});

test('catalog works in English after switching locale', function () {
    $product = Product::factory()->create(['name_ar' => 'قميص', 'name_en' => 'Cotton Shirt']);
    ProductVariant::factory()->for($product)->create();

    $this->post('/locale/en');
    $this->get('/shop')->assertOk()->assertSee('Cotton Shirt')->assertSee('All products');
});
