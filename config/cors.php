<?php

/**
 * Konfigurasi CORS (Cross-Origin Resource Sharing) untuk Laravel.
 * Mengizinkan frontend React (berjalan di port berbeda) untuk mengakses API.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Paths yang Diizinkan CORS
    |--------------------------------------------------------------------------
    | Daftar path API yang mengizinkan request dari origin yang berbeda.
    | 'api/*' mencakup semua endpoint API, 'sanctum/csrf-cookie' untuk Sanctum.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | HTTP Methods yang Diizinkan
    |--------------------------------------------------------------------------
    | Metode HTTP yang boleh digunakan oleh request cross-origin.
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Origins yang Diizinkan
    |--------------------------------------------------------------------------
    | Domain frontend yang diizinkan mengakses API ini.
    | Sesuaikan dengan URL development dan production frontend Anda.
    */
    'allowed_origins' => [
        'http://localhost:5173',  // Vite dev server default port
        'http://localhost:3000',  // Alternatif port development
        'http://127.0.0.1:5173',
    ],

    /*
    |--------------------------------------------------------------------------
    | Patterns Origins yang Diizinkan
    |--------------------------------------------------------------------------
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Headers yang Diizinkan
    |--------------------------------------------------------------------------
    | Header HTTP yang boleh dikirim dalam request cross-origin.
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Headers yang Diekspos
    |--------------------------------------------------------------------------
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Durasi Cache Preflight
    |--------------------------------------------------------------------------
    | Berapa lama (dalam detik) hasil preflight request di-cache browser.
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Credentials Support
    |--------------------------------------------------------------------------
    | Mengizinkan pengiriman credentials (cookies, authorization headers) dalam CORS.
    */
    'supports_credentials' => true,

];
