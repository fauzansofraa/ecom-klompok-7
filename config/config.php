<?php
/**
 * LZFJ Shoes Store - konfigurasi.
 *
 * Production/Vercel:
 *   credential dibaca dari Environment Variables.
 *
 * Local/Laragon:
 *   jika ada config/config.local.php, nilainya dipakai sebagai fallback.
 */
$local = [];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (!is_array($local)) {
        $local = [];
    }
}

$env = static function (string $name, $fallback = '') use ($local) {
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return $fallback;
};

$isProduction = filter_var(
    $env('MIDTRANS_IS_PRODUCTION', $local['midtrans']['is_production'] ?? false),
    FILTER_VALIDATE_BOOLEAN
);

return [
    // Untuk Vercel sebaiknya BASE_URL diisi otomatis dari environment.
    // Jika kosong, bootstrap.php akan mendeteksi host.
    'base_url' => $env('BASE_URL', $local['base_url'] ?? ''),

    'db' => [
        'host'     => $env('DB_HOST', $local['db']['host'] ?? '127.0.0.1'),
        'port'     => (int) $env('DB_PORT', $local['db']['port'] ?? 3306),
        'name'     => $env('DB_NAME', $local['db']['name'] ?? 'lzfj_shoes_store'),
        'user'     => $env('DB_USER', $local['db']['user'] ?? 'root'),
        'password' => $env('DB_PASSWORD', $local['db']['password'] ?? ''),
    ],

    'google' => [
        'client_id' => $env('GOOGLE_CLIENT_ID', $local['google']['client_id'] ?? ''),
        'client_secret' => $env('GOOGLE_CLIENT_SECRET', $local['google']['client_secret'] ?? ''),
        // Jika kosong, otomatis menjadi BASE_URL/google-callback.php
        'redirect_uri' => $env('GOOGLE_REDIRECT_URI', $local['google']['redirect_uri'] ?? ''),
    ],

    'midtrans' => [
        'is_production' => $isProduction,
        'server_key' => $env('MIDTRANS_SERVER_KEY', $local['midtrans']['server_key'] ?? ''),
        'client_key' => $env('MIDTRANS_CLIENT_KEY', $local['midtrans']['client_key'] ?? ''),
    ],

    'shipping' => [
        'regular' => 25000,
        'express' => 50000,
    ],

    'free_shipping_min' => 0,

    'store' => [
        'name' => 'LZFJ Shoes Store',
        'address' => '[STORE ADDRESS]',
        'email' => '[STORE EMAIL]',
        'support' => '[SUPPORT CONTACT]',
        'whatsapp' => '6281234567890',
    ],
];
