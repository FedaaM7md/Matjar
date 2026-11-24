<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InsufficientStockException;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(CartService $cartService): View|RedirectResponse
    {
        $cart = $cartService->current(false);

        if (! $cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->with('error', __('checkout.errors.empty_cart'));
        }

        $summary = $cartService->summary($cart);

        if ($summary['has_issues']) {
            return redirect()->route('cart.index')->with('error', __('checkout.errors.cart_issues'));
        }

        $governorates = Governorate::query()
            ->with('zone')
            ->whereHas('zone', fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get();

        $zones = $governorates->map(fn (Governorate $governorate) => [
            'id' => $governorate->id,
            'name' => $governorate->name,
            'rate_cents' => $governorate->zone->rate_cents,
            'free_threshold_cents' => $governorate->zone->free_threshold_cents,
            'delivery_days_min' => $governorate->zone->delivery_days_min,
            'delivery_days_max' => $governorate->zone->delivery_days_max,
        ])->values();

        $codEnabled = Setting::bool('cod_enabled', true);
        $gatewayEnabled = Setting::bool('fake_gateway_enabled', (bool) config('matjar.payment.fake_gateway'));

        return view('checkout.index', [
            'cart' => $cart,
            'summary' => $summary,
            'governorates' => $governorates,
            'zones' => $zones,
            'codEnabled' => $codEnabled,
            'gatewayEnabled' => $gatewayEnabled,
            'defaultPayment' => $codEnabled ? Order::PAYMENT_METHOD_COD : Order::PAYMENT_METHOD_GATEWAY,
            'currencyLabel' => __('common.egp'),
        ]);
    }

    public function store(Request $request, CartService $cartService, CheckoutService $checkout): RedirectResponse
    {
        $cart = $cartService->current(false);

        if (! $cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->with('error', __('checkout.errors.empty_cart'));
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail) {
                if (Phone::normalize(is_string($value) ? $value : null) === null) {
                    $fail(__('validation.matjar.phone'));
                }
            }],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in([Order::PAYMENT_METHOD_COD, Order::PAYMENT_METHOD_GATEWAY])],
        ]);

        $phone = Phone::normalize($data['customer_phone']);

        $data['customer_phone'] = $phone;
        $data['customer_name'] = trim($data['customer_name']);
        $data['customer_email'] = isset($data['customer_email']) ? strtolower(trim($data['customer_email'])) : null;

        try {
            $result = $checkout->place($cart, $data, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (InsufficientStockException $exception) {
            return back()->with('error', __('cart.errors.not_enough_stock', ['available' => max(0, $exception->available)]))->withInput();
        }

        $order = $result['order'];
        $payment = $result['payment'];

        if ($payment) {
            return redirect()->route('payments.show', $payment->reference)
                ->with('status', __('checkout.placed_pending_payment'));
        }

        return $this->confirmationRedirect($request, $order)
            ->with('status', __('checkout.placed'));
    }

    public function confirmationRedirect(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->id === $order->user_id || $user->canAccessAdmin())) {
            return redirect()->route('orders.confirmation', $order->number);
        }

        return redirect(URL::temporarySignedRoute(
            'orders.confirmation',
            now()->addDays(7),
            ['order' => $order->number],
        ));
    }
}
