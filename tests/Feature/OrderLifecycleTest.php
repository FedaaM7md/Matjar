<?php

use App\Models\Cart;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\StockMovement;
use App\Services\OrderService;
use App\Services\OrderTransitionException;
use App\Services\PaymentService;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->seed(GovernorateSeeder::class);
});

function lifecyclePayload(): array
{
    return [
        'customer_name' => 'عميل دورة الطلب',
        'customer_phone' => '01098765432',
        'customer_email' => 'lifecycle@example.test',
        'governorate_id' => Governorate::where('code', 'GIZ')->first()->id,
        'address' => 'شارع الهرم 20، الجيزة',
        'payment_method' => Order::PAYMENT_METHOD_COD,
    ];
}

function placeCodOrder($test, ProductVariant $variant, int $qty = 2): Order
{
    $customer = $test->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => $qty]);
    $test->actingAs($customer)->post('/checkout', lifecyclePayload())->assertRedirect();

    return Order::latest('id')->firstOrFail();
}

test('confirming a COD order commits reserved stock and records the timeline', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10, 'reserved' => 0]);
    $order = placeCodOrder($this, $variant, 2);
    $admin = $this->userWithRole(Role::ADMIN);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_CONFIRMED, $admin, 'confirmed_by_admin');

    expect($updated->status)->toBe('confirmed');
    expect($updated->stock_state)->toBe('committed');
    expect($updated->confirmed_at)->not->toBeNull();

    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(8);
    expect($fresh->reserved)->toBe(0);
    expect(StockMovement::where('reference_id', $order->id)->where('type', 'commit')->exists())->toBeTrue();

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => 'pending',
        'to_status' => 'confirmed',
        'user_id' => $admin->id,
    ]);
});

test('a confirmed order can move to shipped and delivered', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = placeCodOrder($this, $variant, 1);
    $service = app(OrderService::class);

    $service->transition($order, Order::STATUS_CONFIRMED);
    $service->transition($order->fresh(), Order::STATUS_SHIPPED);
    $service->transition($order->fresh(), Order::STATUS_DELIVERED);

    $final = $order->fresh();
    expect($final->status)->toBe('delivered');
    expect($final->shipped_at)->not->toBeNull();
    expect($final->delivered_at)->not->toBeNull();
    expect($final->histories()->count())->toBe(3);
});

test('returning a delivered order restores stock exactly once', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = placeCodOrder($this, $variant, 2);
    $service = app(OrderService::class);

    $service->transition($order, Order::STATUS_CONFIRMED);
    $service->transition($order->fresh(), Order::STATUS_SHIPPED);
    $service->transition($order->fresh(), Order::STATUS_DELIVERED);
    expect($variant->fresh()->stock)->toBe(3);

    $returned = $service->transition($order->fresh(), Order::STATUS_RETURNED, null, 'customer_return');

    expect($returned->status)->toBe('returned');
    expect($returned->stock_state)->toBe('released');
    expect($variant->fresh()->stock)->toBe(5);
    expect($variant->fresh()->reserved)->toBe(0);
    expect(StockMovement::where('reference_id', $order->id)->where('type', 'restore')->count())->toBe(1);

    // A second transition attempt must not restore again.
    $service->transition($order->fresh(), Order::STATUS_RETURNED);
    expect($variant->fresh()->stock)->toBe(5);
});

test('cancelling a pending order releases the reservation', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    $order = placeCodOrder($this, $variant, 3);
    expect($variant->fresh()->reserved)->toBe(3);

    app(OrderService::class)->transition($order, Order::STATUS_CANCELLED, null, null, 'customer_request');

    expect($variant->fresh()->stock)->toBe(10);
    expect($variant->fresh()->reserved)->toBe(0);
    expect($order->fresh()->stock_state)->toBe('released');
    expect($order->fresh()->cancelled_at)->not->toBeNull();
    expect($order->fresh()->cancellation_reason)->toBe('customer_request');
});

test('cancelling a confirmed order restores the committed stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    $order = placeCodOrder($this, $variant, 4);
    $service = app(OrderService::class);

    $service->transition($order, Order::STATUS_CONFIRMED);
    expect($variant->fresh()->stock)->toBe(6);

    $service->transition($order->fresh(), Order::STATUS_CANCELLED);

    expect($variant->fresh()->stock)->toBe(10);
    expect($variant->fresh()->reserved)->toBe(0);
    expect($order->fresh()->status)->toBe('cancelled');
});

test('invalid transitions are rejected', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = placeCodOrder($this, $variant, 1);
    $service = app(OrderService::class);

    foreach ([Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_RETURNED] as $invalid) {
        expect(fn () => $service->transition($order->fresh(), $invalid))
            ->toThrow(OrderTransitionException::class);
    }

    expect($order->fresh()->status)->toBe('pending');
    expect($variant->fresh()->reserved)->toBe(1);
});

test('cancelled and returned orders are terminal', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $order = placeCodOrder($this, $variant, 1);
    $service = app(OrderService::class);

    $service->transition($order, Order::STATUS_CANCELLED);

    expect(fn () => $service->transition($order->fresh(), Order::STATUS_CONFIRMED))
        ->toThrow(OrderTransitionException::class);
});

test('a gateway-paid order is already confirmed and can still be fulfilled and returned', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 8]);
    $customer = $this->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 2]);

    $this->actingAs($customer)->post('/checkout', array_merge(lifecyclePayload(), [
        'payment_method' => Order::PAYMENT_METHOD_GATEWAY,
    ]))->assertRedirect();

    $payment = Payment::firstOrFail();
    $fields = app(PaymentService::class)->callbackFields($payment, 'success');
    $this->post('/pay/'.$payment->reference.'/callback', array_merge($fields, ['outcome' => 'success']))->assertRedirect();

    $order = Order::firstOrFail();
    expect($order->status)->toBe('confirmed');
    expect($order->fresh()->stock_state)->toBe('committed');

    $service = app(OrderService::class);
    $service->transition($order->fresh(), Order::STATUS_SHIPPED);
    $service->transition($order->fresh(), Order::STATUS_DELIVERED);

    expect($variant->fresh()->stock)->toBe(6);

    $service->transition($order->fresh(), Order::STATUS_RETURNED);

    expect($variant->fresh()->stock)->toBe(8);
    expect($order->fresh()->status)->toBe('returned');
});
