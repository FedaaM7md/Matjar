<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the information pages render', function (string $path, string $titleKey) {
    $this->get($path)
        ->assertOk()
        ->assertSee(__($titleKey));
})->with([
    ['/about', 'pages.about.title'],
    ['/contact', 'pages.contact.title'],
    ['/shipping-returns', 'pages.shipping.title'],
    ['/size-guide', 'pages.size_guide.title'],
    ['/privacy', 'pages.privacy.title'],
    ['/terms', 'pages.terms.title'],
]);

test('the home page exposes open graph metadata and a quiet footer credit', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('property="og:title"', false);
    $response->assertSee('property="og:image"', false);
    $response->assertSee('twitter:card', false);
    $response->assertSee('https://fedaamohamed.com', false);

    // The owner is credited only in the footer, never under the logo.
    $response->assertDontSee('BY FEDAA', false);
    $response->assertDontSee('من فداء');
    $response->assertSee('نسخة عرض');
});

test('the contact page uses clearly fake contact details', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertSee('01000000000')
        ->assertSee('hello@qoton.example.com');
});

test('the size guide lists the full size range', function () {
    $this->get('/size-guide')
        ->assertOk()
        ->assertSee('S')
        ->assertSee('XXL');
});

test('the storefront brand is Qoton, not the previous name', function () {
    $response = $this->get('/');

    $response->assertSee(__('common.app_name'));
    $response->assertSee(__('common.app_tagline'));
    $response->assertDontSee('FEDAA MATJAR');
});
