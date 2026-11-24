<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $phone = Phone::normalize($data['phone']);

        if ($phone === null) {
            return back()->withErrors(['phone' => __('validation.matjar.phone')])->withInput();
        }

        if (User::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => __('validation.unique', ['attribute' => __('fields.phone')])])->withInput();
        }

        $user = DB::transaction(function () use ($data, $phone) {
            $customerRole = Role::where('slug', Role::CUSTOMER)->firstOrFail();

            return User::create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'phone' => $phone,
                'password' => $data['password'],
                'role_id' => $customerRole->id,
                'is_active' => true,
            ]);
        });

        Auth::login($user);
        $cartService->mergeGuestCart($user);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('status', __('auth.registered'));
    }
}
