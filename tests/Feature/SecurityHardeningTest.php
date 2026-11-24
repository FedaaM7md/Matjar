<?php

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use Database\Seeders\GovernorateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->seed(GovernorateSeeder::class);
});

test('security headers are present on public and admin responses', function () {
    $response = $this->get('/');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    $response->assertHeaderMissing('Strict-Transport-Security');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'nonce-")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'");
    // Scripts are nonce-gated only: no unsafe-inline and no unsafe-eval.
    expect($csp)->not->toContain("script-src 'self' 'unsafe-inline'");
    expect($csp)->not->toContain("'unsafe-eval'");

    $admin = $this->userWithRole(Role::ADMIN);
    $this->actingAs($admin)->get('/admin')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // Error pages rendered outside the middleware pipeline still get the headers.
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY');
});

test('content security policy nonce is applied to the vite script tag', function () {
    $response = $this->get('/');

    $csp = (string) $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([^']+)'/", $csp, $matches);
    expect($matches[1] ?? '')->not->toBeEmpty();

    $response->assertSee('nonce="'.$matches[1].'"', false);
});

test('not-found and forbidden pages are branded and localised', function () {
    config(['app.debug' => false]);

    $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee(__('errors.404.title'));

    $customer = $this->userWithRole(Role::CUSTOMER);
    $this->actingAs($customer)->get('/admin')->assertForbidden()->assertSee(__('errors.403.title'));
});

test('product names and descriptions are escaped to prevent stored XSS', function () {
    $product = Product::factory()->create([
        'name_ar' => '<script>alert(1)</script>',
        'name_en' => '<script>alert(1)</script>',
    ]);
    ProductVariant::factory()->for($product)->create();

    $response = $this->get('/shop?q=script');
    $response->assertOk();
    $response->assertDontSee('<script>alert(1)</script>', false);
    $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

test('coupon attempts are throttled', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    $cart = Cart::create(['user_id' => $customer->id]);
    $cart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);
    $this->actingAs($customer);

    for ($i = 0; $i < 10; $i++) {
        $this->post('/cart/coupon', ['coupon' => 'NOTREAL']);
    }

    $this->post('/cart/coupon', ['coupon' => 'NOTREAL'])->assertStatus(429);
});

test('checkout attempts are throttled', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/checkout', []);
    }

    $this->post('/checkout', [])->assertStatus(429);
});

test('expired signed order links are rejected while valid signatures work', function () {
    $order = Order::factory()->create();

    $expired = URL::temporarySignedRoute('orders.show', now()->subMinute(), ['order' => $order->number]);
    $this->get($expired)->assertForbidden();

    $valid = URL::temporarySignedRoute('orders.show', now()->addHour(), ['order' => $order->number]);
    $this->get($valid)->assertOk()->assertSee($order->number);
});

test('English pages render LTR and translated labels', function () {
    $product = Product::factory()->create(['name_ar' => 'منتج عربي', 'name_en' => 'English Product']);
    ProductVariant::factory()->for($product)->create();

    $this->post('/locale/en');

    foreach (['/', '/shop', '/product/'.$product->slug, '/cart', '/login'] as $path) {
        $response = $this->get($path);
        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
    }

    $this->get('/shop')->assertSee('English Product');
});

test('Arabic validation messages are shown for invalid Egyptian phone numbers', function () {
    $this->from('/register')->post('/register', [
        'name' => 'اسم',
        'email' => 'invalid-phone@example.test',
        'phone' => '5555',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertSessionHasErrors([
        'phone' => __('validation.matjar.phone'),
    ]);
});

test('passwords and remember tokens are never included in responses', function () {
    $customer = $this->userWithRole(Role::CUSTOMER, ['password' => 'secret123']);

    $this->actingAs($customer)->get('/account')
        ->assertOk()
        ->assertDontSee('secret123')
        ->assertDontSee((string) $customer->remember_token);
});

test('unsupported HTTP methods on admin mutations are rejected', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->get('/admin/settings')->assertOk();
    $this->actingAs($admin)->post('/admin/settings', [])->assertStatus(405);
    $this->actingAs($admin)->delete('/admin/orders')->assertStatus(405);
});
