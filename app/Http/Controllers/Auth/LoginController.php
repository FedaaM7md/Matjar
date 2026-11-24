<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower(trim($credentials['email']));
        $throttleKey = 'login:'.Str::transliterate($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttled', ['seconds' => $seconds]),
            ]);
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();
        $cartService->mergeGuestCart($user);

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('status', __('auth.welcome_back', ['name' => $user->name]));
        }

        return redirect()->route('account.index')
            ->with('status', __('auth.welcome_back', ['name' => $user->name]));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('auth.logged_out'));
    }
}
