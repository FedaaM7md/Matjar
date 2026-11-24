<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();
        $price = $variant->price_cents;
        $qty = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name_ar' => $variant->product->name_ar,
            'product_name_en' => $variant->product->name_en,
            'variant_label' => $variant->label,
            'sku' => $variant->sku,
            'unit_price_cents' => $price,
            'quantity' => $qty,
            'line_total_cents' => $price * $qty,
        ];
    }
}
