<?php
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false], 405);
}
csrf_check();

$code = strtoupper(trim((string) ($_POST['code'] ?? '')));
$cart = cart_detailed();

if ($code === '') {
    unset($_SESSION['promo']);
    json_response(['ok' => true, 'discount' => 0, 'message' => 'Promo removed.']);
}

$promo = find_promo($code);
if (!$promo) {
    unset($_SESSION['promo']);
    json_response(['ok' => false, 'discount' => 0, 'message' => 'Promo code is not valid today.'], 422);
}
if ($cart['subtotal'] < (int) $promo['min_subtotal']) {
    unset($_SESSION['promo']);
    json_response([
        'ok' => false,
        'discount' => 0,
        'message' => 'Minimum spend for ' . $promo['code'] . ' is ' . rupiah((int) $promo['min_subtotal']) . '.',
    ], 422);
}

$_SESSION['promo'] = $promo['code'];
$discount = promo_discount($promo, $cart['subtotal']);
json_response(['ok' => true, 'discount' => $discount, 'message' => 'Promo ' . $promo['code'] . ' applied.']);