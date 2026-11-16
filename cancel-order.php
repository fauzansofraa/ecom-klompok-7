<?php
/** Batalkan pesanan yang belum dibayar (stok dikembalikan). */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/midtrans.php';

if (!is_post()) {
    redirect('account.php');
}
csrf_check();
$user = require_login('account.php');

$stmt = db()->prepare('SELECT * FROM orders WHERE order_code = ? AND user_id = ?');
$stmt->execute([(string) ($_POST['order'] ?? ''), $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'Order not found.');
    redirect('account.php');
}
if ($order['status'] !== 'pending') {
    flash('error', 'Only unpaid orders can be cancelled.');
    redirect('account.php');
}

// Pastikan belum dibayar di Midtrans, lalu batalkan transaksinya (jika sudah dibuat).
if (midtrans_is_configured()) {
    $live = midtrans_get_status($order['order_code']);
    if ($live && midtrans_map_status($live) === 'paid') {
        midtrans_apply_status($order, $live);
        flash('info', 'This order has already been paid, so it cannot be cancelled.');
        redirect('account.php');
    }
    if ($live) {
        midtrans_request('POST', midtrans_api_base() . '/v2/' . rawurlencode($order['order_code']) . '/cancel');
    }
}

$flag = db()->prepare("UPDATE orders SET status = 'cancelled', stock_restored = 1 WHERE id = ? AND status = 'pending' AND stock_restored = 0");
$flag->execute([$order['id']]);
if ($flag->rowCount() === 1) {
    $items = db()->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
    $items->execute([$order['id']]);
    $restock = db()->prepare('UPDATE products SET stock = stock + ?, sold = GREATEST(CAST(sold AS SIGNED) - ?, 0) WHERE id = ?');
    foreach ($items->fetchAll() as $it) {
        $restock->execute([$it['qty'], $it['qty'], $it['product_id']]);
    }
}
flash('success', 'Order ' . $order['order_code'] . ' has been cancelled.');
redirect('account.php');
