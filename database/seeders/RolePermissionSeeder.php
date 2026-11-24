<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(Permissions::all())->map(function (array $meta, string $slug) {
            return Permission::updateOrCreate(
                ['slug' => $slug],
                ['group' => $meta['group'], 'name_ar' => $meta['ar'], 'name_en' => $meta['en']],
            );
        });

        $admin = Role::updateOrCreate(
            ['slug' => Role::ADMIN],
            ['name_ar' => 'مدير', 'name_en' => 'Admin', 'is_staff' => true],
        );
        $admin->permissions()->sync($permissions->pluck('id'));

        $staff = Role::updateOrCreate(
            ['slug' => Role::STAFF],
            ['name_ar' => 'موظف', 'name_en' => 'Staff', 'is_staff' => true],
        );
        $staff->permissions()->sync(
            $permissions->whereIn('slug', Permissions::staffDefaults())->pluck('id'),
        );

        $customer = Role::updateOrCreate(
            ['slug' => Role::CUSTOMER],
            ['name_ar' => 'عميل', 'name_en' => 'Customer', 'is_staff' => false],
        );
        $customer->permissions()->sync([]);
    }
}
