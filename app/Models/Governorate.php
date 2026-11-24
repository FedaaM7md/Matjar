<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Governorate extends Model
{
    protected $fillable = ['code', 'name_ar', 'name_en', 'position'];

    public function zone(): HasOne
    {
        return $this->hasOne(ShippingZone::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->{'name_'.app()->getLocale()};
    }
}
