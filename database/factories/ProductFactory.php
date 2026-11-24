<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'Product '.fake()->unique()->numerify('####');

        return [
            'category_id' => Category::factory(),
            'name_ar' => 'منتج '.$name,
            'name_en' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description_ar' => 'وصف تجريبي للمنتج.',
            'description_en' => 'Sample product description.',
            'brand' => 'قطن النيل',
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
