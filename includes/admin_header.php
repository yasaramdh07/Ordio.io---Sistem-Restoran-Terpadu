<?php
/**
 * includes/admin_header.php
 * Shared admin sidebar + topbar layout — Ordio.io
 *
 * Requires these variables before include:
 *   $pageTitle  (string) — shown in topbar
 *   $activePage (string) — e.g. 'menu', 'ingredients', 'dashboard'
 */

require_once __DIR__ . '/auth.php';
requireLogin('admin');

$user = currentUser();

// Inisial avatar (max 2 huruf)
$initials = '';
$nameParts = explode(' ', trim($user['full_name']));
foreach (array_slice($nameParts, 0, 2) as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
}
if (!$initials) $initials = strtoupper(mb_substr($user['username'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — Ordio.io</title>
    <link rel="icon" type="image/png" href="/ordio/assets/img/ordio-logo-fav.png">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Ordio Design System -->
    <link rel="stylesheet" href="/ordio/assets/css/ordio.css">
    <!-- Admin Layout -->
    <link rel="stylesheet" href="/ordio/assets/css/admin.css">

    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="admin-body">

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ─── Sidebar ──────────────────────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">

    <a href="/ordio/admin/dashboard.php" class="sidebar-brand">
        <img src="/ordio/assets/img/ordio-logo-nobg.png" alt="Ordio" class="sidebar-logo">
        <span class="sidebar-brand-text">Ordio.io</span>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-label">Utama</span>
            <a href="/ordio/admin/dashboard.php"
               class="nav-item <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>"
               title="Dashboard">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">Manajemen</span>
            <a href="/ordio/admin/menu.php"
               class="nav-item <?= ($activePage ?? '') === 'menu' ? 'active' : '' ?>"
               title="Menu">
                <i class="bi bi-journal-richtext"></i>
                <span>Menu</span>
            </a>
            <a href="/ordio/admin/ingredients.php"
               class="nav-item <?= ($activePage ?? '') === 'ingredients' ? 'active' : '' ?>"
               title="Bahan Baku">
                <i class="bi bi-box-seam"></i>
                <span>Bahan Baku</span>
            </a>
            <a href="/ordio/admin/staff.php"
               class="nav-item <?= ($activePage ?? '') === 'staff' ? 'active' : '' ?>"
               title="Staff / Kasir">
                <i class="bi bi-people"></i>
                <span>Staff</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">Operasional</span>
            <a href="/ordio/admin/orders.php"
               class="nav-item <?= ($activePage ?? '') === 'orders' ? 'active' : '' ?>"
               title="Pesanan">
                <i class="bi bi-receipt"></i>
                <span>Pesanan</span>
            </a>
            <a href="/ordio/admin/reports.php"
               class="nav-item <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>"
               title="Laporan">
                <i class="bi bi-bar-chart-line"></i>
                <span>Laporan</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-label">Pengaturan</span>
            <a href="/ordio/admin/tables.php"
               class="nav-item <?= ($activePage ?? '') === 'tables' ? 'active' : '' ?>"
               title="Data Meja">
                <i class="bi bi-table"></i>
                <span>Meja</span>
            </a>
            <a href="/ordio/admin/qr-generator.php"
               class="nav-item <?= ($activePage ?? '') === 'qr' ? 'active' : '' ?>"
               title="QR Generator">
                <i class="bi bi-qr-code"></i>
                <span>QR Generator</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span>
            <span class="sidebar-user-role"><?= htmlspecialchars($user['role']) ?></span>
        </div>
        <a href="/ordio/logout.php" class="sidebar-logout-btn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>

<!-- ─── Mobile Bottom Navbar ─────────────────────────────────── -->
<nav class="admin-bottom-nav">
    <ul class="bn-list">
        <li class="bn-item">
            <a href="/ordio/admin/dashboard.php" class="bn-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2<?= ($activePage ?? '') === 'dashboard' ? '-fill' : '' ?>"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="bn-item">
            <a href="/ordio/admin/reports.php" class="bn-link <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line<?= ($activePage ?? '') === 'reports' ? '-fill' : '' ?>"></i>
                <span>Laporan</span>
            </a>
        </li>
        <li class="bn-item">
            <a href="/ordio/admin/finance.php" class="bn-link <?= ($activePage ?? '') === 'finance' ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i>
                <span>Keuangan</span>
            </a>
        </li>
        <li class="bn-item">
            <a href="#" class="bn-link" data-bs-toggle="modal" data-bs-target="#mobileMenuModal">
                <i class="bi bi-three-dots"></i>
                <span>Lainnya</span>
            </a>
        </li>
    </ul>
</nav>

<!-- ─── Mobile Bottom Sheet Modal ────────────────────────────── -->
<div class="modal fade bottom-sheet" id="mobileMenuModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-bottom">
    <div class="modal-content">
      <div class="modal-header border-bottom-0 pb-0 justify-content-center">
        <div style="width: 40px; height: 5px; background: var(--border); border-radius: 10px;"></div>
      </div>
      <div class="modal-body pb-5">
        <h6 class="font-heading mb-3 px-2" style="font-weight:700">Menu Lainnya</h6>
        <div class="list-group list-group-flush" style="border-radius: var(--radius-md); overflow:hidden;">
            <a href="/ordio/admin/menu.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3">
                <i class="bi bi-journal-richtext fs-5 text-muted"></i> <span style="font-weight:600">Kelola Menu</span>
            </a>
            <a href="/ordio/admin/ingredients.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3">
                <i class="bi bi-box-seam fs-5 text-muted"></i> <span style="font-weight:600">Bahan Baku</span>
            </a>
            <a href="/ordio/admin/staff.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3">
                <i class="bi bi-people fs-5 text-muted"></i> <span style="font-weight:600">Staff & Kasir</span>
            </a>
            <a href="/ordio/admin/tables.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3">
                <i class="bi bi-table fs-5 text-muted"></i> <span style="font-weight:600">Data Meja</span>
            </a>
            <a href="/ordio/admin/qr-generator.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3">
                <i class="bi bi-qr-code fs-5 text-muted"></i> <span style="font-weight:600">QR Generator</span>
            </a>
            <a href="/ordio/logout.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center gap-3 text-danger mt-2" style="border-top: 1px solid var(--border)">
                <i class="bi bi-box-arrow-right fs-5"></i> <span style="font-weight:600">Logout</span>
            </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ─── Main Wrapper ──────────────────────────────────────────── -->
<div class="admin-main" id="adminMain">

    <!-- Topbar -->
    <header class="admin-topbar">
        <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Admin') ?></div>
        <div class="topbar-actions">
            <?php if (!empty($topbarActions)) echo $topbarActions; ?>
        </div>
    </header>

    <!-- Content -->
    <div class="admin-content">
