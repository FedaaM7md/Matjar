<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'reference' => 'PAY-'.Str::upper(Str::random(24)),
            'provider' => 'fake',
            'amount_cents' => 50000,
            'status' => Payment::STATUS_PENDING,
        ];
    }
}
