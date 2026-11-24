<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderTransitionException extends RuntimeException {}

/**
 * Owns the order state machine and the stock consequences of each transition.
 */
class OrderService
{
    /** @var array<string, list<string>> */
    public const TRANSITIONS = [
        Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
        Order::STATUS_CONFIRMED => [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED],
        Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
        Order::STATUS_DELIVERED => [Order::STATUS_RETURNED],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_RETURNED => [],
    ];

    public function __construct(private readonly InventoryService $inventory) {}

    public function canTransition(Order $order, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$order->status] ?? [], true);
    }

    public function transition(Order $order, string $to, ?User $actor = null, ?string $note = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $note, $reason) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $to) {
                return $locked;
            }

            if (! $this->canTransition($locked, $to)) {
                throw new OrderTransitionException(__('orders.errors.invalid_transition', [
                    'from' => __('orders.statuses.'.$locked->status),
                    'to' => __('orders.statuses.'.$to),
                ]));
            }

            $from = $locked->status;
            $data = ['status' => $to];

            match ($to) {
                Order::STATUS_CONFIRMED => $this->commitStock($locked),
                Order::STATUS_CANCELLED => $this->releaseOrRestoreStock($locked),
                Order::STATUS_RETURNED => $this->restoreStock($locked),
                default => null,
            };

            $timestampColumn = match ($to) {
                Order::STATUS_CONFIRMED => 'confirmed_at',
                Order::STATUS_SHIPPED => 'shipped_at',
                Order::STATUS_DELIVERED => 'delivered_at',
                Order::STATUS_CANCELLED => 'cancelled_at',
                Order::STATUS_RETURNED => 'returned_at',
                default => null,
            };

            if ($timestampColumn) {
                $data[$timestampColumn] = now();
            }

            if ($to === Order::STATUS_CANCELLED) {
                $data['cancellation_reason'] = $reason ?? $note ?? 'cancelled';
                if (! in_array($locked->payment_status, [Order::PAYMENT_PAID], true)) {
                    $data['payment_status'] = Order::PAYMENT_CANCELLED;
                }
            }

            $locked->forceFill($data)->save();

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'user_id' => $actor?->id,
                'created_at' => now(),
            ]);

            $order->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    public function commitStock(Order $order): void
    {
        if ($order->stock_state !== Order::STOCK_RESERVED) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->variant_id) {
                continue;
            }

            $this->inventory->commit(
                $this->variantFor($item),
                $item->quantity,
                'order',
                $order->id,
            );
        }

        $order->forceFill(['stock_state' => Order::STOCK_COMMITTED])->save();
    }

    public function releaseStock(Order $order): void
    {
        if ($order->stock_state !== Order::STOCK_RESERVED) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->variant_id) {
                continue;
            }

            $this->inventory->release(
                $this->variantFor($item),
                $item->quantity,
                'order',
                $order->id,
            );
        }

        $order->forceFill(['stock_state' => Order::STOCK_RELEASED])->save();
    }

    public function restoreStock(Order $order): void
    {
        if ($order->stock_state !== Order::STOCK_COMMITTED) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->variant_id) {
                continue;
            }

            $this->inventory->restore(
                $this->variantFor($item),
                $item->quantity,
                'order',
                $order->id,
            );
        }

        $order->forceFill(['stock_state' => Order::STOCK_RELEASED])->save();
    }

    private function variantFor(OrderItem $item): ProductVariant
    {
        return ProductVariant::query()->findOrFail($item->variant_id);
    }

    private function releaseOrRestoreStock(Order $order): void
    {
        if ($order->stock_state === Order::STOCK_RESERVED) {
            $this->releaseStock($order);

            return;
        }

        if ($order->stock_state === Order::STOCK_COMMITTED) {
            $this->restoreStock($order);
        }
    }
}
