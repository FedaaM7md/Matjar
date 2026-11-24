<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Static, information pages for the fictional storefront (Qoton).
 */
class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function shipping(): View
    {
        return view('pages.shipping');
    }

    public function sizeGuide(): View
    {
        return view('pages.size-guide');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }
}
