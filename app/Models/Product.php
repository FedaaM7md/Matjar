<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'slug', 'name_ar', 'name_en', 'description_ar', 'description_en',
        'brand', 'is_active', 'is_featured',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function getNameAttribute(): string
    {
        $value = $this->{'name_'.app()->getLocale()};

        return $value ?: (string) $this->name_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        $value = $this->{'description_'.app()->getLocale()};

        return $value ?: $this->description_ar;
    }

    public function getMinPriceCentsAttribute(mixed $value = null): int
    {
        if ($value !== null) {
            return (int) $value;
        }

        return (int) ($this->variants->min('price_cents') ?? 0);
    }

    public function getInStockAttribute(mixed $value = null): bool
    {
        if ($value !== null) {
            return (bool) $value;
        }

        return $this->variants->contains(fn (ProductVariant $variant) => $variant->stock - $variant->reserved > 0);
    }

    public function getPrimaryImagePathAttribute(): ?string
    {
        return $this->images->first()?->path;
    }

    public function getMaxPriceCentsAttribute(): int
    {
        return (int) ($this->variants->max('price_cents') ?? 0);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('name_ar', 'like', $like)
                ->orWhere('name_en', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhereHas('variants', fn (Builder $variant) => $variant->where('sku', 'like', $like));
        });
    }
}
