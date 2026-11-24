<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = 'Category '.fake()->unique()->numerify('###');

        return [
            'name_ar' => 'فئة '.$name,
            'name_en' => $name,
            'slug' => Str::slug($name),
            'description_ar' => null,
            'description_en' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
