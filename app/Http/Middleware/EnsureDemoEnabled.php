<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns 404 for every demo route when demo mode is disabled, so the demo
 * entry points disappear completely instead of merely being hidden in the UI.
 */
class EnsureDemoEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('matjar.demo.enabled'), 404);

        return $next($request);
    }
}
