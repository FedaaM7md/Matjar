<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZone extends Model
{
    protected $fillable = [
        'governorate_id', 'rate_cents', 'free_threshold_cents',
        'delivery_days_min', 'delivery_days_max', 'is_active',
    ];

    protected $casts = [
        'rate_cents' => 'integer',
        'free_threshold_cents' => 'integer',
        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',
        'is_active' => 'boolean',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
