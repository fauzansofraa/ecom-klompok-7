<?php
/** Beli lagi: masukkan kembali isi pesanan lama ke keranjang (mengikuti stok terbaru). */
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('account.php');
}
csrf_check();
$user = require_login('account.php');

$stmt = db()->prepare('SELECT id FROM orders WHERE order_code = ? AND user_id = ?');
$stmt->execute([(string) ($_POST['order'] ?? ''), $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'Order not found.');
    redirect('account.php');
}

$items = db()->prepare('SELECT * FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
$items->execute([$order['id']]);
$added = 0;
$skipped = 0;
foreach ($items->fetchAll() as $it) {
    $p = find_product((int) $it['product_id']);
    if (!$p || (int) $p['stock'] <= 0
        || !in_array($it['size'], parse_sizes($p['sizes']), true)
        || !in_array($it['color'], array_column(parse_colors($p['colors']), 'name'), true)) {
        $skipped++;
        continue;
    }
    $room = (int) $p['stock'] - product_cart_qty((int) $p['id']);
    $qty = min((int) $it['qty'], $room, 10);
    if ($qty <= 0) {
        $skipped++;
        continue;
    }
    cart_add((int) $p['id'], $it['size'], $it['color'], $qty);
    $added++;
}

if ($added === 0) {
    flash('error', 'Sorry, none of the items from this order are available right now.');
    redirect('account.php');
}
flash('success', $added . ($added === 1 ? ' item' : ' items') . ' added to your cart.' . ($skipped ? ' ' . $skipped . ' unavailable item(s) were skipped.' : ''));
redirect('cart.php');
