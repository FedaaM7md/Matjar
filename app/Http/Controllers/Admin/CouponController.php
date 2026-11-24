<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $coupons = Coupon::query()
            ->when($request->filled('q'), fn ($query) => $query->where('code', 'like', '%'.$request->query('q').'%'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.coupons.index', ['coupons' => $coupons]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', ['coupon' => new Coupon(['type' => Coupon::TYPE_PERCENT, 'is_active' => true, 'per_user_limit' => 1])]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($request->couponData());

        return redirect()->route('admin.coupons.index')->with('status', __('admin.coupons.created', ['code' => $coupon->code]));
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->couponData());

        return redirect()->route('admin.coupons.index')->with('status', __('admin.coupons.updated', ['code' => $coupon->code]));
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0 || Order::where('coupon_id', $coupon->id)->exists()) {
            return back()->with('error', __('admin.coupons.has_orders'));
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('status', __('admin.coupons.deleted', ['code' => $coupon->code]));
    }
}
