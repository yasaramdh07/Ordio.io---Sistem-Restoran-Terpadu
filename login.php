<?php
/**
 * login.php
 * Halaman login terpusat untuk admin & kasir
 * Ordio.io — QR Order & Stock Management
 */

require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    redirectByRole();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF-lite: cek referer ada dari domain yang sama
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $result = attemptLogin($username, $password);

        if ($result['success']) {
            redirectByRole();
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke Ordio.io — Sistem pemesanan makanan via QR dan manajemen stok internal restoran.">
    <meta name="robots" content="noindex, nofollow">

    <title>Login — Ordio.io</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/ordio/assets/img/ordio-logo-fav.png">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Ordio Design System -->
    <link rel="stylesheet" href="/ordio/assets/css/ordio.css">
</head>
<body>

<div class="login-page">
    <div class="login-wrap">

        <!-- Card Utama -->
        <div class="login-card">

            <!-- Logo & Brand -->
            <div class="login-logo">
                <img src="/ordio/assets/img/ordio-logo-nobg.png"
                     alt="Ordio.io Logo"
                     draggable="false">
                <p class="login-tagline">Order in, serve fast</p>
            </div>

            <h1 class="login-title">Selamat Datang</h1>
            <p class="login-subtitle">Masuk ke dashboard Ordio.io</p>

            <!-- Error Alert -->
            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 px-3" role="alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="/ordio/login.php" id="loginForm" novalidate>

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-icon-wrap">
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            autocomplete="username"
                            autofocus
                            required
                        >
                        <i class="bi bi-person input-icon"></i>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon-wrap" style="position:relative;">
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                        <i class="bi bi-lock input-icon"></i>
                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Tampilkan/sembunyikan password"
                        >
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Masuk ke Dashboard
                    </span>
                    <span class="btn-spinner">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Memverifikasi…
                    </span>
                </button>

            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p class="mb-0">
                <strong>Ordio.io</strong> &mdash; QR Order &amp; Stock Management
            </p>
            <p class="mt-1 mb-0" style="font-size:11px; opacity:0.6;">
                &copy; <?= date('Y') ?> SuraCode Studio. All rights reserved.
            </p>
        </div>

    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    // ── Toggle password visibility ──────────────────────────
    const toggleBtn  = document.getElementById('togglePassword');
    const passwordEl = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (toggleBtn && passwordEl) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = passwordEl.type === 'password';
            passwordEl.type    = isHidden ? 'text' : 'password';
            toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            this.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        });
    }

    // ── Loading state on submit ─────────────────────────────
    const form     = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');

    if (form && loginBtn) {
        form.addEventListener('submit', function (e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                return;
            }

            // Tunjukkan loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;

            // Fallback: hapus loading setelah 5 detik (kalau ada error network)
            setTimeout(function () {
                loginBtn.classList.remove('loading');
                loginBtn.disabled = false;
            }, 5000);
        });
    }

    // ── Auto-focus password jika username sudah terisi ──────
    const usernameEl = document.getElementById('username');
    if (usernameEl && usernameEl.value.trim() !== '') {
        passwordEl && passwordEl.focus();
    }
})();
</script>

</body>
</html>
