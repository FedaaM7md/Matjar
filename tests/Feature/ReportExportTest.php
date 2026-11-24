<?php

use App\Models\Order;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

test('an admin can export reports as a formula-safe CSV', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    Order::factory()->create([
        'customer_name' => '=HYPERLINK("http://example.test")',
        'placed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/admin/reports/export');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    expect($csv)->toContain('رقم الطلب');
    expect($csv)->toContain("'=HYPERLINK");
});

test('a customer cannot export reports', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);

    $this->actingAs($customer)->get('/admin/reports/export')->assertForbidden();
});
