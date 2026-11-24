<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly int $variantId, public readonly int $requested, public readonly int $available)
    {
        parent::__construct("Insufficient stock for variant {$variantId}: requested {$requested}, available {$available}.");
    }
}

/**
 * Single entry point for every stock mutation. Each movement is written to the
 * ledger together with the resulting balances so the history is auditable.
 */
class InventoryService
{
    public function record(
        int $variantId,
        string $type,
        int $stockChange = 0,
        int $reservedChange = 0,
        ?string $reason = null,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        return DB::transaction(function () use ($variantId, $type, $stockChange, $reservedChange, $reason, $user, $referenceType, $referenceId) {
            /** @var ProductVariant $variant */
            $variant = ProductVariant::query()->whereKey($variantId)->lockForUpdate()->firstOrFail();

            $newStock = $variant->stock + $stockChange;
            $newReserved = $variant->reserved + $reservedChange;

            if ($newStock < 0) {
                throw new InsufficientStockException($variant->id, abs($stockChange), $variant->stock);
            }

            if ($newReserved < 0) {
                throw new RuntimeException('Reserved stock cannot become negative.');
            }

            if ($newReserved > $newStock) {
                throw new InsufficientStockException(
                    $variant->id,
                    $newReserved - $newStock,
                    max(0, $variant->stock - $variant->reserved),
                );
            }

            $variant->forceFill(['stock' => $newStock, 'reserved' => $newReserved])->save();

            return StockMovement::create([
                'variant_id' => $variant->id,
                'type' => $type,
                'stock_change' => $stockChange,
                'reserved_change' => $reservedChange,
                'stock_after' => $newStock,
                'reserved_after' => $newReserved,
                'reason' => $reason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);
        });
    }

    public function stockIn(ProductVariant $variant, int $quantity, ?User $user = null, ?string $reason = null, ?string $referenceType = null, ?int $referenceId = null): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_IN, $quantity, 0, $reason ?? 'stock_in', $user, $referenceType, $referenceId);
    }

    public function stockOut(ProductVariant $variant, int $quantity, ?User $user = null, ?string $reason = null, ?string $referenceType = null, ?int $referenceId = null): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_OUT, -$quantity, 0, $reason ?? 'stock_out', $user, $referenceType, $referenceId);
    }

    /** Set the physical stock to a new counted value using an adjustment movement. */
    public function adjustTo(ProductVariant $variant, int $newStock, ?User $user = null, ?string $reason = null): StockMovement
    {
        if ($newStock < 0) {
            throw new RuntimeException('Stock cannot be negative.');
        }

        return DB::transaction(function () use ($variant, $newStock, $user, $reason) {
            /** @var ProductVariant $current */
            $current = ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->firstOrFail();
            $delta = $newStock - $current->stock;

            return $this->record($current->id, StockMovement::TYPE_ADJUST, $delta, 0, $reason ?? 'stock_adjust', $user);
        });
    }

    public function reserve(ProductVariant $variant, int $quantity, string $referenceType, int $referenceId): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_RESERVE, 0, $quantity, 'checkout_reservation', null, $referenceType, $referenceId);
    }

    public function release(ProductVariant $variant, int $quantity, string $referenceType, int $referenceId): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_RELEASE, 0, -$quantity, 'reservation_released', null, $referenceType, $referenceId);
    }

    public function commit(ProductVariant $variant, int $quantity, string $referenceType, int $referenceId): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_COMMIT, -$quantity, -$quantity, 'order_fulfilled_reservation', null, $referenceType, $referenceId);
    }

    public function restore(ProductVariant $variant, int $quantity, string $referenceType, int $referenceId, ?User $user = null): StockMovement
    {
        $this->assertPositive($quantity);

        return $this->record($variant->id, StockMovement::TYPE_RESTORE, $quantity, 0, 'stock_restored', $user, $referenceType, $referenceId);
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be a positive integer.');
        }
    }
}
