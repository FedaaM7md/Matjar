<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo catalogue for the fictional store "قُطن / Qoton — ملابس يومية مصرية".
 * Names, prices and colours are written to look like a real Egyptian clothing
 * shop. Imagery is original per-product SVG illustration (no photography).
 */
class DemoCatalogSeeder extends Seeder
{
    private const SIZES = ['S', 'M', 'L', 'XL', 'XXL'];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $categories = [
            ['slug' => 'tshirts', 'name_ar' => 'تيشيرتات', 'name_en' => 'T-Shirts', 'description_ar' => 'تيشيرتات قطنية يومية بقصات مريحة وألوان هادئة.', 'description_en' => 'Everyday cotton tees in relaxed cuts and calm colours.', 'position' => 1],
            ['slug' => 'shirts', 'name_ar' => 'قمصان', 'name_en' => 'Shirts', 'description_ar' => 'قمصان قطن وكتان للعمل والخروج، بمقاسات كاملة.', 'description_en' => 'Cotton and linen shirts for work and going out, full size range.', 'position' => 2],
            ['slug' => 'pants', 'name_ar' => 'بناطيل', 'name_en' => 'Pants', 'description_ar' => 'بناطيل شينو وجينز وقصات واسعة للاستخدام اليومي.', 'description_en' => 'Chinos, jeans and wide cuts for everyday wear.', 'position' => 3],
            ['slug' => 'hoodies', 'name_ar' => 'هوديز', 'name_en' => 'Hoodies', 'description_ar' => 'هوديز وسويت شيرت قطن ثقيل لطقس مصر البارد.', 'description_en' => 'Heavy cotton hoodies and sweatshirts for cooler days.', 'position' => 4],
            ['slug' => 'bags', 'name_ar' => 'شنط قماش', 'name_en' => 'Canvas Bags', 'description_ar' => 'شنط قماش متينة للعمل والسوق والجامعة.', 'description_en' => 'Sturdy canvas bags for work, errands and university.', 'position' => 5],
            ['slug' => 'accessories', 'name_ar' => 'إكسسوارات', 'name_en' => 'Accessories', 'description_ar' => 'كابات وأحزمة وجوارب تكمل اللوك اليومي.', 'description_en' => 'Caps, belts and socks that finish the everyday look.', 'position' => 6],
        ];

        $categoryModels = [];
        foreach ($categories as $category) {
            $categoryModels[$category['slug']] = Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name_ar' => $category['name_ar'],
                    'name_en' => $category['name_en'],
                    'description_ar' => $category['description_ar'],
                    'description_en' => $category['description_en'],
                    'position' => $category['position'],
                    'is_active' => true,
                ],
            );
        }

        $w = ['أبيض', '#F4F1EC'];
        $b = ['أسود', '#111111'];
        $s = ['رملي', '#D8C3A5'];
        $o = ['زيتي', '#4B5D3A'];
        $n = ['كحلي', '#1F2A44'];
        $g = ['رمادي', '#6B6B6B'];
        $bl = ['أزرق', '#2F4A6D'];
        $lb = ['أزرق فاتح', '#AFC3D6'];
        $be = ['بيج', '#C9B79C'];
        $t = ['أحمر طوبي', '#E63B2E'];
        $d = ['دنيم', '#3B5B86'];
        $gr = ['أخضر فاتح', '#9CAF88'];
        $r = ['أحمر داكن', '#8A2B22'];

        // category, slug, name_ar, name_en, price, colours, featured, description_ar, description_en
        $products = [
            ['tshirts', 'essential-cotton-tee', 'تيشيرت قطني أساسي', 'Essential Cotton Tee', 24900, [$w, $b], true, 'تيشيرت قطني 100% بقصة مستقيمة مريحة، مناسب للاستخدام اليومي.', 'A 100% cotton tee with a relaxed straight cut for everyday wear.'],
            ['tshirts', 'oversized-tee', 'تيشيرت أوفر سايز', 'Oversized Tee', 29900, [$s, $o], false, 'تيشيرت بقصة واسعة وكتف مسدول، قماش قطني ثقيل قليلًا.', 'A boxy oversized tee in slightly heavier cotton.'],
            ['tshirts', 'pocket-tee', 'تيشيرت بجيب', 'Pocket Tee', 27900, [$n, $g], false, 'تيشيرت بجيب أمامي صغير وقماش قطني ناعم.', 'A soft cotton tee with a small chest pocket.'],
            ['tshirts', 'striped-tee', 'تيشيرت مخطط', 'Striped Tee', 28900, [$bl, $w], false, 'تيشيرت مخطط طوليًا بخطوط هادئة وقصة مريحة.', 'A relaxed tee with quiet vertical stripes.'],
            ['tshirts', 'long-sleeve-tee', 'تيشيرت كم طويل', 'Long-Sleeve Tee', 31900, [$b, $be], false, 'تيشيرت كم طويل من القطن الممشط، مناسب للطقس المتغير.', 'A combed-cotton long-sleeve tee for changing weather.'],
            ['tshirts', 'cairo-graphic-tee', 'تيشيرت كايرو', 'Cairo Graphic Tee', 30900, [$w, $t], true, 'تيشيرت بطبعة «كايرو» بسيطة على الصدر.', 'A tee with a simple “Cairo” print on the chest.'],

            ['shirts', 'classic-cotton-shirt', 'قميص قطني كلاسيكي', 'Classic Cotton Shirt', 44900, [$w, $lb], true, 'قميص قطني كلاسيكي بأزرار وقصة مريحة للعمل.', 'A classic button-down cotton shirt with a comfortable cut.'],
            ['shirts', 'oxford-shirt', 'قميص أوكسفورد', 'Oxford Shirt', 49900, [$bl, $w], false, 'قميص أوكسفورد بقماش سميك قليلًا وياقة ثابتة.', 'An Oxford shirt in slightly heavier cloth with a structured collar.'],
            ['shirts', 'linen-shirt', 'قميص كتان', 'Linen Shirt', 52900, [$s, $gr], false, 'قميص كتان خفيف يسمح بمرور الهواء، مثالي للصيف.', 'A light linen shirt that breathes, made for summer.'],
            ['shirts', 'flannel-shirt', 'قميص فلانيل', 'Flannel Shirt', 54900, [$r, $bl], false, 'قميص فلانيل دافئ بمربعات صغيرة وجيوب أمامية.', 'A warm flannel shirt with a small check and chest pockets.'],
            ['shirts', 'short-sleeve-shirt', 'قميص كم قصير', 'Short-Sleeve Shirt', 39900, [$w, $n], false, 'قميص كم قصير بقصة مستقيمة، مناسب للعمل الصيفي.', 'A straight-cut short-sleeve shirt for summer days at work.'],
            ['shirts', 'denim-shirt', 'قميص دنيم', 'Denim Shirt', 59900, [$d], false, 'قميص دنيم متوسط الوزن يمكن لبسه كقطعة خارجية.', 'A mid-weight denim shirt that works as an overshirt.'],

            ['pants', 'chino-pants', 'بنطلون شينو', 'Chino Pants', 54900, [$be, $n], true, 'بنطلون شينو بقصة مستقيمة وجيوب جانبية.', 'Straight-cut chinos with side pockets.'],
            ['pants', 'jogger-pants', 'بنطلون جوجر', 'Jogger Pants', 49900, [$g, $b], false, 'بنطلون جوجر بحزام مطاطي وأساور عند الكاحل.', 'Joggers with an elastic waist and cuffed ankles.'],
            ['pants', 'straight-jeans', 'بنطلون جينز مستقيم', 'Straight Jeans', 64900, [$d, $b], false, 'جينز مستقيم بخصر متوسط وخامة دنيم ثابتة.', 'Straight jeans with a mid rise and sturdy denim.'],
            ['pants', 'cargo-pants', 'بنطلون كارجو', 'Cargo Pants', 59900, [$o, $be], false, 'بنطلون كارجو بجيوب جانبية واسعة وقصة مريحة.', 'Relaxed cargo pants with roomy side pockets.'],
            ['pants', 'wide-leg-trousers', 'بنطلون واسع', 'Wide-Leg Trousers', 57900, [$b, $be], false, 'بنطلون بقصة واسعة من الخصر حتى الكاحل.', 'Wide-leg trousers that fall straight to the ankle.'],
            ['pants', 'linen-pants', 'بنطلون كتان', 'Linen Pants', 52900, [$w, $s], false, 'بنطلون كتان خفيف بحزام داخلي مريح.', 'Light linen trousers with a comfortable inner waistband.'],

            ['hoodies', 'essential-hoodie', 'هودي أساسي', 'Essential Hoodie', 64900, [$b, $g], true, 'هودي قطن ثقيل بجيب أمامي وكابيون قابل للربط.', 'A heavy cotton hoodie with a front pocket and adjustable hood.'],
            ['hoodies', 'zip-hoodie', 'هودي بسحاب', 'Zip Hoodie', 69900, [$n, $g], false, 'هودي بسحاب كامل وجيوب جانبية.', 'A full-zip hoodie with side pockets.'],
            ['hoodies', 'oversized-hoodie', 'هودي أوفر سايز', 'Oversized Hoodie', 72900, [$be, $o], false, 'هودي بقصة واسعة وكتف مسدول لراحة أكبر.', 'An oversized hoodie with dropped shoulders for extra comfort.'],
            ['hoodies', 'crewneck-sweatshirt', 'سويت شيرت', 'Crewneck Sweatshirt', 59900, [$w, $bl], false, 'سويت شيرت برقبة دائرية وقماش قطني ناعم.', 'A crewneck sweatshirt in soft cotton.'],
            ['hoodies', 'cairo-hoodie', 'هودي كايرو', 'Cairo Hoodie', 74900, [$t, $b], true, 'هودي ثقيل بطبعة «كايرو» صغيرة على الصدر.', 'A heavy hoodie with a small “Cairo” print.'],

            ['bags', 'natural-canvas-tote', 'شنطة قماش طبيعي', 'Natural Canvas Tote', 24900, [$s], true, 'شنطة قماش طبيعي بحجم مناسب للعمل والسوق.', 'A natural canvas tote sized for work and errands.'],
            ['bags', 'black-canvas-tote', 'شنطة قماش سوداء', 'Black Canvas Tote', 24900, [$b], false, 'شنطة قماش سوداء بيدين طويلتين وجيب داخلي.', 'A black canvas tote with long handles and an inner pocket.'],
            ['bags', 'canvas-crossbody', 'شنطة كتف قماش', 'Canvas Crossbody', 34900, [$o, $s], false, 'شنطة كتف صغيرة بحزام قابل للتعديل.', 'A small crossbody bag with an adjustable strap.'],
            ['bags', 'canvas-backpack', 'شنطة ظهر قماش', 'Canvas Backpack', 44900, [$n, $g], false, 'شنطة ظهر بحمالات مبطنة وجيب للابتوب.', 'A backpack with padded straps and a laptop sleeve.'],
            ['bags', 'canvas-pouch', 'شنطة صغيرة', 'Canvas Pouch', 14900, [$s, $b], false, 'شنطة صغيرة للاحتياجات السريعة.', 'A small pouch for everyday essentials.'],

            ['accessories', 'cotton-cap', 'كاب قطني', 'Cotton Cap', 19900, [$b, $s], false, 'كاب قطني بحزام خلفي قابل للتعديل.', 'A cotton cap with an adjustable back strap.'],
            ['accessories', 'bucket-hat', 'قبعة بكت', 'Bucket Hat', 22900, [$s, $o], false, 'قبعة بكت قطنية بحواف قصيرة.', 'A cotton bucket hat with a short brim.'],
            ['accessories', 'knit-beanie', 'طاقية تريكو', 'Knit Beanie', 17900, [$g, $b], false, 'طاقية تريكو دافئة بقصة مستقيمة.', 'A warm knit beanie in a straight fit.'],
            ['accessories', 'canvas-belt', 'حزام قماش', 'Canvas Belt', 14900, [$o, $b], false, 'حزام قماش بإبزيم معدني بسيط.', 'A canvas belt with a simple metal buckle.'],
            ['accessories', 'socks-pack', 'طقم جوارب', 'Socks Pack', 9900, [$w, $b], false, 'طقم من ثلاث أزواج جوارب قطنية.', 'A three-pair pack of cotton socks.'],
        ];

        $inventory = app(InventoryService::class);

        DB::transaction(function () use ($products, $categoryModels, $inventory) {
            foreach ($products as $index => $data) {
                [$category, $slug, $nameAr, $nameEn, $price, $colors, $featured, $descAr, $descEn] = $data;

                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $categoryModels[$category]->id,
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'brand' => 'قُطن',
                        'description_ar' => $descAr,
                        'description_en' => $descEn,
                        'is_active' => true,
                        'is_featured' => $featured,
                    ],
                );

                $sizes = in_array($category, ['bags', 'accessories'], true)
                    ? ($slug === 'socks-pack' ? ['35–38', '39–42', '43–46'] : ['مقاس واحد'])
                    : self::SIZES;

                $position = 0;
                foreach ($colors as $colorIndex => [$colorName, $colorHex]) {
                    foreach ($sizes as $size) {
                        $sku = sprintf('QTN-%03d-%d-%s', $index + 1, $colorIndex + 1, $this->skuSuffix($size));
                        $stock = 60;

                        $variant = $product->variants()->updateOrCreate(
                            ['sku' => $sku],
                            [
                                'size' => $size,
                                'color' => $colorName,
                                'color_hex' => $colorHex,
                                'price_cents' => $price,
                                'low_stock_threshold' => 5,
                                'is_active' => true,
                                'position' => $position++,
                            ],
                        );

                        if (! $variant->movements()->exists()) {
                            if ($variant->stock !== 0) {
                                $variant->forceFill(['stock' => 0, 'reserved' => 0])->save();
                            }
                            $inventory->stockIn($variant, $stock, null, 'demo_initial_stock');
                        }
                    }
                }

                $this->ensureIllustration($product, $category, $colors[0][1]);
            }
        });
    }

    private function ensureIllustration(Product $product, string $categorySlug, string $color): void
    {
        if ($product->images()->exists()) {
            return;
        }

        $path = 'demo/product-'.$product->id.'.svg';
        $svg = trim(view('products.illustration', [
            'category' => $categorySlug,
            'color' => $color,
        ])->render());

        Storage::disk('public')->put($path, $svg);

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'path' => $path],
            ['alt_ar' => $product->name_ar, 'alt_en' => $product->name_en, 'position' => 0],
        );
    }

    private function skuSuffix(string $size): string
    {
        return match ($size) {
            'مقاس واحد' => 'OS',
            '35–38' => 'S1',
            '39–42' => 'S2',
            '43–46' => 'S3',
            default => strtoupper($size),
        };
    }
}
