<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'cart_token';

    public function __construct(
        private readonly CouponService $coupons,
        private readonly ShippingService $shipping,
    ) {}

    public function current(bool $create = true): ?Cart
    {
        $user = auth()->user();

        if ($user) {
            $cart = Cart::query()->where('user_id', $user->id)->first();

            if (! $cart && $create) {
                $cart = Cart::create(['user_id' => $user->id]);
            }

            return $cart;
        }

        $token = session(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            $cart = Cart::query()->where('token', $token)->first();

            if ($cart || ! $create) {
                return $cart;
            }
        }

        if (! $create) {
            return null;
        }

        $token = $this->newToken();
        session([self::SESSION_KEY => $token]);

        return Cart::create(['token' => $token]);
    }

    public function count(): int
    {
        $cart = $this->current(false);

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    public function subtotalCents(): int
    {
        $cart = $this->current(false);

        return $cart ? $this->subtotalFor($cart) : 0;
    }

    public function add(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        $this->assertPurchasable($variant);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => __('cart.errors.quantity_min')]);
        }

        $maxPerLine = (int) config('matjar.inventory.max_quantity_per_cart_line', 20);
        $item = $cart->items()->where('variant_id', $variant->id)->first();
        $requested = ($item?->quantity ?? 0) + $quantity;

        if ($requested > $maxPerLine) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.line_max', ['max' => $maxPerLine]),
            ]);
        }

        if ($requested > $variant->available_stock) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.not_enough_stock', ['available' => $variant->available_stock]),
            ]);
        }

        if ($item) {
            $item->update(['quantity' => $requested]);

            return $item;
        }

        return $cart->items()->create([
            'variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    public function update(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => __('cart.errors.quantity_min')]);
        }

        $maxPerLine = (int) config('matjar.inventory.max_quantity_per_cart_line', 20);

        if ($quantity > $maxPerLine) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.line_max', ['max' => $maxPerLine]),
            ]);
        }

        $variant = $item->variant;

        if (! $variant || $quantity > $variant->available_stock) {
            throw ValidationException::withMessages([
                'quantity' => __('cart.errors.not_enough_stock', ['available' => $variant?->available_stock ?? 0]),
            ]);
        }

        $item->update(['quantity' => $quantity]);
    }

    public function applyCoupon(Cart $cart, string $code): Coupon
    {
        $coupon = Coupon::findByCode($code);

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon' => __('coupons.errors.not_found')]);
        }

        $subtotal = $this->subtotalFor($cart);
        $error = $this->coupons->errorFor($coupon, $subtotal, auth()->user());

        if ($error !== null) {
            throw ValidationException::withMessages(['coupon' => $error]);
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Cart $cart, ?Governorate $governorate = null): array
    {
        $cart->loadMissing(['items.variant.product', 'coupon']);

        $items = $cart->items->map(function (CartItem $item) {
            return [
                'item' => $item,
                'variant' => $item->variant,
                'product' => $item->variant?->product,
                'quantity' => $item->quantity,
                'unit_price_cents' => (int) ($item->variant?->price_cents ?? 0),
                'line_total_cents' => $item->line_total_cents,
                'available' => $item->variant?->available_stock ?? 0,
                'issues' => ! $item->variant
                    || ! $item->variant->is_active
                    || ! $item->variant->product?->is_active
                    || $item->quantity > ($item->variant->available_stock ?? 0),
            ];
        });

        $subtotal = (int) $items->sum('line_total_cents');
        $coupon = $cart->coupon;
        $discount = 0;
        $couponError = null;

        if ($coupon) {
            $couponError = $this->coupons->errorFor($coupon, $subtotal, $cart->user ?? auth()->user());

            if ($couponError === null) {
                $discount = $this->coupons->discount($coupon, $subtotal);
            }
        }

        $afterDiscount = max(0, $subtotal - $discount);
        $shippingQuote = $governorate
            ? $this->shipping->quote($governorate, $afterDiscount)
            : [
                'available' => false,
                'zone' => null,
                'shipping_cents' => 0,
                'free' => false,
                'remaining_cents' => null,
                'delivery_days_min' => null,
                'delivery_days_max' => null,
            ];

        return [
            'items' => $items,
            'item_count' => (int) $items->sum('quantity'),
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'shipping_cents' => (int) $shippingQuote['shipping_cents'],
            'total_cents' => max(0, $afterDiscount + (int) $shippingQuote['shipping_cents']),
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'shipping' => $shippingQuote,
            'has_issues' => $items->contains('issues', true),
            'is_empty' => $items->isEmpty(),
            'subtotal_formatted' => Money::withCurrency($subtotal),
            'discount_formatted' => Money::withCurrency($discount),
            'shipping_formatted' => Money::withCurrency((int) $shippingQuote['shipping_cents']),
            'total_formatted' => Money::withCurrency(max(0, $afterDiscount + (int) $shippingQuote['shipping_cents'])),
        ];
    }

    /** Move the session guest cart into the authenticated user's cart. */
    public function mergeGuestCart(User $user): void
    {
        $token = session(self::SESSION_KEY);

        if (! is_string($token) || $token === '') {
            return;
        }

        $guest = Cart::query()->where('token', $token)->with('items.variant')->first();

        if (! $guest) {
            session()->forget(self::SESSION_KEY);

            return;
        }

        DB::transaction(function () use ($guest, $user) {
            $target = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            if (! $target->coupon_id && $guest->coupon_id) {
                $target->update(['coupon_id' => $guest->coupon_id]);
            }

            $maxPerLine = (int) config('matjar.inventory.max_quantity_per_cart_line', 20);

            foreach ($guest->items as $item) {
                $available = $item->variant?->available_stock ?? 0;
                $existing = $target->items()->where('variant_id', $item->variant_id)->first();
                $combined = min(($existing?->quantity ?? 0) + $item->quantity, max(0, $available), $maxPerLine);

                if ($combined < 1) {
                    continue;
                }

                $target->items()->updateOrCreate(
                    ['variant_id' => $item->variant_id],
                    ['quantity' => $combined],
                );
            }

            $guest->items()->delete();
            $guest->delete();
        });

        session()->forget(self::SESSION_KEY);
    }

    private function subtotalFor(Cart $cart): int
    {
        return (int) $cart->items()
            ->with('variant')
            ->get()
            ->sum(fn (CartItem $item) => $item->quantity * (int) ($item->variant?->price_cents ?? 0));
    }

    private function assertPurchasable(ProductVariant $variant): void
    {
        if (! $variant->is_active || ! $variant->product?->is_active) {
            throw ValidationException::withMessages(['variant_id' => __('cart.errors.unavailable')]);
        }
    }

    private function newToken(): string
    {
        do {
            $token = Str::random(40);
        } while (Cart::query()->where('token', $token)->exists());

        return $token;
    }
}
