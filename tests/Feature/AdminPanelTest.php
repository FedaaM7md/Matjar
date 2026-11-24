<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Services\ShippingService;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->seed(GovernorateSeeder::class);
});

function adminCheckoutOrder($test, ProductVariant $variant, int $qty = 1): Order
{
    $customer = $test->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => $qty]);

    $test->actingAs($customer)->post('/checkout', [
        'customer_name' => 'عميل إدارة',
        'customer_phone' => '01055556666',
        'customer_email' => $customer->email,
        'governorate_id' => Governorate::where('code', 'CAI')->first()->id,
        'address' => 'شارع الإدارة 5، القاهرة',
        'payment_method' => Order::PAYMENT_METHOD_COD,
    ])->assertRedirect();

    return Order::latest('id')->firstOrFail();
}

test('admin sections respect the admin and staff permission matrix', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $staff = $this->userWithRole(Role::STAFF);
    $customer = $this->userWithRole(Role::CUSTOMER);

    foreach (['/admin', '/admin/orders', '/admin/customers', '/admin/reports', '/admin/coupons', '/admin/shipping', '/admin/settings'] as $path) {
        $this->actingAs($admin)->get($path)->assertOk();
        $this->actingAs($customer)->get($path)->assertForbidden();
    }

    $this->actingAs($staff)->get('/admin')->assertOk();
    $this->actingAs($staff)->get('/admin/orders')->assertOk();
    $this->actingAs($staff)->get('/admin/customers')->assertOk();
    $this->actingAs($staff)->get('/admin/reports')->assertOk();
    $this->actingAs($staff)->get('/admin/coupons')->assertForbidden();
    $this->actingAs($staff)->get('/admin/shipping')->assertForbidden();
    $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
});

test('admin pages render in English LTR with translated labels', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $this->post('/locale/en');

    foreach ([
        '/admin', '/admin/products', '/admin/products/create', '/admin/categories',
        '/admin/inventory', '/admin/orders', '/admin/customers', '/admin/coupons',
        '/admin/coupons/create', '/admin/shipping', '/admin/settings', '/admin/reports',
    ] as $path) {
        $response = $this->actingAs($admin)->get($path);
        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
    }
});

test('the dashboard shows revenue, pending orders and low-stock alerts', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    Order::factory()->create([
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'total_cents' => 123400,
        'placed_at' => now(),
    ]);
    Order::factory()->create(['status' => 'pending', 'placed_at' => now()]);
    ProductVariant::factory()->create(['stock' => 1, 'reserved' => 0, 'low_stock_threshold' => 3]);

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
    $response->assertSee('1,234.00');
    $response->assertSee(__('admin.dashboard.status_breakdown'));
    $response->assertSee(__('admin.dashboard.review_now'));
});

test('staff can list and search orders', function () {
    $staff = $this->userWithRole(Role::STAFF);
    Order::factory()->create(['number' => 'ORD-2026-7001', 'customer_name' => 'سارة أحمد']);
    Order::factory()->create(['number' => 'ORD-2026-7002', 'customer_name' => 'محمد علي']);

    $this->actingAs($staff)->get('/admin/orders')
        ->assertOk()
        ->assertSee('ORD-2026-7001')
        ->assertSee('ORD-2026-7002');

    $this->actingAs($staff)->get('/admin/orders?q=سارة')
        ->assertOk()
        ->assertSee('ORD-2026-7001')
        ->assertDontSee('ORD-2026-7002');
});

test('an admin can confirm an order which commits stock, then ship and deliver it', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    $order = adminCheckoutOrder($this, $variant, 2);
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->get('/admin/orders/'.$order->number)
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee(__('admin.orders.transition_title'));

    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', ['status' => 'confirmed'])
        ->assertRedirect(route('admin.orders.show', $order));

    expect($order->fresh()->status)->toBe('confirmed');
    expect($variant->fresh()->stock)->toBe(8);
    expect($variant->fresh()->reserved)->toBe(0);

    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', ['status' => 'shipped']);
    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', ['status' => 'delivered']);

    expect($order->fresh()->status)->toBe('delivered');
    expect($variant->fresh()->stock)->toBe(8);
});

test('cancelling through the admin restores stock and invalid transitions are rejected', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    $order = adminCheckoutOrder($this, $variant, 3);
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', ['status' => 'confirmed']);
    expect($variant->fresh()->stock)->toBe(7);

    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', [
        'status' => 'cancelled',
        'reason' => 'طلب العميل',
    ]);

    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->cancellation_reason)->toBe('طلب العميل');
    expect($variant->fresh()->stock)->toBe(10);
    expect($variant->fresh()->reserved)->toBe(0);

    $this->actingAs($admin)->post('/admin/orders/'.$order->number.'/transition', ['status' => 'delivered'])
        ->assertSessionHas('error');
});

test('customers can be searched and viewed by staff', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $customer = $this->userWithRole(Role::CUSTOMER, [
        'name' => 'هدى محمد',
        'email' => 'huda@example.test',
        'phone' => '01011112222',
    ]);
    $admin = $this->userWithRole(Role::ADMIN);

    Order::factory()->create(['user_id' => $customer->id, 'customer_email' => $customer->email]);

    $this->actingAs($staff)->get('/admin/customers?q=هدى')
        ->assertOk()
        ->assertSee('هدى محمد')
        ->assertSee($customer->email);

    $this->actingAs($staff)->get('/admin/customers/'.$customer->id)
        ->assertOk()
        ->assertSee('هدى محمد')
        ->assertSee(__('admin.customers.order_history'));

    $this->actingAs($staff)->get('/admin/customers/'.$admin->id)->assertNotFound();
});

test('coupons can be created, edited and validated', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->get('/admin/coupons/create')->assertOk();

    $this->actingAs($admin)->post('/admin/coupons', [
        'code' => 'welcome10',
        'type' => 'percent',
        'value' => '10',
        'min_subtotal' => '250',
        'max_discount' => '100',
        'usage_limit' => '50',
        'per_user_limit' => '2',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addMonth()->toDateString(),
        'is_active' => '1',
    ])->assertRedirect(route('admin.coupons.index'));

    $coupon = Coupon::where('code', 'WELCOME10')->first();
    expect($coupon)->not->toBeNull();
    expect($coupon->value)->toBe(1000);
    expect($coupon->min_subtotal_cents)->toBe(25000);
    expect($coupon->max_discount_cents)->toBe(10000);
    expect($coupon->per_user_limit)->toBe(2);

    $this->actingAs($admin)->put('/admin/coupons/'.$coupon->id, [
        'code' => 'WELCOME10',
        'type' => 'fixed',
        'value' => '75.50',
        'usage_limit' => '10',
        'is_active' => '1',
    ])->assertRedirect(route('admin.coupons.index'));

    $coupon->refresh();
    expect($coupon->type)->toBe('fixed');
    expect($coupon->value)->toBe(7550);
    expect($coupon->max_discount_cents)->toBeNull();
});

test('coupon validation rejects bad percentages and invalid date ranges', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    Coupon::factory()->create(['code' => 'TAKEN']);

    $this->actingAs($admin)->from('/admin/coupons/create')->post('/admin/coupons', [
        'code' => 'taken',
        'type' => 'percent',
        'value' => '150',
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->subDay()->toDateString(),
        'is_active' => '1',
    ])->assertSessionHasErrors(['code', 'value', 'ends_at']);
});

test('a coupon used in an order cannot be deleted', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $coupon = Coupon::factory()->create(['code' => 'USED', 'used_count' => 1]);

    $this->actingAs($admin)->delete('/admin/coupons/'.$coupon->id)->assertSessionHas('error');
    expect($coupon->fresh())->not->toBeNull();
});

test('shipping rates and thresholds can be updated and affect quotes', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $staff = $this->userWithRole(Role::STAFF);
    $cairo = Governorate::where('code', 'CAI')->first();

    $this->actingAs($staff)->get('/admin/shipping')->assertForbidden();
    $this->actingAs($staff)->post('/admin/shipping/'.$cairo->id, [
        'rate' => '10', 'delivery_days_min' => 1, 'delivery_days_max' => 2,
    ])->assertForbidden();

    $this->actingAs($admin)->post('/admin/shipping/'.$cairo->id, [
        'rate' => '75',
        'free_threshold' => '2000',
        'delivery_days_min' => 2,
        'delivery_days_max' => 4,
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $zone = ShippingZone::where('governorate_id', $cairo->id)->first();
    expect($zone->rate_cents)->toBe(7500);
    expect($zone->free_threshold_cents)->toBe(200000);
    expect($zone->delivery_days_max)->toBe(4);

    $quote = app(ShippingService::class)->quote($cairo->fresh(), 199999);
    expect($quote['shipping_cents'])->toBe(7500);

    $this->actingAs($admin)->from('/admin/shipping')->post('/admin/shipping/'.$cairo->id, [
        'rate' => '75',
        'delivery_days_min' => 5,
        'delivery_days_max' => 2,
    ])->assertSessionHasErrors('delivery_days_max');
});

test('settings can be updated and disabling COD removes it from checkout', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $staff = $this->userWithRole(Role::STAFF);

    $this->actingAs($staff)->put('/admin/settings', [])->assertForbidden();

    $payload = [
        'store_name_ar' => 'متجر النيل',
        'store_name_en' => 'Nile Store',
        'support_phone' => '01000000009',
        'support_email' => 'support@example.test',
        'low_stock_threshold' => '4',
        'fake_gateway_enabled' => '1',
        '_method' => 'PUT',
    ];

    $this->actingAs($admin)->put('/admin/settings', $payload)->assertSessionHasNoErrors();
    Setting::forgetMemo();

    expect(Setting::get('store_name_ar'))->toBe('متجر النيل');
    expect(Setting::bool('cod_enabled'))->toBeFalse();
    expect(Setting::bool('fake_gateway_enabled'))->toBeTrue();

    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $customer = $this->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $this->actingAs($customer)->get('/checkout')
        ->assertOk()
        ->assertDontSee('value="cod"', false)
        ->assertSee('value="test_gateway"', false);
});

test('settings refuse to disable both payment methods', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->from('/admin/settings')->put('/admin/settings', [
        'store_name_ar' => 'متجر',
        'store_name_en' => 'Store',
        'support_phone' => '01000000000',
        'support_email' => 'support@example.test',
        'low_stock_threshold' => '5',
    ])->assertSessionHasErrors('cod_enabled');
});

test('reports compute revenue, top products and governorate breakdown for the period', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $cairo = Governorate::where('code', 'CAI')->first();
    $giza = Governorate::where('code', 'GIZ')->first();

    $variantA = ProductVariant::factory()->create(['price_cents' => 50000]);
    $variantB = ProductVariant::factory()->create(['price_cents' => 25000]);

    $orderA = Order::factory()->create([
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'subtotal_cents' => 100000,
        'shipping_cents' => 5000,
        'total_cents' => 105000,
        'governorate_id' => $cairo->id,
        'governorate_name' => $cairo->name,
        'placed_at' => now(),
    ]);
    $orderA->items()->create([
        'variant_id' => $variantA->id,
        'product_id' => $variantA->product_id,
        'product_name_ar' => 'منتج التقرير',
        'product_name_en' => 'Report product',
        'variant_label' => 'M',
        'sku' => $variantA->sku,
        'unit_price_cents' => 50000,
        'quantity' => 2,
        'line_total_cents' => 100000,
    ]);

    $orderB = Order::factory()->create([
        'status' => 'delivered',
        'payment_status' => 'paid',
        'subtotal_cents' => 50000,
        'shipping_cents' => 5000,
        'total_cents' => 55000,
        'governorate_id' => $giza->id,
        'governorate_name' => $giza->name,
        'placed_at' => now(),
    ]);
    $orderB->items()->create([
        'variant_id' => $variantB->id,
        'product_id' => $variantB->product_id,
        'product_name_ar' => 'منتج ثانٍ',
        'product_name_en' => 'Second product',
        'variant_label' => null,
        'sku' => $variantB->sku,
        'unit_price_cents' => 25000,
        'quantity' => 2,
        'line_total_cents' => 50000,
    ]);

    Order::factory()->create([
        'status' => 'cancelled',
        'total_cents' => 999900,
        'placed_at' => now(),
    ]);
    Order::factory()->create([
        'status' => 'pending',
        'total_cents' => 888800,
        'placed_at' => now()->subDays(60),
    ]);

    $response = $this->actingAs($staff)->get('/admin/reports');
    $response->assertOk();
    $response->assertSee('1,600.00');
    $response->assertSee('منتج التقرير');
    $response->assertSee($cairo->name);
    $response->assertSee($giza->name);
    $response->assertDontSee('9,999.00');
});
