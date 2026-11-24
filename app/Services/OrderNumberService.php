<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderNumberService
{
    /** Generate ORD-YYYY-NNNN using a locked per-year counter. */
    public function next(): string
    {
        return DB::transaction(function () {
            $year = (int) now()->year;

            $counter = DB::table('order_counters')->where('year', $year)->lockForUpdate()->first();

            if (! $counter) {
                DB::table('order_counters')->insert(['year' => $year, 'last_number' => 0]);
                $counter = DB::table('order_counters')->where('year', $year)->lockForUpdate()->first();
            }

            $next = ((int) $counter->last_number) + 1;

            DB::table('order_counters')->where('year', $year)->update(['last_number' => $next]);

            return sprintf('ORD-%d-%04d', $year, $next);
        });
    }
}
