<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $keys = [
            'store_name_ar', 'store_name_en', 'support_phone', 'support_email',
            'cod_enabled', 'fake_gateway_enabled', 'low_stock_threshold',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key);
        }

        return view('admin.settings.edit', ['settings' => $settings]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name_ar' => ['required', 'string', 'max:120'],
            'store_name_en' => ['required', 'string', 'max:120'],
            'support_phone' => ['required', 'string', 'max:20'],
            'support_email' => ['required', 'email', 'max:190'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'cod_enabled' => ['boolean'],
            'fake_gateway_enabled' => ['boolean'],
        ]);

        $cod = $request->boolean('cod_enabled');
        $gateway = $request->boolean('fake_gateway_enabled');

        if (! $cod && ! $gateway) {
            return back()->withErrors(['cod_enabled' => __('admin.settings.at_least_one_payment')])->withInput();
        }

        Setting::put('store_name_ar', $data['store_name_ar'], 'store');
        Setting::put('store_name_en', $data['store_name_en'], 'store');
        Setting::put('support_phone', $data['support_phone'], 'store');
        Setting::put('support_email', $data['support_email'], 'store');
        Setting::put('low_stock_threshold', (string) $data['low_stock_threshold'], 'inventory');
        Setting::put('cod_enabled', $cod ? '1' : '0', 'payments');
        Setting::put('fake_gateway_enabled', $gateway ? '1' : '0', 'payments');

        return back()->with('status', __('admin.settings.saved'));
    }
}
