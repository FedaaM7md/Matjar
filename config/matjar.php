<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store identity
    |--------------------------------------------------------------------------
    */
    'currency' => 'EGP',
    'currency_minor_units' => 2,
    'timezone' => 'Africa/Cairo',

    /*
    |--------------------------------------------------------------------------
    | Checkout / payments
    |--------------------------------------------------------------------------
    | The only online payment option is a local simulated gateway. It signs
    | its callback with the application key; no external service is called.
    */
    'payment' => [
        'fake_gateway' => env('MATJAR_FAKE_GATEWAY', true),
        'callback_ttl_minutes' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory defaults
    |--------------------------------------------------------------------------
    */
    'inventory' => [
        'low_stock_threshold' => 5,
        'max_quantity_per_cart_line' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Egyptian mobile numbers (local format 01xxxxxxxxx after normalisation)
    |--------------------------------------------------------------------------
    */
    'phone' => [
        'pattern' => '/^01[0125][0-9]{8}$/',
        'example' => '01012345678',
    ],

    'locales' => [
        'ar' => ['name' => 'العربية', 'dir' => 'rtl', 'short' => 'ع'],
        'en' => ['name' => 'English', 'dir' => 'ltr', 'short' => 'EN'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    | When enabled, visitors can enter as a seeded demo user with one click and
    | the data is reset on a schedule. When disabled, demo routes return 404
    | and demo entry points disappear from the UI.
    */
    'demo' => [
        'enabled' => (bool) env('DEMO_MODE', true),
        'reset_interval_hours' => 1,
        // Demo accounts get a tighter upload limit than real staff.
        'upload_max_kb' => 2048,
        'roles' => [
            'admin' => ['email' => 'admin@matjar.test', 'label' => 'demo.role_admin'],
            'staff' => ['email' => 'staff@matjar.test', 'label' => 'demo.role_staff'],
            'customer' => ['email' => 'customer@matjar.test', 'label' => 'demo.role_customer'],
        ],
    ],
];
