<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code', 'type', 'value', 'min_subtotal_cents', 'max_discount_cents',
        'usage_limit', 'used_count', 'per_user_limit', 'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'integer',
        'min_subtotal_cents' => 'integer',
        'max_discount_cents' => 'integer',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function normalizeCode(?string $code): string
    {
        return Str::upper(trim((string) $code));
    }

    public static function findByCode(?string $code): ?self
    {
        $normalized = self::normalizeCode($code);

        if ($normalized === '') {
            return null;
        }

        return static::query()->where('code', $normalized)->first();
    }

    public function isPercent(): bool
    {
        return $this->type === self::TYPE_PERCENT;
    }

    public function getTypeLabelAttribute(): string
    {
        return __('coupons.types.'.$this->type);
    }

    /** Human-readable value, e.g. "10%" or "150.00 EGP". */
    public function getValueLabelAttribute(): string
    {
        if ($this->isPercent()) {
            return rtrim(rtrim(number_format($this->value / 100, 2, '.', ''), '0'), '.').'%';
        }

        return Money::withCurrency($this->value);
    }

    public function discountFor(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discount = $this->isPercent()
            ? (int) round($subtotalCents * ($this->value / 10000))
            : (int) $this->value;

        if ($this->max_discount_cents !== null) {
            $discount = min($discount, $this->max_discount_cents);
        }

        return max(0, min($discount, $subtotalCents));
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return __('coupons.statuses.inactive');
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return __('coupons.statuses.scheduled');
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return __('coupons.statuses.expired');
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return __('coupons.statuses.exhausted');
        }

        return __('coupons.statuses.active');
    }
}
