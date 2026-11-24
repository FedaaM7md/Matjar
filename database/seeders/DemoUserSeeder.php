<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts only. These credentials are published in the README and are
 * not suitable for any real deployment. Every account is flagged `is_demo` so
 * the application can apply demo-only restrictions.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();
        $staffRole = Role::where('slug', Role::STAFF)->firstOrFail();
        $customerRole = Role::where('slug', Role::CUSTOMER)->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@matjar.test'],
            [
                'name' => 'ياسمين فؤاد',
                'phone' => '01000000001',
                'password' => Hash::make('Demo12345'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'is_demo' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'staff@matjar.test'],
            [
                'name' => 'كريم سامي',
                'phone' => '01000000002',
                'password' => Hash::make('Demo12345'),
                'role_id' => $staffRole->id,
                'is_active' => true,
                'is_demo' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'customer@matjar.test'],
            [
                'name' => 'منى عبد الله',
                'phone' => '01000000003',
                'password' => Hash::make('Demo12345'),
                'role_id' => $customerRole->id,
                'is_active' => true,
                'is_demo' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
