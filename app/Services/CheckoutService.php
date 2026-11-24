<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Governorate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CouponService $coupons,
        private readonly ShippingService $shipping,
        private readonly OrderNumberService $numbers,
        private readonly OrderService $orders,
    ) {}

    /**
     * Place an order from the cart inside one transaction: price, coupon,
     * shipping and stock are all re-validated server-side.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, payment: Payment|null}
     */
    public function place(Cart $cart, array $data, ?User $user): array
    {
        return DB::transaction(function () use ($cart, $data, $user) {
            $cart->loadMissing('items');
            $items = $cart->items;

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['checkout' => __('checkout.errors.empty_cart')]);
            }

            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $items->pluck('variant_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($items as $item) {
                $variant = $variants->get($item->variant_id);

                if (! $variant || ! $variant->is_active || ! $variant->product?->is_active) {
                    throw ValidationException::withMessages(['checkout' => __('checkout.errors.unavailable_item')]);
                }

                if ($item->quantity > $variant->available_stock) {
                    throw ValidationException::withMessages([
                        'checkout' => __('cart.errors.not_enough_stock', ['available' => $variant->available_stock]),
                    ]);
                }

                $lineTotal = $item->quantity * $variant->price_cents;
                $subtotal += $lineTotal;

                $lines[] = [
                    'variant' => $variant,
                    'product' => $variant->product,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $variant->price_cents,
                    'line_total_cents' => $lineTotal,
                ];
            }

            $governorate = Governorate::query()->find($data['governorate_id']);

            if (! $governorate) {
                throw ValidationException::withMessages(['governorate_id' => __('checkout.errors.governorate')]);
            }

            $coupon = $cart->coupon;
            $discount = 0;

            if ($coupon) {
                $couponError = $this->coupons->errorFor($coupon, $subtotal, $user, $data['customer_email'] ?? null);

                if ($couponError !== null) {
                    throw ValidationException::withMessages(['coupon' => $couponError]);
                }

                $discount = $this->coupons->discount($coupon, $subtotal);
            }

            $afterDiscount = max(0, $subtotal - $discount);
            $shipping = $this->shipping->quote($governorate, $afterDiscount);

            if (! $shipping['available']) {
                throw ValidationException::withMessages(['governorate_id' => __('checkout.errors.shipping_unavailable')]);
            }

            $paymentMethod = $data['payment_method'];
            $this->assertPaymentMethodEnabled($paymentMethod);

            if ($coupon && ! $this->coupons->consume($coupon)) {
                throw ValidationException::withMessages(['coupon' => __('coupons.errors.usage_limit')]);
            }

            $total = $afterDiscount + $shipping['shipping_cents'];

            $order = Order::create([
                'number' => $this->numbers->next(),
                'user_id' => $user?->id,
                'status' => Order::STATUS_PENDING,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === Order::PAYMENT_METHOD_COD
                    ? Order::PAYMENT_UNPAID
                    : Order::PAYMENT_PENDING,
                'stock_state' => Order::STOCK_RESERVED,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'shipping_cents' => $shipping['shipping_cents'],
                'total_cents' => $total,
                'currency' => 'EGP',
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'governorate_id' => $governorate->id,
                'governorate_name' => $governorate->name,
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'variant_id' => $line['variant']->id,
                    'product_id' => $line['product']->id,
                    'product_name_ar' => $line['product']->name_ar,
                    'product_name_en' => $line['product']->name_en,
                    'variant_label' => $line['variant']->label,
                    'sku' => $line['variant']->sku,
                    'unit_price_cents' => $line['unit_price_cents'],
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $line['line_total_cents'],
                ]);
            }

            foreach ($lines as $line) {
                $this->inventory->reserve($line['variant'], $line['quantity'], 'order', $order->id);
            }

            $cart->items()->delete();
            $cart->update(['coupon_id' => null]);

            $payment = null;

            if ($paymentMethod === Order::PAYMENT_METHOD_GATEWAY) {
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'reference' => $this->newPaymentReference(),
                    'provider' => 'fake',
                    'amount_cents' => $total,
                    'status' => Payment::STATUS_PENDING,
                ]);
            }

            return ['order' => $order->fresh(['items', 'payment']), 'payment' => $payment];
        });
    }

    private function assertPaymentMethodEnabled(string $method): void
    {
        $codEnabled = Setting::bool('cod_enabled', true);
        $gatewayEnabled = Setting::bool('fake_gateway_enabled', (bool) config('matjar.payment.fake_gateway'));

        if ($method === Order::PAYMENT_METHOD_COD && ! $codEnabled) {
            throw ValidationException::withMessages(['payment_method' => __('checkout.errors.cod_disabled')]);
        }

        if ($method === Order::PAYMENT_METHOD_GATEWAY && ! $gatewayEnabled) {
            throw ValidationException::withMessages(['payment_method' => __('checkout.errors.gateway_disabled')]);
        }
    }

    private function newPaymentReference(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(24));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
