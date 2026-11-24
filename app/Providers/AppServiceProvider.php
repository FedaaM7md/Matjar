<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\User;
use App\Services\CartService;
use App\Support\Permissions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.tailwind');
        Paginator::defaultSimpleView('pagination.tailwind');

        foreach (array_keys(Permissions::all()) as $slug) {
            Gate::define($slug, function (User $user) use ($slug): bool {
                return $user->isActive() && $user->hasPermission($slug);
            });
        }

        Gate::define('staff', fn (User $user): bool => $user->canAccessAdmin());
        Gate::define('customer', fn (User $user): bool => $user->isActive());

        View::composer('*', function ($view) {
            $locale = app()->getLocale();
            $locales = (array) config('matjar.locales');

            $view->with([
                'locale' => $locale,
                'textDir' => $locales[$locale]['dir'] ?? 'rtl',
                'otherLocale' => $locale === 'ar' ? 'en' : 'ar',
            ]);
        });

        View::composer('layouts.app', function ($view) {
            try {
                $view->with('navCategories', Category::query()
                    ->active()
                    ->orderBy('position')
                    ->orderBy('name_ar')
                    ->limit(6)
                    ->get());
            } catch (\Throwable) {
                $view->with('navCategories', collect());
            }

            try {
                $view->with('cartCount', app(CartService::class)->count());
            } catch (\Throwable) {
                $view->with('cartCount', 0);
            }

            try {
                $view->with('cartSubtotalCents', app(CartService::class)->subtotalCents());
            } catch (\Throwable) {
                $view->with('cartSubtotalCents', 0);
            }
        });
    }
}
