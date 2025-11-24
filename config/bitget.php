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
    'allowed_pair' => env('BITGET_ALLOWED_PAIR', 'TAOUSDT'),

    // Mode trading: 'percentage' atau 'fixed'
    'trade_mode' => env('BITGET_TRADE_MODE', 'fixed'),

    // Jika mode = 'percentage': Persentase balance USDT yang digunakan (1-100)
    'trade_percent' => env('BITGET_TRADE_PERCENT', 95),

    // Jika mode = 'fixed': Jumlah USDT tetap yang digunakan per order
    'trade_fixed_usdt' => env('BITGET_TRADE_FIXED_USDT', 2),

    // Minimum balance USDT yang harus ada
    'min_usdt_balance' => env('BITGET_MIN_USDT_BALANCE', 2),

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
        'ENASDT' => [
            'min_size' => 2.00,
            'precision' => 4,         // 4 decimal places
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