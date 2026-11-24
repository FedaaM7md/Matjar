<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'store_name_ar', 'value' => 'قُطن', 'group' => 'store'],
            ['key' => 'store_name_en', 'value' => 'Qoton', 'group' => 'store'],
            ['key' => 'support_phone', 'value' => '01000000000', 'group' => 'store'],
            ['key' => 'support_email', 'value' => 'hello@qoton.example.com', 'group' => 'store'],
            ['key' => 'cod_enabled', 'value' => '1', 'group' => 'payments'],
            ['key' => 'fake_gateway_enabled', 'value' => '1', 'group' => 'payments'],
            ['key' => 'low_stock_threshold', 'value' => '5', 'group' => 'inventory'],
            ['key' => 'free_shipping_note', 'value' => '1', 'group' => 'shipping'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
