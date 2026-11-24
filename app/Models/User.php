<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role_id', 'is_active', 'is_demo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasPermission(string $slug): bool
    {
        $role = $this->role;

        if (! $role) {
            return false;
        }

        $role->loadMissing('permissions');

        return $role->hasPermission($slug);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === Role::ADMIN;
    }

    public function isStaff(): bool
    {
        return (bool) $this->role?->is_staff;
    }

    public function isCustomer(): bool
    {
        return $this->role?->slug === Role::CUSTOMER;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isActive() && $this->isStaff();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }
}
