<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class DemoMarketingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        Coupon::updateOrCreate(['code' => 'WELCOME10'], [
            'type' => Coupon::TYPE_PERCENT,
            'value' => 1000,
            'min_subtotal_cents' => 50000,
            'max_discount_cents' => 10000,
            'usage_limit' => 100,
            'used_count' => 0,
            'per_user_limit' => 2,
            'starts_at' => now()->subMonth()->startOfDay(),
            'ends_at' => now()->addMonths(3)->endOfDay(),
            'is_active' => true,
        ]);

        Coupon::updateOrCreate(['code' => 'SAVE50'], [
            'type' => Coupon::TYPE_FIXED,
            'value' => 5000,
            'min_subtotal_cents' => 100000,
            'max_discount_cents' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'per_user_limit' => 1,
            'starts_at' => null,
            'ends_at' => now()->addMonths(2)->endOfDay(),
            'is_active' => true,
        ]);
    }
}
