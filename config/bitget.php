<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bitget API Configuration
    |--------------------------------------------------------------------------
    |
    | API credentials untuk Bitget Exchange
    | Dapatkan dari: https://www.bitget.com/en/api-manage
    |
    */

    'api_key' => env('BITGET_API_KEY', ''),
    'api_secret' => env('BITGET_API_SECRET', ''),
    'passphrase' => env('BITGET_PASSPHRASE', ''),
    'base_url' => env('BITGET_BASE_URL', 'https://api.bitget.com'),

    /*
    |--------------------------------------------------------------------------
    | Trading Configuration
    |--------------------------------------------------------------------------
    */

    // Pair yang diizinkan untuk trading (HANYA SATU PAIR)
    'allowed_pair' => env('BITGET_ALLOWED_PAIR', 'ENAUSDT'),

    // Persentase balance USDT yang digunakan untuk trading (1-100)
    'trade_percent' => env('BITGET_TRADE_PERCENT', 95),

    // Minimum balance USDT yang harus ada
    'min_usdt_balance' => env('BITGET_MIN_USDT_BALANCE', 10),

    /*
    |--------------------------------------------------------------------------
    | Pair Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi per trading pair
    | Sesuaikan min_size dan precision per pair
    |
    */

    'pairs' => [
        'ENAUSDT' => [
            'min_size' => 3.84,
            'precision' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    // Whitelist IP yang boleh mengirim signal (kosongkan untuk allow all)
    'allowed_ips' => env('BITGET_ALLOWED_IPS', '') ? explode(',', env('BITGET_ALLOWED_IPS')) : [],

    // API token untuk autentikasi webhook (optional)
    'webhook_token' => env('BITGET_WEBHOOK_TOKEN', ''),
];