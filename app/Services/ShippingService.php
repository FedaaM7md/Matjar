<?php

namespace App\Services;

use App\Models\Governorate;
use App\Models\ShippingZone;

class ShippingService
{
    /**
     * Quote shipping for a governorate against the discounted subtotal.
     *
     * @return array{available: bool, zone: ShippingZone|null, shipping_cents: int, free: bool, remaining_cents: int|null, delivery_days_min: int|null, delivery_days_max: int|null}
     */
    public function quote(Governorate $governorate, int $subtotalAfterDiscountCents): array
    {
        $zone = ShippingZone::query()
            ->where('governorate_id', $governorate->id)
            ->where('is_active', true)
            ->first();

        if (! $zone) {
            return [
                'available' => false,
                'zone' => null,
                'shipping_cents' => 0,
                'free' => false,
                'remaining_cents' => null,
                'delivery_days_min' => null,
                'delivery_days_max' => null,
            ];
        }

        $free = $zone->free_threshold_cents !== null
            && $subtotalAfterDiscountCents >= $zone->free_threshold_cents;

        return [
            'available' => true,
            'zone' => $zone,
            'shipping_cents' => $free ? 0 : $zone->rate_cents,
            'free' => $free,
            'remaining_cents' => $free || $zone->free_threshold_cents === null
                ? null
                : max(0, $zone->free_threshold_cents - $subtotalAfterDiscountCents),
            'delivery_days_min' => $zone->delivery_days_min,
            'delivery_days_max' => $zone->delivery_days_max,
        ];
    }
}
