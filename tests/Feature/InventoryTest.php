<?php

use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\StockMovement;
use App\Services\InsufficientStockException;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inventoryService(): InventoryService
{
    return app(InventoryService::class);
}

beforeEach(function () {
    $this->seedRoles();
});

test('stock in, out and adjustment write balanced ledger entries', function () {
    $variant = ProductVariant::factory()->create(['stock' => 0, 'reserved' => 0]);

    inventoryService()->stockIn($variant, 10, null, 'shipment');
    expect($variant->fresh()->stock)->toBe(10);

    inventoryService()->stockOut($variant, 4, null, 'damaged');
    expect($variant->fresh()->stock)->toBe(6);

    inventoryService()->adjustTo($variant, 9, null, 'counted');
    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(9);

    $movements = StockMovement::where('variant_id', $variant->id)->orderBy('id')->get();
    expect($movements)->toHaveCount(3);
    expect($movements[0]->stock_change)->toBe(10);
    expect($movements[0]->stock_after)->toBe(10);
    expect($movements[1]->stock_change)->toBe(-4);
    expect($movements[1]->stock_after)->toBe(6);
    expect($movements[2]->type)->toBe('adjust');
    expect($movements[2]->stock_change)->toBe(3);
    expect($movements[2]->stock_after)->toBe(9);
});

test('a reservation, commit and restore keep the ledger consistent', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10, 'reserved' => 0]);

    inventoryService()->reserve($variant, 3, 'order', 99);
    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(10);
    expect($fresh->reserved)->toBe(3);
    expect($fresh->available_stock)->toBe(7);

    inventoryService()->commit($variant, 3, 'order', 99);
    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(7);
    expect($fresh->reserved)->toBe(0);

    inventoryService()->restore($variant, 3, 'order', 99);
    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(10);
    expect($fresh->reserved)->toBe(0);

    $balances = StockMovement::where('variant_id', $variant->id)->orderBy('id')
        ->get()
        ->map(fn (StockMovement $movement) => [$movement->stock_after, $movement->reserved_after])
        ->all();

    expect($balances)->toBe([[10, 3], [7, 0], [10, 0]]);
});

test('reserving beyond available stock is rejected without touching balances', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5, 'reserved' => 0]);

    inventoryService()->reserve($variant, 5, 'order', 1);

    expect(fn () => inventoryService()->reserve($variant, 1, 'order', 2))
        ->toThrow(InsufficientStockException::class);

    $fresh = $variant->fresh();
    expect($fresh->stock)->toBe(5);
    expect($fresh->reserved)->toBe(5);
    expect(StockMovement::where('variant_id', $variant->id)->where('type', 'reserve')->count())->toBe(1);
});

test('stock out cannot take the physical stock below zero', function () {
    $variant = ProductVariant::factory()->create(['stock' => 2, 'reserved' => 0]);

    expect(fn () => inventoryService()->stockOut($variant, 3))
        ->toThrow(InsufficientStockException::class);

    expect($variant->fresh()->stock)->toBe(2);
    expect(StockMovement::count())->toBe(0);
});

test('an adjustment below the reserved quantity is rejected', function () {
    $variant = ProductVariant::factory()->create(['stock' => 10, 'reserved' => 4]);

    expect(fn () => inventoryService()->adjustTo($variant, 3))
        ->toThrow(InsufficientStockException::class);

    expect($variant->fresh()->stock)->toBe(10);
});

test('inventory screens require staff permissions', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);
    $staff = $this->userWithRole(Role::STAFF);
    $variant = ProductVariant::factory()->create();

    $this->actingAs($customer)->get('/admin/inventory')->assertForbidden();
    $this->actingAs($customer)->post('/admin/inventory/'.$variant->id.'/adjust', ['type' => 'in', 'quantity' => 2])->assertForbidden();

    $this->actingAs($staff)->get('/admin/inventory')->assertOk();
    $this->actingAs($staff)->get('/admin/inventory/'.$variant->id)->assertOk();
});

test('staff can receive stock through the admin form', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $variant = ProductVariant::factory()->create(['stock' => 4]);

    $this->actingAs($staff)->post('/admin/inventory/'.$variant->id.'/adjust', [
        'type' => 'in',
        'quantity' => 6,
        'reason' => 'شحنة جديدة',
    ])->assertSessionHasNoErrors()->assertSessionHas('status');

    expect($variant->fresh()->stock)->toBe(10);
    $movement = StockMovement::where('variant_id', $variant->id)->latest('id')->first();
    expect($movement->type)->toBe('in');
    expect($movement->reason)->toBe('شحنة جديدة');
    expect($movement->user_id)->toBe($staff->id);
});

test('the admin form rejects an out movement larger than stock', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $variant = ProductVariant::factory()->create(['stock' => 3]);

    $this->actingAs($staff)->post('/admin/inventory/'.$variant->id.'/adjust', [
        'type' => 'out',
        'quantity' => 4,
    ])->assertSessionHas('error');

    expect($variant->fresh()->stock)->toBe(3);
});

test('the admin form rejects zero quantity for stock in', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $variant = ProductVariant::factory()->create(['stock' => 3]);

    $this->actingAs($staff)->post('/admin/inventory/'.$variant->id.'/adjust', [
        'type' => 'in',
        'quantity' => 0,
    ])->assertSessionHas('error');

    expect($variant->fresh()->stock)->toBe(3);
});

test('low stock variants appear in the low-stock filter with an alert count', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $low = ProductVariant::factory()->create(['stock' => 2, 'reserved' => 0, 'low_stock_threshold' => 3]);
    $healthy = ProductVariant::factory()->create(['stock' => 50, 'reserved' => 0, 'low_stock_threshold' => 3]);

    $response = $this->actingAs($staff)->get('/admin/inventory?low_stock=1');
    $response->assertOk();
    $response->assertSee($low->sku);
    $response->assertDontSee($healthy->sku);
    $response->assertSee(__('inventory.low_stock_alert', ['count' => 1]));

    $this->actingAs($staff)->get('/admin/inventory')
        ->assertSee($low->sku)
        ->assertSee(__('inventory.status_low'));
});

test('the variant ledger page lists movements with resulting balances', function () {
    $staff = $this->userWithRole(Role::STAFF);
    $variant = ProductVariant::factory()->create(['stock' => 0]);

    inventoryService()->stockIn($variant, 8, $staff, 'shipment');

    $this->actingAs($staff)->get('/admin/inventory/'.$variant->id)
        ->assertOk()
        ->assertSee('+8')
        ->assertSee('shipment')
        ->assertSee('8 / 0');
});
