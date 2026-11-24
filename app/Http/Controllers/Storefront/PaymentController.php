<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Request $request, Payment $payment, PaymentService $payments): View|RedirectResponse
    {
        $payment->load('order');

        if ($payment->status === Payment::STATUS_PAID) {
            return $this->confirmationRedirect($request, $payment->order)
                ->with('status', __('payments.already_paid'));
        }

        if (in_array($payment->order->status, [Order::STATUS_CANCELLED, Order::STATUS_RETURNED], true)) {
            return $this->confirmationRedirect($request, $payment->order)
                ->with('error', __('payments.errors.order_not_open'));
        }

        $fields = [];

        foreach ($payments->outcomes() as $outcome) {
            $fields[$outcome] = $payments->callbackFields($payment, $outcome);
        }

        return view('payments.show', [
            'payment' => $payment,
            'order' => $payment->order,
            'fields' => $fields,
        ]);
    }

    public function callback(Request $request, Payment $payment, PaymentService $payments, OrderService $orders): RedirectResponse
    {
        $data = $request->validate([
            'outcome' => ['required', 'string'],
            'amount_cents' => ['required', 'integer'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        if ($payment->order->payment_method !== Order::PAYMENT_METHOD_GATEWAY) {
            abort(403, __('payments.errors.not_allowed'));
        }

        if (! $payments->verify(
            $payment,
            (string) $data['outcome'],
            (int) $data['expires'],
            (int) $data['amount_cents'],
            (string) $data['signature'],
        )) {
            abort(403, __('payments.errors.invalid_signature'));
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            return $this->confirmationRedirect($request, $payment->order)
                ->with('error', __('payments.errors.already_final'));
        }

        $outcome = (string) $data['outcome'];
        $order = $payment->order;

        if ($outcome === PaymentService::OUTCOME_SUCCESS) {
            DB::transaction(function () use ($payment, $orders, $data) {
                /** @var Payment $locked */
                $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== Payment::STATUS_PENDING) {
                    return;
                }

                $lockedOrder = $locked->order;

                if (! in_array($lockedOrder->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED], true)) {
                    abort(409, __('payments.errors.order_not_open'));
                }

                $locked->forceFill([
                    'status' => Payment::STATUS_PAID,
                    'paid_at' => now(),
                    'signature' => $data['signature'],
                    'payload' => [
                        'outcome' => PaymentService::OUTCOME_SUCCESS,
                        'amount_cents' => $locked->amount_cents,
                        'at' => now()->toIso8601String(),
                    ],
                ])->save();

                $lockedOrder->forceFill(['payment_status' => Order::PAYMENT_PAID])->save();

                $orders->transition($lockedOrder, Order::STATUS_CONFIRMED, null, 'payment_success');
            });

            return $this->confirmationRedirect($request, $order)
                ->with('status', __('payments.success'));
        }

        DB::transaction(function () use ($payment, $orders, $outcome) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Payment::STATUS_PENDING) {
                return;
            }

            $locked->forceFill([
                'status' => $outcome === PaymentService::OUTCOME_FAIL ? Payment::STATUS_FAILED : Payment::STATUS_CANCELLED,
                'payload' => ['outcome' => $outcome, 'at' => now()->toIso8601String()],
            ])->save();

            $lockedOrder = $locked->order;

            if ($lockedOrder->isCancellable()) {
                $lockedOrder = $orders->transition(
                    $lockedOrder,
                    Order::STATUS_CANCELLED,
                    null,
                    $outcome === PaymentService::OUTCOME_FAIL ? 'payment_failed' : 'payment_cancelled',
                );
            }

            $lockedOrder->forceFill([
                'payment_status' => $outcome === PaymentService::OUTCOME_FAIL
                    ? Order::PAYMENT_FAILED
                    : Order::PAYMENT_CANCELLED,
            ])->save();
        });

        return $this->confirmationRedirect($request, $order)
            ->with('error', $outcome === PaymentService::OUTCOME_FAIL ? __('payments.failed') : __('payments.cancelled'));
    }

    private function confirmationRedirect(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($user && ($user->id === $order->user_id || $user->canAccessAdmin())) {
            return redirect()->route('orders.confirmation', $order->number);
        }

        return redirect(URL::temporarySignedRoute(
            'orders.confirmation',
            now()->addDays(7),
            ['order' => $order->number],
        ));
    }
}
