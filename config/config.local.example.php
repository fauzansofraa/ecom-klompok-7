<?php
/**
 * COPY file ini menjadi:
 *   config/config.local.php
 *
 * File config.local.php di-ignore Git dan hanya untuk Laragon/local.
 * Jangan masukkan credential asli ke GitHub.
 */
return [
    'base_url' => 'http://localhost/lzfj-shoes-store',

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'lzfj_shoes_store',
        'user' => 'root',
        'password' => '',
    ],

    'google' => [
        'client_id' => 'ISI_GOOGLE_CLIENT_ID',
        'client_secret' => 'ISI_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/lzfj-shoes-store/google-callback.php',
    ],

    'midtrans' => [
        'is_production' => false,
        'server_key' => 'ISI_MIDTRANS_SANDBOX_SERVER_KEY',
        'client_key' => 'ISI_MIDTRANS_SANDBOX_CLIENT_KEY',
    ],
];
