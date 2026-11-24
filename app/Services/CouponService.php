<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Schema;

class CouponService
{
    /** Returns a translated error message when the coupon cannot be used, or null. */
    public function errorFor(Coupon $coupon, int $subtotalCents, ?User $user = null, ?string $email = null): ?string
    {
        if (! $coupon->is_active) {
            return __('coupons.errors.inactive');
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isAfter($now)) {
            return __('coupons.errors.not_started');
        }

        if ($coupon->ends_at && $coupon->ends_at->isBefore($now)) {
            return __('coupons.errors.expired');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return __('coupons.errors.usage_limit');
        }

        if ($subtotalCents < $coupon->min_subtotal_cents) {
            return __('coupons.errors.min_subtotal', ['amount' => Money::withCurrency($coupon->min_subtotal_cents)]);
        }

        if ($coupon->per_user_limit !== null && $this->perUserUsage($coupon, $user, $email) >= $coupon->per_user_limit) {
            return __('coupons.errors.per_user_limit');
        }

        return null;
    }

    public function perUserUsage(Coupon $coupon, ?User $user = null, ?string $email = null): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        $email = strtolower($email ?? $user?->email ?? '');

        if (! $user && $email === '') {
            return 0;
        }

        return (int) Order::query()
            ->where('coupon_id', $coupon->id)
            ->where(function ($query) use ($user, $email) {
                if ($user) {
                    $query->where('user_id', $user->id);
                }

                if ($email !== '') {
                    $user
                        ? $query->orWhere('customer_email', $email)
                        : $query->where('customer_email', $email);
                }
            })
            ->whereNotIn('status', ['cancelled'])
            ->count();
    }

    /** Increment usage under a row lock; returns false when the global limit was reached. */
    public function consume(Coupon $coupon): bool
    {
        $fresh = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

        if (! $fresh) {
            return false;
        }

        if ($fresh->usage_limit !== null && $fresh->used_count >= $fresh->usage_limit) {
            return false;
        }

        $fresh->forceFill(['used_count' => $fresh->used_count + 1])->save();
        $coupon->setRawAttributes($fresh->getAttributes(), true);

        return true;
    }

    public function discount(Coupon $coupon, int $subtotalCents): int
    {
        return $coupon->discountFor($subtotalCents);
    }
}
