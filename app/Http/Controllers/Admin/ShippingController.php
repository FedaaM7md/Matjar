<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function index(): View
    {
        $governorates = Governorate::query()
            ->with('zone')
            ->orderBy('position')
            ->get();

        return view('admin.shipping.index', [
            'governorates' => $governorates,
            'activeCount' => $governorates->filter(fn (Governorate $governorate) => $governorate->zone?->is_active)->count(),
        ]);
    }

    public function update(Request $request, Governorate $governorate): RedirectResponse
    {
        $data = $request->validate([
            'rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'free_threshold' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'delivery_days_min' => ['required', 'integer', 'min:1', 'max:60'],
            'delivery_days_max' => ['required', 'integer', 'min:1', 'max:60', 'gte:delivery_days_min'],
            'is_active' => ['boolean'],
        ]);

        $governorate->zone()->updateOrCreate([], [
            'rate_cents' => Money::toMinor($data['rate']),
            'free_threshold_cents' => $request->filled('free_threshold') ? Money::toMinor($data['free_threshold']) : null,
            'delivery_days_min' => (int) $data['delivery_days_min'],
            'delivery_days_max' => (int) $data['delivery_days_max'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('admin.shipping.updated', ['name' => $governorate->name]));
    }
}
