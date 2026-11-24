<?php

return [
    'types' => [
        'percent' => 'Percentage',
        'fixed' => 'Fixed amount',
    ],
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'scheduled' => 'Scheduled',
        'expired' => 'Expired',
        'exhausted' => 'Exhausted',
    ],
    'errors' => [
        'not_found' => 'This coupon code does not exist.',
        'inactive' => 'This coupon is not active.',
        'not_started' => 'This coupon is not valid yet.',
        'expired' => 'This coupon has expired.',
        'usage_limit' => 'This coupon has reached its usage limit.',
        'per_user_limit' => 'You have already used this coupon the maximum number of times.',
        'min_subtotal' => 'The minimum order for this coupon is :amount.',
    ],
];
