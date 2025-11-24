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
    | Catatan: Config ini sebagai fallback jika API gagal fetch symbol info
    */

    'pairs' => [
        'ENAUSDT' => [
            'min_size' => 2.00,      // Minimum order dalam koin (bukan USDT)
            'precision' => 4,        // 4 decimal places untuk quantity
        ],
        'BTCUSDT' => [
            'min_size' => 0.00001,
            'precision' => 8,
        ],
        'ETHUSDT' => [
            'min_size' => 0.0001,
            'precision' => 6,
        ],
        'TAOUSDT' => [
            'min_size' => 0.01,
            'precision' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    // Whitelist IP yang boleh mengirim signal (kosongkan untuk allow all)
    // Format: '1.2.3.4,5.6.7.8' atau kosongkan
    'allowed_ips' => env('BITGET_ALLOWED_IPS', '') 
        ? explode(',', env('BITGET_ALLOWED_IPS')) 
        : [],

    // API token untuk autentikasi webhook (optional)
    // Set token di header X-Webhook-Token atau query param ?token=xxx
    'webhook_token' => env('BITGET_WEBHOOK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Advanced Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum retry untuk API calls
    'max_retries' => env('BITGET_MAX_RETRIES', 3),

    // Timeout untuk HTTP requests (seconds)
    'timeout' => env('BITGET_TIMEOUT', 30),

    // Enable dry run mode (simulasi tanpa execute order)
    'dry_run' => env('BITGET_DRY_RUN', false),
];