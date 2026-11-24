<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderTransitionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $orders = Order::query()
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function (Builder $inner) use ($like) {
                    $inner->where('number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_phone', 'like', $like)
                        ->orWhere('customer_email', 'like', $like);
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->when($request->filled('payment'), fn (Builder $query) => $query->where('payment_status', $request->query('payment')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'statuses' => Order::statuses(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'histories.user', 'payment', 'user', 'governorate.zone']);

        return view('admin.orders.show', [
            'order' => $order,
            'allowedTransitions' => OrderService::TRANSITIONS[$order->status] ?? [],
            'invoiceUrl' => route('orders.invoice', $order->number),
        ]);
    }

    public function transition(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::statuses()))],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $orders->transition($order, $data['status'], $request->user(), $data['reason'] ?? null, $data['reason'] ?? null);
        } catch (OrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('status', __('admin.orders.transitioned', [
                'number' => $order->number,
                'status' => __('orders.statuses.'.$data['status']),
            ]));
    }
}
