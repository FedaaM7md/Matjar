<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_RETURNED = 'returned';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_CANCELLED = 'cancelled';

    public const STOCK_RESERVED = 'reserved';

    public const STOCK_COMMITTED = 'committed';

    public const STOCK_RELEASED = 'released';

    public const PAYMENT_METHOD_COD = 'cod';

    public const PAYMENT_METHOD_GATEWAY = 'test_gateway';

    protected $fillable = [
        'number', 'user_id', 'status', 'payment_method', 'payment_status', 'stock_state',
        'coupon_id', 'coupon_code', 'subtotal_cents', 'discount_cents', 'shipping_cents',
        'total_cents', 'currency', 'customer_name', 'customer_phone', 'customer_email',
        'governorate_id', 'governorate_name', 'address', 'notes', 'placed_at', 'confirmed_at',
        'shipped_at', 'delivered_at', 'cancelled_at', 'returned_at', 'cancellation_reason',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'shipping_cents' => 'integer',
        'total_cents' => 'integer',
        'placed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => __('orders.statuses.pending'),
            self::STATUS_CONFIRMED => __('orders.statuses.confirmed'),
            self::STATUS_SHIPPED => __('orders.statuses.shipped'),
            self::STATUS_DELIVERED => __('orders.statuses.delivered'),
            self::STATUS_CANCELLED => __('orders.statuses.cancelled'),
            self::STATUS_RETURNED => __('orders.statuses.returned'),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function getStatusLabelAttribute(): string
    {
        return __('orders.statuses.'.$this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return __('orders.payment_statuses.'.$this->payment_status);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SHIPPED], true);
    }

    public function isReturnable(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }
}
