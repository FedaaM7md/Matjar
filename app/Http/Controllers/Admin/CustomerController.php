<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $customers = User::query()
            ->whereHas('role', fn (Builder $query) => $query->where('slug', 'customer'))
            ->withCount('orders')
            ->withSum('orders as paid_total', 'total_cents')
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function show(Request $request, User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $orders = $customer->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'paidTotal' => (int) $customer->orders()->where('payment_status', 'paid')->sum('total_cents'),
            'ordersTotal' => $customer->orders()->count(),
        ]);
    }
}
