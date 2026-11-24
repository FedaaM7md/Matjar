<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

/**
 * The 27 Egyptian governorates are real administrative units. The shipping
 * rates, free-shipping thresholds and delivery windows below are demo values.
 */
class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['CAI', 'القاهرة', 'Cairo', 5000, 150000, 1, 2],
            ['GIZ', 'الجيزة', 'Giza', 5000, 150000, 1, 3],
            ['QAL', 'القليوبية', 'Qalyubia', 5500, 150000, 1, 3],
            ['ALX', 'الإسكندرية', 'Alexandria', 6000, 150000, 2, 3],
            ['PTS', 'بورسعيد', 'Port Said', 6500, 150000, 2, 4],
            ['SUZ', 'السويس', 'Suez', 6500, 150000, 2, 4],
            ['ISM', 'الإسماعيلية', 'Ismailia', 6500, 150000, 2, 4],
            ['DAM', 'دمياط', 'Damietta', 6000, 150000, 2, 4],
            ['DK', 'الدقهلية', 'Dakahlia', 6500, 150000, 2, 4],
            ['SHR', 'الشرقية', 'Sharqia', 6500, 150000, 2, 4],
            ['KFS', 'كفر الشيخ', 'Kafr El Sheikh', 7000, 150000, 3, 5],
            ['GHR', 'الغربية', 'Gharbia', 6500, 150000, 2, 4],
            ['MNF', 'المنوفية', 'Monufia', 6500, 150000, 2, 4],
            ['BEH', 'البحيرة', 'Beheira', 6500, 150000, 2, 4],
            ['BNS', 'بني سويف', 'Beni Suef', 7500, 200000, 3, 5],
            ['FYM', 'الفيوم', 'Fayoum', 7500, 200000, 3, 5],
            ['MNY', 'المنيا', 'Minya', 8000, 200000, 3, 6],
            ['AST', 'أسيوط', 'Asyut', 8000, 200000, 3, 6],
            ['SHG', 'سوهاج', 'Sohag', 8500, 200000, 4, 6],
            ['QNA', 'قنا', 'Qena', 9000, 200000, 4, 7],
            ['ASW', 'أسوان', 'Aswan', 9500, 250000, 4, 7],
            ['LXR', 'الأقصر', 'Luxor', 9000, 250000, 4, 7],
            ['RS', 'البحر الأحمر', 'Red Sea', 9000, 250000, 4, 7],
            ['WAD', 'الوادي الجديد', 'New Valley', 9500, 250000, 5, 8],
            ['MTR', 'مطروح', 'Matrouh', 8500, 250000, 3, 6],
            ['NSI', 'شمال سيناء', 'North Sinai', 10000, 250000, 5, 8],
            ['SSI', 'جنوب سيناء', 'South Sinai', 10000, 250000, 5, 8],
        ];

        foreach ($rows as $position => [$code, $nameAr, $nameEn, $rate, $threshold, $minDays, $maxDays]) {
            $governorate = Governorate::updateOrCreate(
                ['code' => $code],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'position' => $position + 1],
            );

            $governorate->zone()->updateOrCreate([], [
                'rate_cents' => $rate,
                'free_threshold_cents' => $threshold,
                'delivery_days_min' => $minDays,
                'delivery_days_max' => $maxDays,
                'is_active' => true,
            ]);
        }
    }
}
