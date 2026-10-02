<?php

return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'easykash' => [
        'enabled' => (bool) env('EASYKASH_ENABLED', true),
        'base_url' => rtrim(env('EASYKASH_BASE_URL', 'https://back.easykash.net'), '/'),
        'api_key' => env('EASYKASH_API_KEY'),
        'hmac_secret' => env('EASYKASH_HMAC_SECRET'),
        'payment_options' => array_values(array_filter(array_map('intval', explode(',', (string) env('EASYKASH_PAYMENT_OPTIONS', ''))))),
        'cash_expiry_hours' => (int) env('EASYKASH_CASH_EXPIRY_HOURS', 12),
    ],
];
