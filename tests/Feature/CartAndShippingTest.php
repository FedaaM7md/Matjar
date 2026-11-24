<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\ShippingService;
use App\Support\Money;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->seed(GovernorateSeeder::class);
});

function cartForCustomer($test): array
{
    $customer = $test->userWithRole(Role::CUSTOMER);
    $cart = Cart::create(['user_id' => $customer->id]);
    $test->actingAs($customer);

    return [$customer, $cart, app(CartService::class)];
}

test('all 27 Egyptian governorates are seeded with active shipping zones', function () {
    expect(Governorate::count())->toBe(27);
    expect(ShippingZone::where('is_active', true)->count())->toBe(27);
    expect(Governorate::where('code', 'CAI')->exists())->toBeTrue();
    expect(Governorate::where('code', 'ASW')->exists())->toBeTrue();
});

test('shipping is charged below the free threshold and free at or above it', function () {
    $shipping = app(ShippingService::class);
    $cairo = Governorate::where('code', 'CAI')->first();
    $zone = $cairo->zone;
    $zone->update(['rate_cents' => 5000, 'free_threshold_cents' => 150000]);

    $below = $shipping->quote($cairo->fresh(), 149999);
    expect($below['shipping_cents'])->toBe(5000);
    expect($below['free'])->toBeFalse();
    expect($below['remaining_cents'])->toBe(1);

    $at = $shipping->quote($cairo->fresh(), 150000);
    expect($at['shipping_cents'])->toBe(0);
    expect($at['free'])->toBeTrue();
});

test('inactive or missing zones are unavailable', function () {
    $shipping = app(ShippingService::class);
    $luxor = Governorate::where('code', 'LXR')->first();
    $luxor->zone->update(['is_active' => false]);

    expect($shipping->quote($luxor->fresh(), 500000)['available'])->toBeFalse();
});

test('coupon percentage discount respects the maximum cap', function () {
    $coupon = Coupon::factory()->create(['type' => Coupon::TYPE_PERCENT, 'value' => 1000, 'max_discount_cents' => 5000]);

    expect($coupon->discountFor(100000))->toBe(5000);
    expect($coupon->discountFor(20000))->toBe(2000);
});

test('coupon percentage rounding and fixed discounts are correct', function () {
    $percent = Coupon::factory()->create(['type' => Coupon::TYPE_PERCENT, 'value' => 1250]);
    expect($percent->discountFor(33333))->toBe(4167);

    $fixed = Coupon::factory()->fixed(15000)->create();
    expect($fixed->discountFor(100000))->toBe(15000);
    expect($fixed->discountFor(5000))->toBe(5000);
});

test('coupon validation covers inactive, scheduled, expired, limits and minimums', function () {
    $coupons = app(CouponService::class);

    $inactive = Coupon::factory()->create(['is_active' => false]);
    expect($coupons->errorFor($inactive, 100000))->toBe(__('coupons.errors.inactive'));

    $scheduled = Coupon::factory()->create(['starts_at' => now()->addDay()]);
    expect($coupons->errorFor($scheduled, 100000))->toBe(__('coupons.errors.not_started'));

    $expired = Coupon::factory()->expired()->create();
    expect($coupons->errorFor($expired, 100000))->toBe(__('coupons.errors.expired'));

    $exhausted = Coupon::factory()->create(['usage_limit' => 2, 'used_count' => 2]);
    expect($coupons->errorFor($exhausted, 100000))->toBe(__('coupons.errors.usage_limit'));

    $minimum = Coupon::factory()->create(['min_subtotal_cents' => 50000]);
    expect($coupons->errorFor($minimum, 49999))->toBe(__('coupons.errors.min_subtotal', ['amount' => Money::withCurrency(50000)]));
    expect($coupons->errorFor($minimum, 50000))->toBeNull();
});

test('the cart summary totals subtotal, discount, shipping and grand total in minor units', function () {
    [$customer, $cart, $cartService] = cartForCustomer($this);

    $a = ProductVariant::factory()->create(['price_cents' => 34900, 'stock' => 10]);
    $b = ProductVariant::factory()->create(['price_cents' => 27500, 'stock' => 10]);
    $cart->items()->create(['variant_id' => $a->id, 'quantity' => 2]);
    $cart->items()->create(['variant_id' => $b->id, 'quantity' => 1]);

    $coupon = Coupon::factory()->fixed(10000)->create();
    $cart->update(['coupon_id' => $coupon->id]);

    $cairo = Governorate::where('code', 'CAI')->first();
    $cairo->zone->update(['rate_cents' => 5000, 'free_threshold_cents' => 150000]);

    $summary = $cartService->summary($cart->fresh(), $cairo->fresh());

    expect($summary['subtotal_cents'])->toBe(97300);
    expect($summary['discount_cents'])->toBe(10000);
    expect($summary['shipping_cents'])->toBe(5000);
    expect($summary['total_cents'])->toBe(92300);
    expect($summary['total_formatted'])->toContain('923.00');
});

test('free shipping is evaluated on the subtotal after discount', function () {
    [$customer, $cart, $cartService] = cartForCustomer($this);
    $variant = ProductVariant::factory()->create(['price_cents' => 160000, 'stock' => 2]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $cairo = Governorate::where('code', 'CAI')->first();
    $cairo->zone->update(['rate_cents' => 5000, 'free_threshold_cents' => 150000]);

    $withoutCoupon = $cartService->summary($cart->fresh(), $cairo->fresh());
    expect($withoutCoupon['shipping_cents'])->toBe(0);
    expect($withoutCoupon['shipping']['free'])->toBeTrue();

    $coupon = Coupon::factory()->create(['type' => Coupon::TYPE_PERCENT, 'value' => 1000]);
    $cart->update(['coupon_id' => $coupon->id]);

    $withCoupon = $cartService->summary($cart->fresh(), $cairo->fresh());
    expect($withCoupon['subtotal_cents'])->toBe(160000);
    expect($withCoupon['discount_cents'])->toBe(16000);
    expect($withCoupon['shipping_cents'])->toBe(5000);
});

test('a coupon can be applied through the cart, is case-insensitive and can be removed', function () {
    [$customer, $cart, $cartService] = cartForCustomer($this);
    $variant = ProductVariant::factory()->create(['price_cents' => 50000, 'stock' => 5]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $coupon = Coupon::factory()->create(['code' => 'WELCOME10', 'value' => 1000]);

    $this->post('/cart/coupon', ['coupon' => 'welcome10'])
        ->assertRedirect('/cart')
        ->assertSessionHas('status');

    expect($cart->fresh()->coupon_id)->toBe($coupon->id);
    expect($cartService->summary($cart->fresh())['discount_cents'])->toBe(5000);

    $this->delete('/cart/coupon')->assertRedirect('/cart');
    expect($cart->fresh()->coupon_id)->toBeNull();
});

test('an invalid coupon is rejected with a translated error', function () {
    [$customer, $cart] = cartForCustomer($this);
    $variant = ProductVariant::factory()->create(['price_cents' => 50000]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    $this->from('/cart')->post('/cart/coupon', ['coupon' => 'NOPE'])
        ->assertSessionHasErrors(['coupon' => __('coupons.errors.not_found')]);
});

test('coupons below the minimum subtotal cannot be applied', function () {
    [$customer, $cart] = cartForCustomer($this);
    $variant = ProductVariant::factory()->create(['price_cents' => 10000]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    Coupon::factory()->create(['code' => 'BIGONLY', 'min_subtotal_cents' => 50000]);

    $this->from('/cart')->post('/cart/coupon', ['coupon' => 'BIGONLY'])
        ->assertSessionHasErrors('coupon');

    expect($cart->fresh()->coupon_id)->toBeNull();
});

test('the cart summary flags items that no longer have enough stock', function () {
    [$customer, $cart, $cartService] = cartForCustomer($this);
    $variant = ProductVariant::factory()->create(['price_cents' => 10000, 'stock' => 1]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 3]);

    $summary = $cartService->summary($cart->fresh());
    expect($summary['has_issues'])->toBeTrue();
    expect($summary['items']->first()['issues'])->toBeTrue();
});

test('coupon per-customer limits apply to guest email addresses too', function () {
    $coupon = Coupon::factory()->create(['per_user_limit' => 1]);
    $email = 'guest-coupon@example.test';

    Order::factory()->create([
        'coupon_id' => $coupon->id,
        'customer_email' => $email,
        'user_id' => null,
        'status' => 'pending',
    ]);

    $error = app(CouponService::class)->errorFor($coupon, 100000, null, $email);
    expect($error)->toBe(__('coupons.errors.per_user_limit'));

    expect(app(CouponService::class)->errorFor($coupon, 100000, null, 'other@example.test'))->toBeNull();
});

test('money conversion between major and minor units is exact', function () {
    expect(Money::toMinor('349.99'))->toBe(34999);
    expect(Money::toMinor('1,250.50'))->toBe(125050);
    expect(Money::format(125050))->toBe('1,250.50');
    expect(Money::toMajor(125050))->toBe('1250.50');
});
