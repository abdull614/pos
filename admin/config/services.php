<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'trends' => [
        'url' => env('TRENDS_SERVICE_URL', 'http://localhost:5000'),
        'key' => env('TRENDS_SERVICE_KEY'),
        'geo' => env('TRENDS_SERVICE_GEO', 'ID'),

        // Daftar kata kunci produk sembako umum, dipakai untuk cek skor
        // trending "Trend Global" -- supaya hasilnya relevan dengan warung
        // sembako, bukan topik trending nasional yang acak (berita, bola, dll).
        // Silakan tambah/kurangi sesuai jenis dagangan toko Anda.
        'sembako_keywords' => [
            'beras', 'minyak goreng', 'gula pasir', 'telur ayam', 'tepung terigu',
            'kecap manis', 'saus sambal', 'garam dapur', 'mie instan', 'susu kental manis',
            'kopi bubuk', 'teh celup', 'sabun mandi', 'sabun cuci piring', 'deterjen',
            'pasta gigi', 'shampoo', 'mentega', 'margarin', 'bawang merah',
            'bawang putih', 'cabai', 'gas lpg', 'air mineral', 'kopi instan',
            'sarden kaleng', 'kornet', 'biskuit', 'wafer', 'permen',
        ],
    ],

];