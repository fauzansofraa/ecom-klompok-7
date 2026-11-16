<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        db()->prepare('INSERT IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
        flash('success', 'Thanks for subscribing! Choose from 10 daily promo codes at checkout.');
    } else {
        flash('error', 'Please enter a valid email address.');
    }
}
redirect('home.php');
