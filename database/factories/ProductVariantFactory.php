<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        static $counter = 0;

        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.str_pad((string) ++$counter, 6, '0', STR_PAD_LEFT).'-'.fake()->unique()->numerify('###'),
            'size' => fake()->randomElement([null, 'S', 'M', 'L']),
            'color' => fake()->randomElement([null, 'Navy', 'White', 'Olive']),
            'color_hex' => null,
            'price_cents' => fake()->numberBetween(9900, 99000),
            'compare_at_cents' => null,
            'stock' => 10,
            'reserved' => 0,
            'low_stock_threshold' => 3,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
