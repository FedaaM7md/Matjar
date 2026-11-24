<?php

use App\Models\Cart;
use App\Models\Coupon;
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

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'منى أحمد',
        'customer_phone' => '+20 101 234 5678',
        'customer_email' => 'mona@example.test',
        'governorate_id' => Governorate::where('code', 'CAI')->first()->id,
        'address' => 'شارع النيل 10، المعادي، القاهرة',
        'notes' => null,
        'payment_method' => Order::PAYMENT_METHOD_COD,
    ], $overrides);
}

function cartWith($test, ProductVariant $variant, int $qty = 1, ?Coupon $coupon = null)
{
    $customer = $test->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id, 'coupon_id' => $coupon?->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => $qty]);
    $test->actingAs($customer);

    return [$customer, $cart];
}

test('the checkout page requires a non-empty valid cart', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $this->actingAs($customer)->get('/checkout')->assertRedirect('/cart');

    $variant = ProductVariant::factory()->create(['stock' => 5]);
    cartWith($this, $variant, 1);

    $this->get('/checkout')->assertOk()->assertSee(__('checkout.place_order'));
});

test('cash on delivery checkout creates an order, snapshot lines, reserves stock and clears the cart', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 34900, 'stock' => 10, 'reserved' => 0]);
    [$customer, $cart] = cartWith($this, $variant, 2);

    $response = $this->post('/checkout', checkoutPayload());

    $order = Order::first();
    expect($order)->not->toBeNull();
    $response->assertRedirect(route('orders.confirmation', $order->number));

    expect($order->user_id)->toBe($customer->id);
    expect($order->number)->toMatch('/^ORD-\d{4}-\d{4}$/');
    expect($order->status)->toBe('pending');
    expect($order->payment_method)->toBe('cod');
    expect($order->payment_status)->toBe('unpaid');
    expect($order->stock_state)->toBe('reserved');
    expect($order->subtotal_cents)->toBe(69800);
    expect($order->shipping_cents)->toBe(5000);
    expect($order->total_cents)->toBe(74800);
    expect($order->customer_phone)->toBe('01012345678');
    expect($order->governorate_name)->toBe('القاهرة');

    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->sku)->toBe($variant->sku);
    expect($order->items->first()->quantity)->toBe(2);
    expect($order->items->first()->line_total_cents)->toBe(69800);

    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(10);
    expect($fresh->reserved)->toBe(2);

    expect($cart->fresh()->items()->count())->toBe(0);
    expect(StockMovement::where('reference_type', 'order')->where('reference_id', $order->id)->where('type', 'reserve')->count())->toBe(1);
});

test('checkout validates the egyptian phone, address and governorate', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    cartWith($this, $variant, 1);

    $this->from('/checkout')->post('/checkout', checkoutPayload([
        'customer_phone' => '12345',
        'address' => 'short',
        'governorate_id' => 99999,
    ]))->assertSessionHasErrors(['customer_phone', 'address', 'governorate_id']);

    expect(Order::count())->toBe(0);
    expect($variant->fresh()->reserved)->toBe(0);
});

test('checkout refuses to oversell when stock changed after adding to the cart', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    cartWith($this, $variant, 2);

    $variant->forceFill(['reserved' => 4])->save();

    $this->from('/checkout')->post('/checkout', checkoutPayload())
        ->assertSessionHasErrors(['checkout']);

    expect(Order::count())->toBe(0);
    expect($variant->fresh()->reserved)->toBe(4);
});

test('checkout applies coupon discount, consumes it once and handles free shipping after discount', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 160000, 'stock' => 5]);
    $coupon = Coupon::factory()->create(['code' => 'TENOFF', 'type' => Coupon::TYPE_PERCENT, 'value' => 1000, 'usage_limit' => 1]);
    [$customer, $cart] = cartWith($this, $variant, 1, $coupon);

    $this->post('/checkout', checkoutPayload())->assertRedirect();

    $order = Order::first();
    expect($order->subtotal_cents)->toBe(160000);
    expect($order->discount_cents)->toBe(16000);
    expect($order->shipping_cents)->toBe(5000);
    expect($order->total_cents)->toBe(149000);
    expect($order->coupon_code)->toBe('TENOFF');
    expect($coupon->fresh()->used_count)->toBe(1);
});

test('free shipping applies above the governorate threshold', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 160000, 'stock' => 5]);
    cartWith($this, $variant, 1);

    $this->post('/checkout', checkoutPayload())->assertRedirect();

    $order = Order::first();
    expect($order->shipping_cents)->toBe(0);
    expect($order->total_cents)->toBe(160000);
});

test('a coupon that reached its usage limit blocks a second order', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 100000, 'stock' => 10]);
    $coupon = Coupon::factory()->create(['code' => 'ONCE', 'type' => Coupon::TYPE_FIXED, 'value' => 10000, 'usage_limit' => 1]);

    cartWith($this, $variant, 1, $coupon);
    $this->post('/checkout', checkoutPayload())->assertRedirect();
    expect(Order::count())->toBe(1);

    $second = ProductVariant::factory()->create(['price_cents' => 100000, 'stock' => 10]);
    $this->actingAs($this->userWithRole(Role::CUSTOMER));
    $cart = Cart::create(['user_id' => auth()->id(), 'coupon_id' => $coupon->id]);
    $cart->items()->create(['variant_id' => $second->id, 'quantity' => 1]);

    $this->from('/checkout')->post('/checkout', checkoutPayload())
        ->assertSessionHasErrors(['coupon']);

    expect(Order::count())->toBe(1);
    expect($coupon->fresh()->used_count)->toBe(1);
});

test('order numbers are sequential per year', function () {
    $a = ProductVariant::factory()->create(['price_cents' => 10000, 'stock' => 10]);
    cartWith($this, $a, 1);
    $this->post('/checkout', checkoutPayload())->assertRedirect();

    $b = ProductVariant::factory()->create(['price_cents' => 10000, 'stock' => 10]);
    $this->actingAs($this->userWithRole(Role::CUSTOMER));
    $cart = Cart::create(['user_id' => auth()->id()]);
    $cart->items()->create(['variant_id' => $b->id, 'quantity' => 1]);
    $this->post('/checkout', checkoutPayload())->assertRedirect();

    $numbers = Order::orderBy('id')->pluck('number')->all();
    expect($numbers)->toBe(['ORD-2026-0001', 'ORD-2026-0002']);
});

test('a guest order is reachable through a signed url and hidden without it', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);

    $this->post('/cart/add', ['variant_id' => $variant->id, 'quantity' => 1]);
    $response = $this->post('/checkout', checkoutPayload());

    $order = Order::first();
    $location = $response->headers->get('Location');
    expect($location)->toContain('signature=');

    $this->get($location)->assertOk()->assertSee($order->number)->assertSee(__('orders.thanks_title'));

    auth()->logout();
    $this->get('/orders/'.$order->number)->assertForbidden();
    $this->get('/orders/'.$order->number.'/invoice')->assertForbidden();
});

test('the simulated gateway checkout creates a pending payment and reserves stock', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 5]);
    cartWith($this, $variant, 1);

    $response = $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $order = Order::first();
    $payment = Payment::first();
    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe('pending');
    expect($payment->amount_cents)->toBe(55000);
    expect($payment->order_id)->toBe($order->id);
    expect($order->payment_status)->toBe('pending');
    expect($order->stock_state)->toBe('reserved');
    expect($variant->fresh()->reserved)->toBe(1);

    $response->assertRedirect(route('payments.show', $payment->reference));
    $this->get(route('payments.show', $payment->reference))->assertOk()->assertSee(__('payments.simulator'));
});

test('a valid signed success callback confirms the order and commits reserved stock', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 2);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $order = Order::first();
    $fields = app(PaymentService::class)->callbackFields($payment, 'success');

    $this->post('/pay/'.$payment->reference.'/callback', array_merge($fields, ['outcome' => 'success']))
        ->assertRedirect(route('orders.confirmation', $order->number));

    expect($payment->fresh()->status)->toBe('paid');
    expect($order->fresh()->status)->toBe('confirmed');
    expect($order->fresh()->payment_status)->toBe('paid');
    expect($order->fresh()->stock_state)->toBe('committed');

    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(8);
    expect($fresh->reserved)->toBe(0);
    expect(StockMovement::where('reference_id', $order->id)->where('type', 'commit')->exists())->toBeTrue();
});

test('a failed gateway payment cancels the order and releases stock', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 2);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $order = Order::first();
    $fields = app(PaymentService::class)->callbackFields($payment, 'fail');

    $this->post('/pay/'.$payment->reference.'/callback', array_merge($fields, ['outcome' => 'fail']))
        ->assertRedirect(route('orders.confirmation', $order->number));

    expect($payment->fresh()->status)->toBe('failed');
    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->payment_status)->toBe('failed');
    expect($order->fresh()->stock_state)->toBe('released');
    expect($variant->fresh()->reserved)->toBe(0);
    expect($variant->fresh()->stock)->toBe(10);
});

test('a cancelled gateway payment cancels the order and releases stock', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 1);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $order = Order::first();
    $fields = app(PaymentService::class)->callbackFields($payment, 'cancel');

    $this->post('/pay/'.$payment->reference.'/callback', array_merge($fields, ['outcome' => 'cancel']))
        ->assertRedirect(route('orders.confirmation', $order->number));

    expect($payment->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->payment_status)->toBe('cancelled');
    expect($variant->fresh()->reserved)->toBe(0);
});

test('tampered callbacks are rejected without changing any state', function (string $tamper) {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 1);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $order = Order::first();
    $fields = app(PaymentService::class)->callbackFields($payment, 'success');

    $payload = match ($tamper) {
        'signature' => array_merge($fields, ['outcome' => 'success', 'signature' => str_repeat('a', 64)]),
        'amount' => array_merge($fields, ['outcome' => 'success', 'amount_cents' => 1]),
        'outcome' => array_merge($fields, ['outcome' => 'fail']),
        'expiry' => array_merge($fields, ['outcome' => 'success', 'expires' => now()->subHour()->timestamp]),
    };

    $this->post('/pay/'.$payment->reference.'/callback', $payload)->assertForbidden();

    expect($payment->fresh()->status)->toBe('pending');
    expect($order->fresh()->status)->toBe('pending');
    expect($order->fresh()->stock_state)->toBe('reserved');
    expect($variant->fresh()->reserved)->toBe(1);
})->with(['signature', 'amount', 'outcome', 'expiry']);

test('a paid callback cannot be replayed to double-deduct stock', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 1);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $fields = app(PaymentService::class)->callbackFields($payment, 'success');
    $payload = array_merge($fields, ['outcome' => 'success']);

    $this->post('/pay/'.$payment->reference.'/callback', $payload)->assertRedirect();
    $this->post('/pay/'.$payment->reference.'/callback', $payload)
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($variant->fresh()->stock)->toBe(9);
    expect($variant->fresh()->reserved)->toBe(0);
});

test('a cancelled gateway order cannot be paid', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 10]);
    cartWith($this, $variant, 1);
    $this->post('/checkout', checkoutPayload(['payment_method' => Order::PAYMENT_METHOD_GATEWAY]));

    $payment = Payment::first();
    $order = Order::first();

    app(OrderService::class)->transition($order, Order::STATUS_CANCELLED, null, null, 'test_cancel');

    $fields = app(PaymentService::class)->callbackFields($payment, 'success');
    $this->post('/pay/'.$payment->reference.'/callback', array_merge($fields, ['outcome' => 'success']))
        ->assertStatus(409);

    expect($payment->fresh()->status)->toBe('pending');
    expect($order->fresh()->status)->toBe('cancelled');
    expect($variant->fresh()->reserved)->toBe(0);
    expect($variant->fresh()->stock)->toBe(10);

    $this->get('/pay/'.$payment->reference)
        ->assertRedirect(route('orders.confirmation', $order->number));
});

test('an invoice is printable by its owner and contains totals', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 34900, 'stock' => 10]);
    cartWith($this, $variant, 2);
    $this->post('/checkout', checkoutPayload());

    $order = Order::first();

    $this->get('/orders/'.$order->number.'/invoice')
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('698.00')
        ->assertSee(__('orders.invoice'));
});

test('invalid order transitions are rejected and leave stock untouched', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    cartWith($this, $variant, 1);
    $this->post('/checkout', checkoutPayload());

    $order = Order::first();

    expect(fn () => app(OrderService::class)->transition($order, Order::STATUS_DELIVERED))
        ->toThrow(OrderTransitionException::class);

    expect($order->fresh()->status)->toBe('pending');
    expect($variant->fresh()->reserved)->toBe(1);
});
