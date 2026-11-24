<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

function addToCart($test, ProductVariant $variant, int $quantity, ?User $user = null)
{
    if ($user) {
        $test->actingAs($user);
    }

    return $test->post('/cart/add', [
        'variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);
}

test('a guest can add a product to the cart', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10, 'reserved' => 0]);

    $response = addToCart($this, $variant, 2);

    $response->assertRedirect('/cart');
    $response->assertSessionHas('status');
    expect(session('cart_token'))->not->toBeNull();
    expect(CartItem::where('variant_id', $variant->id)->first()->quantity)->toBe(2);
});

test('adding the same variant again increases its quantity', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);

    addToCart($this, $variant, 2);
    addToCart($this, $variant, 3);

    expect(CartItem::where('variant_id', $variant->id)->count())->toBe(1);
    expect(CartItem::first()->quantity)->toBe(5);
});

test('a guest cannot add more than the available stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 3, 'reserved' => 0]);

    addToCart($this, $variant, 4)->assertSessionHasErrors('quantity');

    expect(CartItem::count())->toBe(0);
});

test('reserved stock reduces what can be added to the cart', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5, 'reserved' => 4]);

    addToCart($this, $variant, 2)->assertSessionHasErrors('quantity');
    addToCart($this, $variant, 1)->assertSessionHasNoErrors();
});

test('inactive variants cannot be added', function () {
    $variant = ProductVariant::factory()->create(['is_active' => false, 'stock' => 10]);

    addToCart($this, $variant, 1)->assertSessionHasErrors('variant_id');
    expect(CartItem::count())->toBe(0);
});

test('the per-line maximum quantity is enforced', function () {
    $variant = ProductVariant::factory()->create(['stock' => 100]);

    addToCart($this, $variant, 19);
    addToCart($this, $variant, 2)->assertSessionHasErrors('quantity');

    expect(CartItem::first()->quantity)->toBe(19);
});

test('cart quantities can be updated and items removed', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    addToCart($this, $variant, 2);

    $item = CartItem::first();

    $this->patch('/cart/items/'.$item->id, ['quantity' => 4])->assertRedirect('/cart');
    expect($item->fresh()->quantity)->toBe(4);

    $this->delete('/cart/items/'.$item->id)->assertRedirect('/cart');
    expect(CartItem::count())->toBe(0);
});

test('a cart item from another cart cannot be modified', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    $otherCart = Cart::create(['token' => 'other-token']);
    $otherItem = $otherCart->items()->create(['variant_id' => $variant->id, 'quantity' => 1]);

    addToCart($this, $variant, 1);

    $this->patch('/cart/items/'.$otherItem->id, ['quantity' => 5])->assertNotFound();
    $this->delete('/cart/items/'.$otherItem->id)->assertNotFound();
});

test('the cart page shows line totals and the item count', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10, 'price_cents' => 34900]);
    addToCart($this, $variant, 2);

    $this->get('/cart')
        ->assertOk()
        ->assertSee('698.00')
        ->assertSee('عدد القطع: 2');
});

test('a guest cart is merged into the customer cart at login', function () {
    $variant = ProductVariant::factory()->create(['stock' => 20]);
    $customer = $this->userWithRole(Role::CUSTOMER, [
        'email' => 'merge@example.test',
        'password' => Hash::make('secret123'),
    ]);

    addToCart($this, $variant, 3);

    $this->post('/login', ['email' => 'merge@example.test', 'password' => 'secret123']);

    $userCart = Cart::where('user_id', $customer->id)->first();
    expect($userCart)->not->toBeNull();
    expect($userCart->items()->where('variant_id', $variant->id)->first()->quantity)->toBe(3);
    expect(Cart::whereNotNull('token')->count())->toBe(0);
});

test('merged quantities are capped by available stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 4]);
    $customer = $this->userWithRole(Role::CUSTOMER, [
        'email' => 'merge2@example.test',
        'password' => Hash::make('secret123'),
    ]);

    $userCart = Cart::create(['user_id' => $customer->id]);
    $userCart->items()->create(['variant_id' => $variant->id, 'quantity' => 3]);

    addToCart($this, $variant, 3);

    $this->post('/login', ['email' => 'merge2@example.test', 'password' => 'secret123']);

    expect($userCart->fresh()->items()->first()->quantity)->toBe(4);
});

test('the header shows the number of cart units', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10]);
    addToCart($this, $variant, 2);

    $this->get('/')->assertOk()->assertSee('data-cart-count="2"', false);
});
