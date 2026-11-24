<?php

namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Governorate> */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name_ar' => 'محافظة '.fake()->unique()->word(),
            'name_en' => 'Governorate '.fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
