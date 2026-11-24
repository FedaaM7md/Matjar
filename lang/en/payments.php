<?php

return [
    'title' => 'Simulated payment gateway',
    'simulator' => 'Local payment simulation',
    'demo_warning' => 'This is not a real payment gateway. Never enter card data and no money is collected. The choice here only updates the order state for demo and testing purposes.',
    'order' => 'Order',
    'amount' => 'Amount',
    'method' => 'Method',
    'choose_outcome' => 'Choose a payment outcome',
    'success' => 'Success',
    'fail' => 'Failure',
    'cancel' => 'Cancel',
    'success_desc' => 'Confirm payment and move the order to confirmed.',
    'fail_desc' => 'Record a failed payment, cancel the order and release reserved stock.',
    'cancel_desc' => 'Cancel the payment and order, releasing reserved stock.',
    'back_to_order' => 'Back to the order',
    'already_paid' => 'This payment is already complete.',
    'success_flash' => 'The simulated payment was confirmed.',
    'failed' => 'The payment was recorded as failed and the order was cancelled.',
    'cancelled' => 'The payment and order were cancelled.',
    'signed_note' => 'Each button posts an HMAC signature covering amount, outcome and expiry. Any tampering invalidates it.',
    'errors' => [
        'invalid_signature' => 'Invalid or expired signature. The payment status was not changed.',
        'not_allowed' => 'This order does not use the simulated gateway.',
        'already_final' => 'This payment is not awaiting an action.',
        'order_not_open' => 'This order is no longer open for payment.',
    ],
];
