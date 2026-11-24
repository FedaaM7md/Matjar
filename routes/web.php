<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\DemoController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/shipping-returns', [PageController::class, 'shipping'])->name('pages.shipping');
Route::get('/size-guide', [PageController::class, 'sizeGuide'])->name('pages.size-guide');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');

Route::middleware('demo')->group(function () {
    Route::get('/demo', [DemoController::class, 'show'])->name('demo.enter');
    Route::post('/demo/login', [DemoController::class, 'login'])->middleware('throttle:20,1')->name('demo.login');
});

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/category/{category}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/product/{product}', [ShopController::class, 'show'])->name('shop.product');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.add');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

Route::get('/orders/{order}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

Route::get('/pay/{payment}', [PaymentController::class, 'show'])->name('payments.show');
Route::post('/pay/{payment}/callback', [PaymentController::class, 'callback'])->middleware('throttle:20,1')->name('payments.callback');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'can:customer'])->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:staff'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::middleware('can:inventory.view')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/{variant}', [InventoryController::class, 'show'])->name('inventory.show');
    });

    Route::middleware('can:inventory.manage')->group(function () {
        Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    });

    Route::middleware('can:orders.view')->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    });

    Route::post('orders/{order}/transition', [AdminOrderController::class, 'transition'])
        ->middleware('can:orders.manage')
        ->name('orders.transition');

    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('can:customers.view')
        ->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('can:customers.view')
        ->name('customers.show');

    Route::get('coupons', [CouponController::class, 'index'])
        ->middleware('can:coupons.view')
        ->name('coupons.index');

    Route::middleware('can:coupons.manage')->group(function () {
        Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
        Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
    });

    Route::get('shipping', [ShippingController::class, 'index'])
        ->middleware('can:shipping.view')
        ->name('shipping.index');
    Route::post('shipping/{governorate}', [ShippingController::class, 'update'])
        ->middleware('can:shipping.manage')
        ->name('shipping.update');

    Route::get('settings', [SettingsController::class, 'edit'])
        ->middleware('can:settings.view')
        ->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])
        ->middleware('can:settings.manage')
        ->name('settings.update');

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('can:reports.view')
        ->name('reports.index');

    Route::get('reports/export', [ReportController::class, 'export'])
        ->middleware('can:reports.view')
        ->name('reports.export');

    Route::middleware('can:products.view')->group(function () {
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
    });

    Route::middleware('can:products.manage')->group(function () {
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('variants.store');
        Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');
        Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('images.store');
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('images.destroy');
    });
});
