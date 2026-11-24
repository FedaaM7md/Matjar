<?php

namespace Database\Seeders;

use App\Models\Governorate;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderNumberService;
use App\Services\ShippingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo orders for the admin dashboards and reports. Orders are spread over the
 * last ~3 months across all governorates, with realistic Egyptian customer
 * names and varied statuses so charts and lists look alive. Every row is
 * explicitly demo data; stock movements are recorded so inventory balances.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || Order::query()->exists()) {
            return;
        }

        mt_srand(20261124);

        $variants = ProductVariant::query()->with('product')->where('is_active', true)->get();
        if ($variants->isEmpty()) {
            return;
        }

        $governorates = Governorate::query()->with('zone')->get();
        if ($governorates->isEmpty()) {
            return;
        }

        $customer = User::where('is_demo', true)->where('email', 'customer@matjar.test')->first();
        $inventory = app(InventoryService::class);
        $numbers = app(OrderNumberService::class);
        $shipping = app(ShippingService::class);

        $names = [
            'أحمد سامي', 'منى عبد الله', 'كريم مصطفى', 'سارة إبراهيم', 'عمر خالد',
            'ندى حسن', 'يوسف فؤاد', 'هبة رمضان', 'طارق سعيد', 'ليلى محمود',
            'مينا عادل', 'دعاء سمير', 'حسام الدين', 'نهى كمال', 'رامي طارق',
            'أسماء يوسف', 'محمود عز', 'فاطمة ناصر', 'زياد شريف', 'رنا جمال',
        ];

        // A weighted status mix so the reports look believable.
        $statuses = array_merge(
            array_fill(0, 26, Order::STATUS_DELIVERED),
            array_fill(0, 12, Order::STATUS_CONFIRMED),
            array_fill(0, 10, Order::STATUS_SHIPPED),
            array_fill(0, 8, Order::STATUS_PENDING),
            array_fill(0, 7, Order::STATUS_CANCELLED),
            array_fill(0, 5, Order::STATUS_RETURNED),
        );
        shuffle($statuses);

        $placedOrders = 0;

        DB::transaction(function () use ($variants, $governorates, $customer, $inventory, $numbers, $shipping, $names, $statuses, &$placedOrders) {
            foreach ($statuses as $status) {
                $governorate = $governorates->random();
                $placedAt = $this->placedAtFor($status);

                $lines = [];
                $subtotal = 0;
                $picked = $variants->random(mt_rand(1, 2));

                foreach ($picked as $variant) {
                    $quantity = mt_rand(1, 2);

                    if ($variant->available_stock < $quantity) {
                        continue;
                    }

                    $lineTotal = $variant->price_cents * $quantity;
                    $subtotal += $lineTotal;
                    $lines[] = ['variant' => $variant, 'quantity' => $quantity, 'unit' => $variant->price_cents, 'line' => $lineTotal];
                }

                if ($lines === []) {
                    continue;
                }

                $quote = $shipping->quote($governorate, $subtotal);
                $name = $names[array_rand($names)];
                $phone = '010'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                $email = 'customer'.mt_rand(1000, 9999).'@example.com';
                $paymentMethod = mt_rand(0, 3) === 0 ? 'test_gateway' : 'cod';

                $paid = in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED], true)
                    && ($paymentMethod === 'cod' ? mt_rand(0, 1) === 1 : true);

                $paymentStatus = $paymentMethod === 'cod'
                    ? ($paid ? Order::PAYMENT_PAID : Order::PAYMENT_UNPAID)
                    : ($paid ? Order::PAYMENT_PAID : Order::PAYMENT_FAILED);

                $order = Order::create([
                    'number' => $numbers->next(),
                    'user_id' => mt_rand(0, 4) === 0 ? $customer?->id : null,
                    'status' => Order::STATUS_PENDING,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'stock_state' => Order::STOCK_RESERVED,
                    'subtotal_cents' => $subtotal,
                    'discount_cents' => 0,
                    'shipping_cents' => $quote['shipping_cents'],
                    'total_cents' => $subtotal + $quote['shipping_cents'],
                    'currency' => 'EGP',
                    'customer_name' => $name,
                    'customer_phone' => $phone,
                    'customer_email' => $email,
                    'governorate_id' => $governorate->id,
                    'governorate_name' => $governorate->name,
                    'address' => 'عنوان تجريبي — '.$governorate->name,
                    'notes' => 'DEMO ORDER — بيانات تجريبية للعرض فقط.',
                    'placed_at' => $placedAt,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                foreach ($lines as $line) {
                    $order->items()->create([
                        'variant_id' => $line['variant']->id,
                        'product_id' => $line['variant']->product_id,
                        'product_name_ar' => $line['variant']->product->name_ar,
                        'product_name_en' => $line['variant']->product->name_en,
                        'variant_label' => $line['variant']->label,
                        'sku' => $line['variant']->sku,
                        'unit_price_cents' => $line['unit'],
                        'quantity' => $line['quantity'],
                        'line_total_cents' => $line['line'],
                    ]);
                }

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => Order::STATUS_PENDING,
                    'note' => 'demo_seed',
                    'created_at' => $placedAt,
                ]);

                foreach ($lines as $line) {
                    $inventory->reserve($line['variant'], $line['quantity'], 'order', $order->id);
                }

                $series = [Order::STATUS_PENDING];

                if (in_array($status, [Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_RETURNED], true)) {
                    foreach ($lines as $line) {
                        $inventory->commit($line['variant'], $line['quantity'], 'order', $order->id);
                    }
                    $order->forceFill(['stock_state' => Order::STOCK_COMMITTED, 'confirmed_at' => $placedAt])->save();
                    $series[] = Order::STATUS_CONFIRMED;
                }

                if (in_array($status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_RETURNED], true)) {
                    $order->forceFill(['shipped_at' => $placedAt->copy()->addDay()])->save();
                    $series[] = Order::STATUS_SHIPPED;
                }

                if (in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_RETURNED], true)) {
                    $order->forceFill(['delivered_at' => $placedAt->copy()->addDays(2)])->save();
                    $series[] = Order::STATUS_DELIVERED;
                }

                if ($status === Order::STATUS_CANCELLED) {
                    foreach ($lines as $line) {
                        $inventory->release($line['variant'], $line['quantity'], 'order', $order->id);
                    }
                    $order->forceFill([
                        'stock_state' => Order::STOCK_RELEASED,
                        'cancelled_at' => $placedAt->copy()->addHours(6),
                        'cancellation_reason' => 'demo_cancelled',
                    ])->save();
                    $series[] = Order::STATUS_CANCELLED;
                }

                if ($status === Order::STATUS_RETURNED) {
                    foreach ($lines as $line) {
                        $inventory->restore($line['variant'], $line['quantity'], 'order', $order->id);
                    }
                    $order->forceFill([
                        'stock_state' => Order::STOCK_RELEASED,
                        'returned_at' => $placedAt->copy()->addDays(4),
                    ])->save();
                    $series[] = Order::STATUS_RETURNED;
                }

                $previous = null;
                $offset = 0;
                foreach (array_slice($series, 1) as $step) {
                    $offset++;
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'from_status' => $previous ?? Order::STATUS_PENDING,
                        'to_status' => $step,
                        'note' => 'demo_seed',
                        'created_at' => $placedAt->copy()->addHours($offset * 10),
                    ]);
                    $previous = $step;
                }

                $order->forceFill(['status' => $status])->save();

                if ($paymentMethod === 'test_gateway' && $paid) {
                    Payment::create([
                        'order_id' => $order->id,
                        'reference' => 'PAY-DEMO-'.Str::upper(Str::random(16)),
                        'provider' => 'fake',
                        'amount_cents' => $order->total_cents,
                        'status' => Payment::STATUS_PAID,
                        'payload' => ['outcome' => 'success', 'demo' => true],
                        'paid_at' => $placedAt,
                    ]);
                }

                $placedOrders++;
            }
        });
    }

    private function placedAtFor(string $status): Carbon
    {
        [$min, $max] = match ($status) {
            Order::STATUS_DELIVERED => [15, 88],
            Order::STATUS_SHIPPED => [5, 30],
            Order::STATUS_CONFIRMED => [3, 20],
            Order::STATUS_PENDING => [0, 6],
            Order::STATUS_CANCELLED => [2, 45],
            Order::STATUS_RETURNED => [12, 60],
            default => [0, 88],
        };

        return now()
            ->subDays(mt_rand($min, $max))
            ->setTime(mt_rand(9, 21), mt_rand(0, 59));
    }
}
