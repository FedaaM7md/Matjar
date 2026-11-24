<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUST = 'adjust';

    public const TYPE_RESERVE = 'reserve';

    public const TYPE_RELEASE = 'release';

    public const TYPE_COMMIT = 'commit';

    public const TYPE_RESTORE = 'restore';

    public $timestamps = false;

    protected $fillable = [
        'variant_id', 'type', 'stock_change', 'reserved_change', 'stock_after', 'reserved_after',
        'reason', 'reference_type', 'reference_id', 'user_id', 'created_at',
    ];

    protected $casts = [
        'stock_change' => 'integer',
        'reserved_change' => 'integer',
        'stock_after' => 'integer',
        'reserved_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __('inventory.types.'.$this->type);
    }
}
