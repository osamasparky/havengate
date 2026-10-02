<?php

return [
    /*
    | Supported public locales. `dir` drives <html dir>, fonts and logical CSS.
    */
    'locales' => [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'carbon' => 'en'],
        'ar' => ['name' => 'Arabic',  'native' => 'العربية', 'dir' => 'rtl', 'carbon' => 'ar'],
        'he' => ['name' => 'Hebrew',  'native' => 'עברית',   'dir' => 'rtl', 'carbon' => 'he'],
    ],

    'default_locale' => env('APP_LOCALE', 'en'),

    // All prices are stored in this currency (integer minor units are NOT used:
    // EGP amounts are stored as decimal(12,2)).
    'currency' => env('HG_CURRENCY', 'EGP'),

    // Minutes a pending booking holds its unit while the guest pays.
    'hold_minutes' => (int) env('HG_HOLD_MINUTES', 20),

    // How far ahead guests may book.
    'max_advance_days' => 540,

    'admin_email' => env('HG_ADMIN_EMAIL'),

    'coordinates' => ['lat' => 29.069222, 'lng' => 34.669965],

    'social' => [
        'instagram' => env('HG_INSTAGRAM', 'https://www.instagram.com/heavengatecamp/'),
        'whatsapp' => env('HG_WHATSAPP'),
    ],
];
