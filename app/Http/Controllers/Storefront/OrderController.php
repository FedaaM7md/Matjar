<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->authorizeAccess($request, $order);

        return $this->view($request, $order, false);
    }

    public function confirmation(Request $request, Order $order): View
    {
        $this->authorizeAccess($request, $order);

        return $this->view($request, $order, true);
    }

    public function invoice(Request $request, Order $order): View
    {
        $this->authorizeAccess($request, $order);

        $order->load(['items', 'governorate', 'payment']);

        return view('orders.invoice', ['order' => $order]);
    }

    private function view(Request $request, Order $order, bool $confirmation): View
    {
        $order->load(['items', 'governorate', 'payment', 'histories.user']);

        $user = $request->user();
        $isOwner = $user && $user->id === $order->user_id;
        $canManage = $user && $user->canAccessAdmin();

        $invoiceUrl = $isOwner || $canManage
            ? route('orders.invoice', $order->number)
            : URL::temporarySignedRoute('orders.invoice', now()->addDays(7), ['order' => $order->number]);

        return view('orders.show', [
            'order' => $order,
            'confirmation' => $confirmation,
            'invoiceUrl' => $invoiceUrl,
            'canPayOnline' => $order->payment_method === Order::PAYMENT_METHOD_GATEWAY
                && $order->payment_status === Order::PAYMENT_PENDING
                && $order->payment,
        ]);
    }

    private function authorizeAccess(Request $request, Order $order): void
    {
        $user = $request->user();

        $allowed = $request->hasValidSignature()
            || ($user && ($user->id === $order->user_id || $user->canAccessAdmin()));

        abort_unless($allowed, 403);
    }
}
