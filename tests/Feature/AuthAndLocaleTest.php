<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

test('home page renders in Arabic RTL by default', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('dir="rtl"', false);
    $response->assertSee('lang="ar"', false);
    $response->assertSee('قُطن');
});

test('language switch stores the locale and renders LTR English', function () {
    $this->from('/')->post('/locale/en')->assertRedirect('/');

    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('dir="ltr"', false);
    $response->assertSee('lang="en"', false);
    $response->assertSee('Qoton');
});

test('unsupported locale returns 404', function () {
    $this->post('/locale/fr')->assertNotFound();
});

test('a customer can register and is signed in with the customer role', function () {
    $response = $this->post('/register', [
        'name' => 'منى أحمد',
        'email' => 'mona@example.test',
        'phone' => '+20 101 234 5678',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect('/account');

    $user = User::where('email', 'mona@example.test')->first();
    expect($user)->not->toBeNull();
    expect($user->phone)->toBe('01012345678');
    expect($user->role->slug)->toBe(Role::CUSTOMER);
    $this->assertAuthenticatedAs($user);
});

test('registration rejects an invalid Egyptian phone number', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'bad-phone@example.test',
        'phone' => '12345',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('phone');
    expect(User::where('email', 'bad-phone@example.test')->exists())->toBeFalse();
});

test('registration requires a strong enough password', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'weak@example.test',
        'phone' => '01012345678',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
});

test('login regenerates the session and redirects customers to their account', function () {
    $role = Role::where('slug', Role::CUSTOMER)->first();
    $user = User::factory()->create([
        'email' => 'customer@example.test',
        'password' => Hash::make('secret123'),
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'customer@example.test',
        'password' => 'secret123',
    ]);

    $response->assertRedirect('/account');
    $this->assertAuthenticatedAs($user);
});

test('login is throttled after five failed attempts', function () {
    User::factory()->create([
        'email' => 'throttle@example.test',
        'password' => Hash::make('secret123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'throttle@example.test', 'password' => 'wrong']);
    }

    $response = $this->from('/login')->post('/login', [
        'email' => 'throttle@example.test',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an inactive account cannot log in', function () {
    User::factory()->create([
        'email' => 'inactive@example.test',
        'password' => Hash::make('secret123'),
        'is_active' => false,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'inactive@example.test',
        'password' => 'secret123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin users are redirected to the dashboard after login', function () {
    $this->userWithRole(Role::ADMIN, [
        'email' => 'admin@example.test',
        'password' => Hash::make('secret123'),
    ]);

    $this->post('/login', ['email' => 'admin@example.test', 'password' => 'secret123'])
        ->assertRedirect('/admin');
});

test('logout invalidates the authenticated session', function () {
    $user = $this->userWithRole(Role::CUSTOMER);

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});
