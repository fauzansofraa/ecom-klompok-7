<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$cases = [
    ['below minimum percentage', ['type' => 'percent', 'value' => 10, 'min_subtotal' => 500000], 499999, 0],
    ['minimum percentage', ['type' => 'percent', 'value' => 10, 'min_subtotal' => 500000], 500000, 50000],
    ['fixed amount', ['type' => 'fixed', 'value' => 75000, 'min_subtotal' => 750000], 1000000, 75000],
    ['fixed discount capped at subtotal', ['type' => 'fixed', 'value' => 75000, 'min_subtotal' => 0], 50000, 50000],
    ['no promo', null, 1000000, 0],
];

foreach ($cases as [$name, $promo, $subtotal, $expected]) {
    $actual = promo_discount($promo, $subtotal);
    if ($actual !== $expected) {
        throw new RuntimeException($name . ': expected ' . $expected . ', got ' . $actual);
    }
}

echo count($cases) . " promo rule checks passed.\n";
