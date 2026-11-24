<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(DemoUserSeeder::class);
    config(['matjar.demo.enabled' => true]);
});

test('the demo entry page lists the three roles', function () {
    $this->get('/demo')
        ->assertOk()
        ->assertSee(__('demo.role_admin'))
        ->assertSee(__('demo.role_staff'))
        ->assertSee(__('demo.role_customer'));
});

test('one-click demo login signs in as the chosen role', function (string $role) {
    $response = $this->post('/demo/login', ['role' => $role]);

    $this->assertAuthenticated();

    $user = auth()->user();
    expect($user->isDemo())->toBeTrue();
    expect($user->is_active)->toBeTrue();

    if ($role === 'customer') {
        $response->assertRedirect('/account');
    } else {
        $response->assertRedirect('/admin');
    }
})->with(['admin', 'staff', 'customer']);

test('demo login rejects an unknown role and keeps the visitor a guest', function () {
    $this->post('/demo/login', ['role' => 'owner'])
        ->assertSessionHasErrors('role');

    $this->assertGuest();
});

test('demo users are flagged so demo-only rules can be applied', function () {
    expect(User::where('is_demo', true)->count())->toBe(3);
    expect(User::where('is_demo', true)->whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->exists())->toBeTrue();
});

test('demo entry points disappear and routes return 404 when demo mode is off', function () {
    config(['matjar.demo.enabled' => false]);

    $this->get('/demo')->assertNotFound();
    $this->post('/demo/login', ['role' => 'admin'])->assertNotFound();

    $this->get('/')
        ->assertOk()
        ->assertDontSee(__('demo.launch'))
        ->assertDontSee(__('demo.banner'));
});

test('the demo entry and discreet footer note are visible while demo mode is enabled', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(__('demo.launch'))
        ->assertSee(__('common.demo_footer_note'))
        ->assertDontSee(__('demo.banner'));
});

test('demo accounts cannot change their email or password', function () {
    $user = User::where('email', 'customer@matjar.test')->firstOrFail();

    $this->actingAs($user)->put('/account/profile', [
        'name' => 'اسم جديد',
        'email' => 'new-demo@example.test',
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
        'current_password' => 'Demo12345',
    ])->assertForbidden();

    $user->refresh();
    expect($user->email)->toBe('customer@matjar.test');
    expect($user->password)->not->toBe('newsecret123');
});

test('a non-demo customer can update their profile', function () {
    $role = Role::where('slug', Role::CUSTOMER)->firstOrFail();
    $user = User::factory()->create([
        'role_id' => $role->id,
        'is_active' => true,
        'is_demo' => false,
        'password' => 'secret123',
    ]);

    $this->actingAs($user)->put('/account/profile', [
        'name' => 'منى أحمد',
        'email' => 'mona.updated@example.test',
    ])->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('منى أحمد');
    expect($user->email)->toBe('mona.updated@example.test');
});

test('the account page shows the demo lock notice instead of the profile form', function () {
    $user = User::where('email', 'customer@matjar.test')->firstOrFail();

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSee(__('demo.note'))
        ->assertDontSee(route('account.profile.update'), false);
});
