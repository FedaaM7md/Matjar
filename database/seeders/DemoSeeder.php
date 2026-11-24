<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            DemoUserSeeder::class,
            DemoCatalogSeeder::class,
            DemoMarketingSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
