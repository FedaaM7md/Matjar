<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'size', 'color', 'color_hex', 'price_cents', 'compare_at_cents',
        'stock', 'reserved', 'low_stock_threshold', 'is_active', 'position',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'compare_at_cents' => 'integer',
        'stock' => 'integer',
        'reserved' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id')->latest('id');
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock - $this->reserved);
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([$this->size, $this->color]);

        return $parts === [] ? (string) $this->sku : implode(' · ', $parts);
    }

    public function isLowStock(): bool
    {
        return $this->available_stock <= $this->low_stock_threshold;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('(stock - reserved) <= low_stock_threshold');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
