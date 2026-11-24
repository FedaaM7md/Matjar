<?php

namespace Tests;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetMemo();
    }

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /** @param array<string, mixed> $attributes */
    public function userWithRole(string $roleSlug, array $attributes = []): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'is_active' => true,
        ], $attributes));
    }
}
