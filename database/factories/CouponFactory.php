<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => 'SAVE'.Str::upper(Str::random(6)),
            'type' => Coupon::TYPE_PERCENT,
            'value' => 1000,
            'min_subtotal_cents' => 0,
            'max_discount_cents' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'per_user_limit' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $cents): static
    {
        return $this->state(fn () => ['type' => Coupon::TYPE_FIXED, 'value' => $cents]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['ends_at' => now()->subDay()]);
    }
}
