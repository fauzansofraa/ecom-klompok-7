<?php
/** Endpoint AJAX wishlist: tambah / hapus (toggle) satu produk. */
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
}
csrf_check();

$product = find_product((int) ($_POST['product_id'] ?? 0));
if (!$product) {
    json_response(['ok' => false, 'message' => 'Product not found.'], 404);
}

$on = wishlist_toggle((int) $product['id']);
json_response([
    'ok'      => true,
    'on'      => $on,
    'count'   => wishlist_count(),
    'message' => $on ? 'Added to wishlist' : 'Removed from wishlist',
]);
