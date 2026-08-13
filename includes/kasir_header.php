<?php
/**
 * kasir_header.php
 * Header khusus Kasir dengan Top Navbar (Desktop) / Bottom Navbar (Mobile)
 * Ordio.io
 */

require_once __DIR__ . '/auth.php';
requireLogin('kasir'); // Pastikan cuma kasir yang akses, admin juga boleh kalau mau, tapi kita cek minimal login.

$user = currentUser();
// Jika admin mengakses ini, izinkan saja (kadang admin merangkap kasir)
if ($user['role'] !== 'admin' && $user['role'] !== 'kasir') {
    die("Akses ditolak.");
}

$pageTitle = $pageTitle ?? 'Kasir';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?> — Ordio.io</title>
    
    <link rel="icon" type="image/png" href="/ordio/assets/img/ordio-logo-fav.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="/ordio/assets/css/ordio.css">
    
    <style>
        body {
            background-color: var(--light);
            font-family: var(--font-body);
            color: var(--charcoal);
            padding-bottom: 70px; /* Space for mobile bottom nav */
        }
        
        /* ─── Top Navbar (Desktop) ─── */
        .kasir-top-nav {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }
        .kasir-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .kasir-brand img { height: 36px; }
        .kasir-brand span {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .kasir-nav-links {
            display: flex;
            gap: 1rem;
        }
        .kasir-nav-item {
            text-decoration: none;
            color: var(--muted);
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .kasir-nav-item:hover {
            color: var(--primary);
            background: var(--primary-light);
        }
        .kasir-nav-item.active {
            color: var(--primary);
            background: var(--primary-light);
            font-weight: 700;
        }
        
        .kasir-user-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--light);
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* ─── Bottom Navbar (Mobile) ─── */
        .kasir-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 1px solid var(--border);
            z-index: 1020;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.03);
            padding-bottom: env(safe-area-inset-bottom);
        }
        .bn-list {
            display: flex;
            list-style: none;
            margin: 0; padding: 0;
        }
        .bn-item {
            flex: 1;
            text-align: center;
        }
        .bn-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--muted);
            padding: 0.5rem 0;
            transition: color 0.2s;
            font-size: 11px;
            font-weight: 600;
        }
        .bn-link i {
            font-size: 1.25rem;
            margin-bottom: 2px;
            transition: transform 0.2s;
        }
        .bn-link.active {
            color: var(--primary);
        }
        .bn-link.active i {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .kasir-nav-links { display: none; }
            .kasir-bottom-nav { display: block; }
            body { padding-top: 0; }
        }
        @media (min-width: 769px) {
            body { padding-bottom: 0; }
        }
        
        .kasir-content {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<!-- Desktop Top Nav -->
<header class="kasir-top-nav">
    <a href="/ordio/kasir/dashboard.php" class="kasir-brand">
        <img src="/ordio/assets/img/ordio-logo-nobg.png" alt="Ordio">
        <span>Ordio.io</span>
    </a>
    
    <div class="kasir-nav-links">
        <a href="/ordio/kasir/dashboard.php" class="kasir-nav-item <?= $activePage === 'kasir-dashboard' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> Pesanan Masuk
        </a>
        <a href="/ordio/kasir/order-history.php" class="kasir-nav-item <?= $activePage === 'kasir-history' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i> Riwayat
        </a>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <div class="kasir-user-badge">
            <i class="bi bi-person-circle text-primary" style="font-size:1.2rem"></i>
            <?= htmlspecialchars($user['full_name']) ?>
        </div>
        <a href="/ordio/logout.php" class="btn btn-outline-danger btn-sm" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>

<!-- Mobile Bottom Nav -->
<nav class="kasir-bottom-nav">
    <ul class="bn-list">
        <li class="bn-item">
            <a href="/ordio/kasir/dashboard.php" class="bn-link <?= $activePage === 'kasir-dashboard' ? 'active' : '' ?>">
                <i class="bi bi-bell<?= $activePage === 'kasir-dashboard' ? '-fill' : '' ?>"></i>
                <span>Pesanan</span>
            </a>
        </li>
        <li class="bn-item">
            <a href="/ordio/kasir/order-history.php" class="bn-link <?= $activePage === 'kasir-history' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat</span>
            </a>
        </li>
        <li class="bn-item">
            <a href="/ordio/logout.php" class="bn-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<div class="kasir-content">
