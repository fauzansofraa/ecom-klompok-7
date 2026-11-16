<?php
/**
 * Bootstrap: dimuat di awal setiap halaman.
 * - Membaca konfigurasi
 * - Memulai session
 * - Koneksi database (PDO MySQL)
 * - Fungsi bantuan (helper)
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');

$GLOBALS['config'] = require APP_ROOT . '/config/config.php';

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

// ---------------------------------------------------------------- Session
$sessionLifetime = 60 * 60 * 24 * 30; // 30 hari (dipakai saat "Remember Me")
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_name('LZFJSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ---------------------------------------------------------------- Database
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) $c['port'], $c['name']);
    try {
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'"); // WIB, agar waktu pesanan sesuai jam Indonesia
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<div style="font-family:Poppins,Arial,sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #e5e5e5;border-radius:12px">'
            . '<h2 style="margin:0 0 8px">Tidak bisa terhubung ke database</h2>'
            . '<p style="color:#666">Pastikan MySQL di XAMPP sudah <b>Start</b>, database <b>' . htmlspecialchars($c['name'])
            . '</b> sudah dibuat &amp; file <code>database/lzfj_shoes_store.sql</code> sudah di-import, lalu cek <code>config/config.php</code>.</p>'
            . '<pre style="background:#f4f4f4;padding:12px;border-radius:8px;white-space:pre-wrap">' . htmlspecialchars($e->getMessage()) . '</pre></div>';
        exit;
    }
    return $pdo;
}

// ---------------------------------------------------------------- URL helpers
function base_url(): string
{
    $configured = rtrim((string) config('base_url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }

    // Vercel terminates HTTPS before the container. Therefore relying only on
    // $_SERVER['HTTPS'] can incorrectly generate an http:// callback URL.
    $vercelHost = getenv('VERCEL_PROJECT_PRODUCTION_URL')
        ?: getenv('VERCEL_URL')
        ?: '';
    if ($vercelHost !== '') {
        return 'https://' . preg_replace('#^https?://#i', '', rtrim($vercelHost, '/'));
    }

    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function product_image(string $file): string
{
    return asset('img/products/' . $file) . '?v=4'; // ?v= supaya browser memuat ulang foto yang sudah diganti arahnya
}

function redirect(string $path): never
{
    $target = preg_match('#^https?://#', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Hanya izinkan redirect internal (mencegah open-redirect). */
function safe_redirect_path(?string $path, string $fallback = 'home.php'): string
{
    $path = (string) $path;
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || str_contains($path, "\n")) {
        return $fallback;
    }
    return ltrim($path, '/');
}

// ---------------------------------------------------------------- Output helpers
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int|float $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// ---------------------------------------------------------------- Flash message
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------------------------------------------------------------- CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['ok' => false, 'message' => 'Session expired, please refresh the page.'], 419);
        }
        http_response_code(419);
        exit('Session expired, please go back and refresh the page.');
    }
}

// ---------------------------------------------------------------- Auth
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function login_user(int $userId, bool $remember = false): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    wishlist_merge_session($userId); // wishlist saat belum login ikut tersimpan ke akun
    if ($remember) {
        $p = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => $p['path'],
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $p['secure'],
        ]);
    }
}

function require_login(?string $returnTo = null): array
{
    $user = current_user();
    if (!$user) {
        $returnTo = $returnTo ?? ltrim(basename($_SERVER['SCRIPT_NAME'] ?? 'home.php') . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']), '/');
        flash('info', 'Please log in first to continue.');
        redirect('login.php?redirect=' . urlencode($returnTo));
    }
    return $user;
}

function unique_username(string $base): string
{
    $base = strtolower(preg_replace('/[^a-z0-9_]/i', '', $base)) ?: 'user';
    $base = substr($base, 0, 40);
    $candidate = $base;
    $i = 1;
    $stmt = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    while (true) {
        $stmt->execute([$candidate]);
        if (!$stmt->fetchColumn()) {
            return $candidate;
        }
        $candidate = $base . (++$i);
    }
}

// ---------------------------------------------------------------- Products
function parse_colors(string $raw): array
{
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', $raw))) as $pair) {
        [$name, $hex] = array_pad(explode(':', $pair, 2), 2, '#111111');
        $out[] = ['name' => trim($name), 'hex' => trim($hex)];
    }
    return $out;
}

function parse_sizes(string $raw): array
{
    return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
}

function product_public(array $p): array
{
    return [
        'id'          => (int) $p['id'],
        'slug'        => $p['slug'],
        'name'        => $p['name'],
        'brand'       => $p['brand'],
        'category'    => strtoupper($p['category']),
        'price'       => (int) $p['price'],
        'price_label' => rupiah((int) $p['price']),
        'old_price'   => $p['old_price'] ? rupiah((int) $p['old_price']) : null,
        'badge'       => $p['badge'],
        'rating'      => number_format((float) $p['rating'], 1),
        'reviews'     => (int) $p['reviews'],
        'sold'        => (int) $p['sold'],
        'description' => $p['description'],
        'image'       => product_image($p['image']),
        'sizes'       => parse_sizes($p['sizes']),
        'colors'      => parse_colors($p['colors']),
        'stock'       => (int) $p['stock'],
    ];
}

function find_product(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------------------- Cart (disimpan di session)
function cart_items(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

/** Total jumlah sebuah produk di keranjang (semua ukuran / warna). */
function product_cart_qty(int $productId, ?string $excludeKey = null): int
{
    $total = 0;
    foreach (cart_items() as $key => $row) {
        if ((int) $row['product_id'] === $productId && $key !== $excludeKey) {
            $total += (int) $row['qty'];
        }
    }
    return $total;
}

function cart_add(int $productId, string $size, string $color, int $qty): void
{
    $key = $productId . '|' . $size . '|' . $color;
    $cart = cart_items();
    if (isset($cart[$key])) {
        $cart[$key]['qty'] = min(10, $cart[$key]['qty'] + $qty);
    } else {
        $cart[$key] = ['product_id' => $productId, 'size' => $size, 'color' => $color, 'qty' => min(10, $qty)];
    }
    $_SESSION['cart'] = $cart;
}

/** Ubah jumlah item. Jumlah otomatis dibatasi oleh stok yang tersedia. */
function cart_update(string $key, int $qty): void
{
    if (!isset($_SESSION['cart'][$key])) {
        return;
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $productId = (int) $_SESSION['cart'][$key]['product_id'];
    $product = find_product($productId);
    $stock = $product ? (int) $product['stock'] : 0;
    $room = $stock - product_cart_qty($productId, $key); // sisa stok untuk baris ini
    if ($room <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $_SESSION['cart'][$key]['qty'] = min(10, $qty, $room);
}

/** Teks stok untuk ditampilkan ke pembeli. */
function stock_text(int $stock): string
{
    if ($stock <= 0) {
        return 'Out of stock';
    }
    if ($stock <= 5) {
        return 'Only ' . $stock . ' left';
    }
    return 'Stock: ' . $stock;
}

/**
 * Pesanan yang belum dibayar melewati batas waktu Midtrans (24 jam) dianggap kedaluwarsa,
 * lalu stoknya dikembalikan. Berguna jika notifikasi Midtrans tidak pernah masuk (mis. di localhost).
 */
function release_expired_orders(): void
{
    try {
        $pdo = db();
        $ids = $pdo->query("SELECT id FROM orders WHERE status = 'pending' AND stock_restored = 0 AND created_at < (NOW() - INTERVAL 25 HOUR)")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            $flag = $pdo->prepare("UPDATE orders SET status = 'expired', stock_restored = 1 WHERE id = ? AND status = 'pending' AND stock_restored = 0");
            $flag->execute([$id]);
            if ($flag->rowCount() === 1) {
                $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
                $items->execute([$id]);
                $restock = $pdo->prepare('UPDATE products SET stock = stock + ?, sold = GREATEST(CAST(sold AS SIGNED) - ?, 0) WHERE id = ?');
                foreach ($items->fetchAll() as $it) {
                    $restock->execute([$it['qty'], $it['qty'], $it['product_id']]);
                }
            }
        }
    } catch (Throwable $e) {
        // abaikan: pembersihan ini tidak boleh membuat halaman error
    }
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['promo']);
}

/** Gabungkan isi cart dengan data produk terbaru dari database. */
function cart_detailed(): array
{
    $items = [];
    $subtotal = 0;
    foreach (cart_items() as $key => $row) {
        $p = find_product((int) $row['product_id']);
        if (!$p) {
            continue;
        }
        $line = (int) $p['price'] * (int) $row['qty'];
        $subtotal += $line;
        $items[] = [
            'key'     => $key,
            'product' => $p,
            'size'    => $row['size'],
            'color'   => $row['color'],
            'qty'     => (int) $row['qty'],
            'line'    => $line,
        ];
    }
    return ['items' => $items, 'subtotal' => $subtotal];
}

function shipping_cost(string $method, int $subtotal): int
{
    $min = (int) config('free_shipping_min', 0);
    if ($min > 0 && $subtotal >= $min) {
        return 0;
    }
    return (int) config('shipping.' . ($method === 'express' ? 'express' : 'regular'), 25000);
}

function promo_discount(?array $promo, int $subtotal): int
{
    if (!$promo || $subtotal < (int) $promo['min_subtotal']) {
        return 0;
    }
    $d = $promo['type'] === 'percent'
        ? (int) floor($subtotal * ((int) $promo['value']) / 100)
        : (int) $promo['value'];
    return max(0, min($d, $subtotal));
}

function find_promo(string $code): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM promo_codes
         WHERE code = ? AND is_active = 1
           AND starts_on <= CURRENT_DATE()
           AND (ends_on IS NULL OR ends_on >= CURRENT_DATE())'
    );
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetch() ?: null;
}

function daily_promos(): array
{
    return db()->query(
        'SELECT * FROM promo_codes
         WHERE is_active = 1
           AND starts_on <= CURRENT_DATE()
           AND (ends_on IS NULL OR ends_on >= CURRENT_DATE())
         ORDER BY min_subtotal ASC, code ASC'
    )->fetchAll();
}

// ---------------------------------------------------------------- Wishlist
// Login  -> disimpan di tabel `wishlists` (ikut ke semua perangkat)
// Tamu   -> disimpan di session, dipindah ke akun saat login
function wishlist_ensure_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        db()->exec('CREATE TABLE IF NOT EXISTS `wishlists` (
            `user_id`    INT UNSIGNED NOT NULL,
            `product_id` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`, `product_id`),
            KEY `idx_wish_product` (`product_id`),
            CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_wish_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    } catch (Throwable $e) {
        // tabel mungkin sudah ada / hak akses terbatas: abaikan
    }
}

/** Daftar id produk di wishlist (terbaru dulu). */
function wishlist_ids(bool $fresh = false): array
{
    if (!$fresh && isset($GLOBALS['_wishlist_cache'])) {
        return $GLOBALS['_wishlist_cache'];
    }
    $ids = [];
    $user = current_user();
    if ($user) {
        wishlist_ensure_table();
        $stmt = db()->prepare('SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC, product_id DESC');
        $stmt->execute([$user['id']]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $ids = array_reverse(array_map('intval', $_SESSION['wishlist'] ?? []));
    }
    return $GLOBALS['_wishlist_cache'] = $ids;
}

function wishlist_has(int $productId): bool
{
    return in_array($productId, wishlist_ids(), true);
}

function wishlist_count(): int
{
    return count(wishlist_ids());
}

/** Tambah / hapus dari wishlist. Mengembalikan true jika sekarang ada di wishlist. */
function wishlist_toggle(int $productId): bool
{
    $on = !wishlist_has($productId);
    $user = current_user();
    if ($user) {
        wishlist_ensure_table();
        if ($on) {
            db()->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)')->execute([$user['id'], $productId]);
        } else {
            db()->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')->execute([$user['id'], $productId]);
        }
    } else {
        $list = array_values(array_diff(array_map('intval', $_SESSION['wishlist'] ?? []), [$productId]));
        if ($on) {
            $list[] = $productId;
        }
        $_SESSION['wishlist'] = $list;
    }
    unset($GLOBALS['_wishlist_cache']);
    return $on;
}

/** Pindahkan wishlist tamu (session) ke akun yang baru login. */
function wishlist_merge_session(int $userId): void
{
    $list = array_map('intval', $_SESSION['wishlist'] ?? []);
    unset($_SESSION['wishlist'], $GLOBALS['_wishlist_cache']);
    if (!$list) {
        return;
    }
    try {
        wishlist_ensure_table();
        $stmt = db()->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) SELECT ?, id FROM products WHERE id = ?');
        foreach ($list as $pid) {
            $stmt->execute([$userId, $pid]);
        }
    } catch (Throwable $e) {
        // jangan gagalkan login hanya karena wishlist
    }
}

// ---------------------------------------------------------------- Payment channels
function payment_channels(): array
{
    return [
        'bank_transfer' => [
            'label' => 'Bank Transfer',
            'channels' => [
                'bca_va'     => ['name' => 'BCA',         'full' => 'BCA Virtual Account',         'color' => '#0060AF', 'logo' => 'bca'],
                'bni_va'     => ['name' => 'BNI',         'full' => 'BNI Virtual Account',         'color' => '#F15A23', 'logo' => 'bni'],
                'bri_va'     => ['name' => 'BRI',         'full' => 'BRI Virtual Account',         'color' => '#00529C', 'logo' => 'bri'],
                'echannel'   => ['name' => 'Mandiri',     'full' => 'Mandiri Bill Payment',        'color' => '#003D79', 'logo' => 'mandiri'],
                'bsi_va'     => ['name' => 'BSI',         'full' => 'BSI Virtual Account',         'color' => '#00A39D', 'logo' => 'bsi', 'note' => 'BSI Virtual Account is paid through the BYOND by BSI app: open the app, choose Pay / Virtual Account, then enter the VA number shown after you place your order.'],
                'permata_va' => ['name' => 'Permata',     'full' => 'Permata Virtual Account',     'color' => '#E2001A', 'logo' => 'permata'],
                'cimb_va'    => ['name' => 'CIMB Niaga',  'full' => 'CIMB Niaga Virtual Account',  'color' => '#7A0019', 'logo' => 'cimb'],
            ],
        ],
        'ewallet' => [
            'label' => 'E-Wallet',
            'channels' => [
                'gopay'      => ['name' => 'GoPay',     'full' => 'GoPay',     'color' => '#00AED6', 'logo' => 'gopay'],
                'shopeepay'  => ['name' => 'ShopeePay', 'full' => 'ShopeePay', 'color' => '#EE4D2D', 'logo' => 'shopeepay'],
                'other_qris' => ['name' => 'QRIS',      'full' => 'QRIS (DANA, OVO, LinkAja, dll)', 'color' => '#111111', 'logo' => 'qris'],
            ],
        ],
        'card' => [
            'label' => 'Credit / Debit Card',
            'channels' => [
                'credit_card' => ['name' => 'Card', 'full' => 'Visa / Mastercard / JCB', 'color' => '#111111', 'logo' => 'card'],
            ],
        ],
    ];
}

/**
 * URL logo pembayaran. Mencari file di assets/img/payment/<nama>.(svg|png|webp|jpg|jpeg|gif)
 * (taruh logo resmi di sana). Jika tidak ada, memakai logo sementara di assets/img/payment/placeholder/.
 */
function payment_logo_url(string $name): string
{
    $root = APP_ROOT . '/assets/img/payment/';
    foreach (['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'] as $ext) {
        if (is_file($root . $name . '.' . $ext)) {
            return asset('img/payment/' . $name . '.' . $ext);
        }
    }
    return asset('img/payment/placeholder/' . $name . '.svg');
}

function payment_label(string $method, string $channel): string
{
    $all = payment_channels();
    $m = $all[$method] ?? null;
    if (!$m) {
        return ucfirst($method);
    }
    if ($method === 'card') {
        return $m['label'];
    }
    $c = $m['channels'][$channel]['name'] ?? strtoupper($channel);
    return $m['label'] . ' - ' . $c;
}

function status_label(string $status): string
{
    return [
        'pending'   => 'Waiting for payment',
        'paid'      => 'Paid',
        'failed'    => 'Failed',
        'expired'   => 'Expired',
        'cancelled' => 'Cancelled',
        'refunded'  => 'Refunded',
    ][$status] ?? ucfirst($status);
}

require_once APP_ROOT . '/includes/http.php';
require_once APP_ROOT . '/includes/icons.php';
