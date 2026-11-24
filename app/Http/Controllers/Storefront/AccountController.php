<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->withCount('items')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $stats = [
            'orders' => $user->orders()->count(),
            'active' => $user->orders()->whereIn('status', ['pending', 'confirmed', 'shipped'])->count(),
            'spent' => (int) $user->orders()->where('payment_status', Order::PAYMENT_PAID)->sum('total_cents'),
        ];

        return view('account.index', [
            'user' => $user,
            'recentOrders' => $orders,
            'stats' => $stats,
        ]);
    }

    public function orders(Request $request): View
    {
        $status = (string) $request->query('status');

        $orders = $request->user()->orders()
            ->withCount('items')
            ->when(in_array($status, array_keys(Order::statuses()), true), fn (Builder $query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('account.orders', [
            'orders' => $orders,
            'status' => $status,
        ]);
    }

    /**
     * Update the signed-in customer's name, email or password. Demo accounts are
     * explicitly forbidden (403) rather than relying on the absence of a form.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->isDemo(), 403, __('demo.restricted'));

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->name = trim($data['name']);
        $user->email = strtolower(trim($data['email']));

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('status', __('account.profile_updated'));
    }
}
