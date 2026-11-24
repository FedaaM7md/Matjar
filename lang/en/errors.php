<?php

return [
    '404' => [
        'title' => 'Page not found',
        'body' => 'The link you opened does not exist or has moved. Try heading back to the store.',
    ],
    '403' => [
        'title' => 'You do not have access',
        'body' => 'This action needs a permission your account does not have, or the order link is no longer valid.',
    ],
    '419' => [
        'title' => 'Session expired',
        'body' => 'Refresh the page and try again.',
    ],
    '500' => [
        'title' => 'Something went wrong',
        'body' => 'We could not complete the request. No money was collected; please try again later.',
    ],
    'back_home' => 'Back to the store',
];
