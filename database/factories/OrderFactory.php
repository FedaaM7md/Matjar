<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 200000);

        return [
            'number' => 'ORD-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => null,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_UNPAID,
            'stock_state' => Order::STOCK_RESERVED,
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'shipping_cents' => 5000,
            'total_cents' => $subtotal + 5000,
            'currency' => 'EGP',
            'customer_name' => 'عميل تجريبي',
            'customer_phone' => '01012345678',
            'customer_email' => 'demo@example.test',
            'governorate_name' => 'القاهرة',
            'address' => 'شارع تجريبي 12، القاهرة',
            'placed_at' => now(),
        ];
    }
}
