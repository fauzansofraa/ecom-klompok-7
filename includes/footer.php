<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="footer-brand"><?= e(config('store.name')) ?></div>
            <p>JL. Syech Abdurrauf No.3, Kopelma Darussalam, Kec. Syiah Kuala, Kota Banda Aceh</p>
        </div>
        <div>
            <h4>Shop</h4>
            <a href="<?= e(url('men.php')) ?>">Men</a>
            <a href="<?= e(url('women.php')) ?>">Women</a>
            <a href="<?= e(url('kids.php')) ?>">Kids</a>
        </div>
        <div>
            <h4>Help</h4>
            <a href="https://wa.me/<?= e(config('store.whatsapp')) ?>" target="_blank" rel="noopener">Contact</a>
            <a href="<?= e(url('account.php')) ?>">Shipping</a>
        </div>
        <div>
            <h4>Follow</h4>
            <a href="https://www.instagram.com/fauzansofra/" target="_blank" rel="noopener">Instagram</a>
            <a href="https://www.tiktok.com/@fznsffra" target="_blank" rel="noopener">TikTok</a>
        </div>
    </div>
</footer>
<?php require APP_ROOT . '/includes/scripts.php'; ?>