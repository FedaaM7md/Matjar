<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'variant_id', 'product_id', 'product_name_ar', 'product_name_en',
        'variant_label', 'sku', 'unit_price_cents', 'quantity', 'line_total_cents',
    ];

    protected $casts = [
        'unit_price_cents' => 'integer',
        'quantity' => 'integer',
        'line_total_cents' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->{'product_name_'.app()->getLocale()};
    }
}
