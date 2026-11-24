<?php

return [
    'about' => [
        'title' => 'About Qoton',
        'meta' => 'Qoton is a simple Egyptian everyday-clothing label: cotton pieces in a full size range at clear prices.',
        'intro' => 'Qoton started in Cairo with one idea: everyday clothes you reach for often, in a size range that includes everyone, with the price written plainly.',
        'story' => [
            'We make basics — tees, shirts, pants and hoodies — in comfortable cotton and calm cuts that do not chase fast fashion.',
            'Most pieces run from S to XXL, and every size’s measurements are listed in the size guide so you get it right the first time.',
            'We ship from our Cairo store to every Egyptian governorate, with cash on delivery always available.',
        ],
        'values' => [
            ['title' => 'Cloth that lasts', 'body' => 'We choose practical cotton fabrics that hold up to repeat washing and everyday wear.'],
            ['title' => 'Clear price', 'body' => 'The price you see is the final price of the piece; shipping is calculated for your governorate.'],
            ['title' => 'Full size range', 'body' => 'S to XXL on most pieces, with a simple size guide.'],
        ],
    ],

    'contact' => [
        'title' => 'Contact us',
        'meta' => 'Reach the Qoton team by phone or email, or visit our Cairo store.',
        'intro' => 'Our team is available Saturday to Thursday for questions about sizing, shipping and exchanges.',
        'phone_label' => 'Phone',
        'phone' => '01000000000',
        'email_label' => 'Email',
        'email' => 'hello@qoton.example.com',
        'address_label' => 'Store',
        'address' => 'New Cairo — Fifth Settlement (sample address)',
        'hours_label' => 'Opening hours',
        'hours' => 'Saturday – Thursday, 10am to 7pm',
        'note' => 'These contact details are sample data for the demo build only.',
    ],

    'shipping' => [
        'title' => 'Shipping & returns',
        'meta' => 'Shipping details for every Egyptian governorate, free shipping, and our returns and exchange policy.',
        'intro' => 'We ship to all 27 Egyptian governorates from our Cairo store. Here is what you need to know before ordering.',
        'delivery' => [
            'title' => 'Delivery time',
            'body' => '2–3 working days in Cairo and Giza, and 3–5 working days for the rest of the governorates.',
        ],
        'rates' => [
            'title' => 'Shipping rate',
            'body' => 'From 50 EGP and it varies by governorate. The final rate appears at checkout once you choose your governorate.',
        ],
        'free' => [
            'title' => 'Free shipping',
            'body' => 'Free shipping on orders over 1,500 EGP after discount in most governorates.',
        ],
        'returns' => [
            'title' => 'Returns & exchanges',
            'body' => 'You can return or exchange within 14 days of delivery, provided the piece is in its original, unworn condition with its tag.',
        ],
        'returns_note' => 'To start a return, email us with your order number and we will arrange the pickup or exchange.',
    ],

    'size_guide' => [
        'title' => 'Size guide',
        'meta' => 'Qoton size guide in centimetres for every piece from S to XXL.',
        'intro' => 'Lay a similar piece you own flat and compare it with the table. All measurements are in centimetres and may vary by one or two.',
        'unit' => 'cm',
        'table' => [
            'size' => 'Size',
            'chest' => 'Chest',
            'waist' => 'Waist',
            'length' => 'Length',
        ],
        'sizes' => [
            ['label' => 'S', 'chest' => '92', 'waist' => '76', 'length' => '68'],
            ['label' => 'M', 'chest' => '98', 'waist' => '82', 'length' => '70'],
            ['label' => 'L', 'chest' => '104', 'waist' => '88', 'length' => '72'],
            ['label' => 'XL', 'chest' => '110', 'waist' => '94', 'length' => '74'],
            ['label' => 'XXL', 'chest' => '116', 'waist' => '100', 'length' => '76'],
        ],
        'measure_title' => 'How to measure',
        'measure_body' => 'Chest: measure around the fullest part of the chest. Waist: measure around your natural waist. Length: from the top of the shoulder to the bottom hem.',
    ],

    'privacy' => [
        'title' => 'Privacy policy',
        'meta' => 'How Qoton collects and uses your data, and your rights in the demo build.',
        'intro' => 'We respect your data. This page explains what we collect and how we use it.',
        'sections' => [
            ['title' => 'What we collect', 'body' => 'At checkout we collect your name, mobile number, address and governorate so we can deliver the order. Email is optional.'],
            ['title' => 'How we use it', 'body' => 'We use the data only to fulfil the order and keep you updated on its status. We do not sell your data to third parties.'],
            ['title' => 'Cookies', 'body' => 'We use an essential session to remember your cart and language. We do not use trackers or advertising tools.'],
            ['title' => 'Demo note', 'body' => 'This is a demo build; the data is sample data that resets automatically. Do not enter real personal data.'],
        ],
    ],

    'terms' => [
        'title' => 'Terms & conditions',
        'meta' => 'Terms of use for the Qoton demo store.',
        'intro' => 'By using this site you agree to these terms. This is a demo build, not a real store.',
        'sections' => [
            ['title' => 'Prices and orders', 'body' => 'Prices are shown in Egyptian pounds and include VAT where applicable. All orders in this build are demo orders and no money is collected.'],
            ['title' => 'Stock', 'body' => 'We reserve stock when an order is placed. If a quantity is unavailable, we will contact you to choose an alternative or cancel the order.'],
            ['title' => 'Returns', 'body' => 'The 14-day return policy applies to pieces in their original condition.'],
            ['title' => 'Liability', 'body' => 'This build is for demonstration only. Qoton is not liable for any commercial use of the data shown.'],
        ],
    ],
];
