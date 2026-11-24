<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DemoController extends Controller
{
    /**
     * Show the demo role picker. Visitors can enter as admin, staff or customer.
     */
    public function show(): View
    {
        return view('demo.enter', [
            'roles' => (array) config('matjar.demo.roles'),
        ]);
    }

    /**
     * Sign the visitor in instantly as the demo user for the chosen role.
     * No password is required; the route is throttled and only works while
     * DEMO_MODE is enabled.
     */
    public function login(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:admin,staff,customer'],
        ]);

        $role = $data['role'];
        $email = (string) config("matjar.demo.roles.{$role}.email");

        $user = User::query()
            ->where('email', $email)
            ->where('is_demo', true)
            ->where('is_active', true)
            ->first();

        abort_if($user === null, 404);

        Auth::login($user);
        $cartService->mergeGuestCart($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        $message = __('demo.signed_in', ['role' => __('demo.role_'.$role)]);

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard')->with('status', $message);
        }

        return redirect()->route('account.index')->with('status', $message);
    }
}
