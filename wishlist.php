<?php
require __DIR__ . '/includes/bootstrap.php';

$ids = wishlist_ids(true);
$products = [];
if ($ids) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }
    foreach ($ids as $id) { // urutan: terbaru ditambahkan dulu
        if (isset($byId[$id])) {
            $products[] = $byId[$id];
        }
    }
}
$total = count($products);
$me = current_user();

$cardLinksToShop = true; // tombol "View Details" membuka detail di halaman Shop
$pageTitle = 'My Wishlist — LZFJ Shoes Store';
$pageDescription = 'Shoes you saved to your wishlist.';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<main class="container shop-page">
    <nav class="breadcrumb" aria-label="Breadcrumb"><span>Wishlist</span></nav>

    <div class="shop-head">
        <h1 class="page-title">My Wishlist</h1>
        <p class="muted" data-wish-total><?= $total ?> <?= $total === 1 ? 'item' : 'items' ?></p>
    </div>

    <?php if (!$me && $total): ?>
        <p class="muted small" style="margin-top:12px">Your wishlist is saved on this browser. <a class="link" href="<?= e(url('login.php?redirect=wishlist.php')) ?>">Log in</a> to keep it in your account.</p>
    <?php endif; ?>

    <div class="empty-state" style="margin-top:32px" data-wish-empty <?= $total ? 'hidden' : '' ?>>
        <?= icon('heart', 40) ?>
        <p>Your wishlist is empty.</p>
        <p class="muted small">Tap the heart on any shoe to save it here.</p>
        <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
    </div>

    <div class="grid-4" style="margin-top:32px" data-wishlist-grid <?= $total ? '' : 'hidden' ?>>
        <?php foreach ($products as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
