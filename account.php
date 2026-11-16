<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login('account.php');

// ---------------- Update profil / password
$settingsError = '';
if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $settingsError = 'Please enter your name (2-120 characters).';
        } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) {
            $settingsError = 'Please enter a valid phone number.';
        } else {
            db()->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone !== '' ? $phone : null, $user['id']]);
            flash('success', 'Your profile has been updated.');
            redirect('account.php');
        }
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($user['password_hash'] && !password_verify($current, $user['password_hash'])) {
            $settingsError = 'Your current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $settingsError = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $settingsError = 'New password and confirmation do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', $user['password_hash'] ? 'Your password has been changed.' : 'Password set! You can now log in with your email or username and this password.');
            redirect('account.php');
        }
    }
    $fresh = db()->prepare('SELECT * FROM users WHERE id = ?');
    $fresh->execute([$user['id']]);
    $user = $fresh->fetch() ?: $user;
}
$stmt = db()->prepare('SELECT o.*, (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) AS item_count FROM orders o WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Account — LZFJ Shoes Store';
$pageDescription = 'Track your orders and manage your LZFJ Shoes Store account.';
require __DIR__ . '/includes/header.php';
?>
<main class="container account-page">
    <div class="account-head">
        <div class="account-user">
            <?php if ($user['avatar_url']): ?>
                <img class="avatar-lg" src="<?= e($user['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
            <?php else: ?>
                <span class="avatar-lg avatar-initial"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
            <?php endif; ?>
            <div>
                <h1 class="page-title page-title-sm"><?= e($user['name']) ?></h1>
                <p class="muted"><?= e($user['email']) ?><?= $user['google_id'] ? ' · Google account' : '' ?></p>
            </div>
        </div>
        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline" type="submit"><?= icon('logout', 18) ?> Logout</button>
        </form>
    </div>

    <section class="panel">
        <h2 class="panel-title">My Orders</h2>
        <?php if (!$orders): ?>
            <p class="muted">You have no orders yet.</p>
            <a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Start Shopping</a>
        <?php else: ?>
            <div class="table-wrap">
                <table class="orders-table">
                    <thead><tr><th>Order number</th><th>Date</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= e($o['order_code']) ?></strong></td>
                            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
                            <td><?= (int) $o['item_count'] ?></td>
                            <td><?= e(payment_label($o['payment_method'], $o['payment_channel'])) ?></td>
                            <td><strong><?= e(rupiah((int) $o['total'])) ?></strong></td>
                            <td><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                            <td style="white-space:nowrap">
                                <a class="btn btn-outline btn-sm" href="<?= e(url('order-confirmation.php?order=' . urlencode($o['order_code']))) ?>">Details</a>
                                <form method="post" action="<?= e(url('buy-again.php')) ?>" style="display:inline">
                                    <?= csrf_field() ?><input type="hidden" name="order" value="<?= e($o['order_code']) ?>">
                                    <button class="btn btn-outline btn-sm" type="submit">Buy Again</button>
                                </form>
                                <?php if ($o['status'] === 'pending'): ?>
                                <form method="post" action="<?= e(url('cancel-order.php')) ?>" style="display:inline" onsubmit="return confirm('Cancel this unpaid order?');">
                                    <?= csrf_field() ?><input type="hidden" name="order" value="<?= e($o['order_code']) ?>">
                                    <button class="btn btn-outline btn-sm" type="submit">Cancel</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel" style="margin-top:24px">
        <h2 class="panel-title">Account Settings</h2>
        <?php if ($settingsError): ?><div class="alert" style="margin-top:0;margin-bottom:16px"><?= e($settingsError) ?></div><?php endif; ?>
        <form method="post" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <label class="field"><span>Full name</span>
                <input class="input" type="text" name="name" value="<?= e($user['name']) ?>" maxlength="120" required></label>
            <label class="field"><span>Phone number</span>
                <input class="input" type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="08xxxxxxxxxx" maxlength="30"></label>
            <label class="field"><span>Email</span>
                <input class="input" type="text" value="<?= e($user['email']) ?>" disabled></label>
            <label class="field"><span>Username (you can log in with it)</span>
                <input class="input" type="text" value="<?= e($user['username']) ?>" disabled></label>
            <div class="span-2"><button class="btn btn-dark" type="submit">Save Profile</button></div>
        </form>
        <hr style="border:0;border-top:1px solid var(--line);margin:28px 0">
        <h3 class="panel-title" style="font-size:16px"><?= $user['password_hash'] ? 'Change Password' : 'Set Password' ?></h3>
        <?php if (!$user['password_hash']): ?>
            <p class="muted small" style="margin-bottom:16px">You signed in with Google, so this account has no password yet. Set one to also log in with your email or username.</p>
        <?php endif; ?>
        <form method="post" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <?php if ($user['password_hash']): ?>
            <label class="field span-2"><span>Current password</span>
                <input class="input" type="password" name="current_password" autocomplete="current-password" required></label>
            <?php endif; ?>
            <label class="field"><span>New password</span>
                <input class="input" type="password" name="new_password" minlength="8" autocomplete="new-password" required></label>
            <label class="field"><span>Confirm new password</span>
                <input class="input" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
            <div class="span-2"><button class="btn btn-dark" type="submit"><?= $user['password_hash'] ? 'Change Password' : 'Set Password' ?></button></div>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
