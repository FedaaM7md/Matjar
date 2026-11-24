<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartService $cartService): View
    {
        $cart = $cartService->current(false);

        if (! $cart) {
            return view('cart.index', [
                'cart' => null,
                'summary' => null,
            ]);
        }

        return view('cart.index', [
            'cart' => $cart,
            'summary' => $cartService->summary($cart),
        ]);
    }

    public function store(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['variant_id']);

        try {
            $cartService->add($cartService->current(), $variant, (int) $data['quantity']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()->route('cart.index')->with('status', __('cart.added'));
    }

    public function update(Request $request, CartItem $item, CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current(false);
        abort_unless($cart && $item->cart_id === $cart->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $cartService->update($item, (int) $data['quantity']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('cart.index')->with('status', __('cart.updated'));
    }

    public function destroy(CartItem $item, CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current(false);
        abort_unless($cart && $item->cart_id === $cart->id, 404);

        $item->delete();

        return redirect()->route('cart.index')->with('status', __('cart.removed'));
    }

    public function applyCoupon(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'coupon' => ['required', 'string', 'max:40'],
        ]);

        $cart = $cartService->current(false);
        abort_unless($cart, 404);

        try {
            $coupon = $cartService->applyCoupon($cart, $data['coupon']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('cart.index')->with('status', __('cart.coupon_applied', ['code' => $coupon->code]));
    }

    public function removeCoupon(CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current(false);
        abort_unless($cart, 404);

        $cartService->removeCoupon($cart);

        return redirect()->route('cart.index')->with('status', __('cart.coupon_removed'));
    }
}
