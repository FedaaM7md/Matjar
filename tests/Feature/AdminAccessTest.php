<?php

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

test('guests are redirected from the admin panel to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('customers cannot enter the admin panel', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});

test('staff can view the dashboard', function () {
    $staff = $this->userWithRole(Role::STAFF);

    $this->actingAs($staff)->get('/admin')->assertOk();
});

test('admin can view the dashboard', function () {
    $admin = $this->userWithRole(Role::ADMIN);

    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('admin holds every permission while staff holds only the operational subset', function () {
    $admin = $this->userWithRole(Role::ADMIN);
    $staff = $this->userWithRole(Role::STAFF);

    foreach (array_keys(Permissions::all()) as $permission) {
        expect($admin->hasPermission($permission))->toBeTrue();
    }

    expect($staff->hasPermission(Permissions::ORDERS_MANAGE))->toBeTrue();
    expect($staff->hasPermission(Permissions::PRODUCTS_MANAGE))->toBeTrue();
    expect($staff->hasPermission(Permissions::SETTINGS_MANAGE))->toBeFalse();
    expect($staff->hasPermission(Permissions::COUPONS_MANAGE))->toBeFalse();
    expect($staff->hasPermission(Permissions::SHIPPING_MANAGE))->toBeFalse();
});

test('customers have no admin permissions', function () {
    $customer = $this->userWithRole(Role::CUSTOMER);

    expect($customer->hasPermission(Permissions::DASHBOARD_VIEW))->toBeFalse();
    expect($customer->canAccessAdmin())->toBeFalse();
});

test('inactive staff are refused the admin panel even when authenticated', function () {
    $staff = $this->userWithRole(Role::STAFF, ['is_active' => false]);

    $this->actingAs($staff)->get('/admin')->assertForbidden();
});
