/* LZFJ Shoes Store — script umum */
(function () {
    'use strict';

    window.lzfjToast = function (message, type, linkHref, linkText) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        var t = document.createElement('div');
        t.className = 'toast toast-' + (type || 'success');
        t.textContent = message;
        if (linkHref) {
            var a = document.createElement('a');
            a.href = linkHref;
            a.textContent = linkText || 'View';
            t.appendChild(a);
        }
        stack.appendChild(t);
        setTimeout(function () { t.remove(); }, 4500);
    };

    window.lzfjPost = function (path, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
        body.append('csrf', window.LZFJ.csrf);
        return fetch(window.LZFJ.baseUrl + '/' + path, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, message: 'Unexpected server response (HTTP ' + r.status + ').' }; });
        });
    };

    // Toast dari server hilang otomatis
    document.querySelectorAll('[data-auto-hide] .toast').forEach(function (t) {
        setTimeout(function () { t.remove(); }, 5000);
    });

    // Tombol search di header
    var searchToggle = document.querySelector('[data-search-toggle]');
    var drawer = document.querySelector('[data-search-drawer]');
    if (searchToggle && drawer) {
        searchToggle.addEventListener('click', function () {
            drawer.hidden = !drawer.hidden;
            if (!drawer.hidden) drawer.querySelector('input').focus();
        });
    }

    // Show / Hide password
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });

    // Wishlist (disimpan di server: akun jika login, session jika tamu)
    function setWishCount(n) {
        document.querySelectorAll('[data-wish-count]').forEach(function (el) {
            el.textContent = n;
            el.hidden = !n;
        });
        document.querySelectorAll('[data-wish-total]').forEach(function (el) {
            el.textContent = n + (n === 1 ? ' item' : ' items');
        });
    }
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-wish]');
        if (!b) return;
        ev.preventDefault();
        ev.stopPropagation();
        if (b.disabled) return;
        b.disabled = true;
        window.lzfjPost('wishlist-action.php', { product_id: b.getAttribute('data-wish') })
            .then(function (res) {
                if (!res.ok) {
                    window.lzfjToast(res.message || 'Could not update wishlist.', 'error');
                    return;
                }
                b.classList.toggle('is-on', !!res.on);
                b.title = res.on ? 'Remove from wishlist' : 'Add to wishlist';
                b.setAttribute('aria-label', b.title);
                b.setAttribute('aria-pressed', res.on ? 'true' : 'false');
                setWishCount(res.count);
                if (res.on) {
                    window.lzfjToast('Added to wishlist', 'success', window.LZFJ.baseUrl + '/wishlist.php', 'View wishlist');
                } else {
                    window.lzfjToast('Removed from wishlist', 'info');
                    // Di halaman Wishlist: kartu langsung hilang
                    var grid = b.closest('[data-wishlist-grid]');
                    if (grid) {
                        var card = b.closest('.product-card');
                        if (card) card.remove();
                        if (!grid.querySelector('.product-card')) {
                            grid.hidden = true;
                            var empty = document.querySelector('[data-wish-empty]');
                            if (empty) empty.hidden = false;
                        }
                    }
                }
            })
            .catch(function () { window.lzfjToast('Network error, please try again.', 'error'); })
            .finally(function () { b.disabled = false; });
    });
})();
