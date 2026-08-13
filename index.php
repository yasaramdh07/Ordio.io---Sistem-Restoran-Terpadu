<?php
/**
 * index.php
 * Entry point — redirect ke login atau dashboard sesuai session
 * Ordio.io
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectByRole();
} else {
    header('Location: /ordio/login.php');
    exit;
}
