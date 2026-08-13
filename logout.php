<?php
/**
 * logout.php
 * Endpoint logout — hancurkan session dan redirect ke login
 * Ordio.io
 */
require_once __DIR__ . '/includes/auth.php';
logout(); // fungsi ini sudah handle destroy + redirect
