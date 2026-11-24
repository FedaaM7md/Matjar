<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

function historyOrder($test, $customer, array $attributes = []): Order
{
    return Order::factory()->create(array_merge([
        'user_id' => $customer->id,
        'customer_email' => $customer->email,
    ], $attributes));
}

test('guests are redirected from account order pages', function () {
    $this->get('/account/orders')->assertRedirect('/login');
    $this->get('/account')->assertRedirect('/login');
});

test('customers only see their own orders in the history', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $other = $this->userWithRole(Role::CUSTOMER);

    $mine = historyOrder($this, $customer, ['number' => 'ORD-2026-1001']);
    $theirs = historyOrder($this, $other, ['number' => 'ORD-2026-1002']);

    $this->actingAs($customer)->get('/account/orders')
        ->assertOk()
        ->assertSee('ORD-2026-1001')
        ->assertDontSee('ORD-2026-1002');
});

test('order history can be filtered by status', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    historyOrder($this, $customer, ['number' => 'ORD-2026-2001', 'status' => 'pending']);
    historyOrder($this, $customer, ['number' => 'ORD-2026-2002', 'status' => 'delivered']);

    $this->actingAs($customer)->get('/account/orders?status=delivered')
        ->assertOk()
        ->assertSee('ORD-2026-2002')
        ->assertDontSee('ORD-2026-2001');
});

test('customers cannot view another customer order or invoice', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $other = $this->userWithRole(Role::CUSTOMER);
    $order = historyOrder($this, $other, ['number' => 'ORD-2026-3001']);

    $this->actingAs($customer)->get('/orders/'.$order->number)->assertForbidden();
    $this->actingAs($customer)->get('/orders/'.$order->number.'/invoice')->assertForbidden();
    $this->actingAs($customer)->get('/orders/'.$order->number.'/confirmation')->assertForbidden();
});

test('admins can view any order and its invoice', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $customer = $this->userWithRole(Role::CUSTOMER);
    $order = historyOrder($this, $customer, ['number' => 'ORD-2026-3002']);

    $this->actingAs($admin)->get('/orders/'.$order->number)->assertOk()->assertSee($order->number);
    $this->actingAs($admin)->get('/orders/'.$order->number.'/invoice')->assertOk();
});

test('the account dashboard shows real order statistics and recent orders', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);

    historyOrder($this, $customer, [
        'number' => 'ORD-2026-4001',
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'total_cents' => 123400,
    ]);
    historyOrder($this, $customer, [
        'number' => 'ORD-2026-4002',
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($customer)->get('/account')
        ->assertOk()
        ->assertSee('ORD-2026-4001')
        ->assertSee('ORD-2026-4002')
        ->assertSee('1,234.00')
        ->assertSee('2');
});

test('the order page shows items, totals, status and timeline', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $variant = ProductVariant::factory()->create(['price_cents' => 25000, 'stock' => 10]);
    $order = historyOrder($this, $customer, [
        'number' => 'ORD-2026-4003',
        'subtotal_cents' => 50000,
        'shipping_cents' => 6500,
        'total_cents' => 56500,
        'status' => 'confirmed',
    ]);
    $order->items()->create([
        'variant_id' => $variant->id,
        'product_id' => $variant->product_id,
        'product_name_ar' => 'منتج للعرض',
        'product_name_en' => 'Display product',
        'variant_label' => 'M',
        'sku' => $variant->sku,
        'unit_price_cents' => 25000,
        'quantity' => 2,
        'line_total_cents' => 50000,
    ]);
    $order->histories()->create([
        'from_status' => 'pending',
        'to_status' => 'confirmed',
        'note' => 'confirmed_by_admin',
        'created_at' => now(),
    ]);

    $this->actingAs($customer)->get('/orders/'.$order->number)
        ->assertOk()
        ->assertSee('ORD-2026-4003')
        ->assertSee('منتج للعرض')
        ->assertSee('565.00')
        ->assertSee(__('orders.statuses.confirmed'))
        ->assertSee(__('orders.print_invoice'));
});
