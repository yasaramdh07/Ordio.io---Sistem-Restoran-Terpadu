<?php
/**
 * includes/auth.php
 * Fungsi autentikasi & session management
 * Ordio.io — QR Order & Stock Management
 */

require_once __DIR__ . '/db.php';

// ─── Session ────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // Sampai browser ditutup
        'path'     => '/',
        'secure'   => false,      // Set true jika pakai HTTPS
        'httponly' => true,       // Cegah akses JavaScript ke cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Login ──────────────────────────────────────────────────
/**
 * Coba login user.
 * @return array ['success' => bool, 'error' => string|null, 'user' => array|null]
 */
function attemptLogin(string $username, string $password): array {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, username, password_hash, role, full_name, is_active
        FROM users
        WHERE username = :username
        LIMIT 1
    ");
    $stmt->execute([':username' => trim($username)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Username atau password salah.', 'user' => null];
    }

    if (!(bool)$user['is_active']) {
        return ['success' => false, 'error' => 'Akun ini telah dinonaktifkan. Hubungi admin.', 'user' => null];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Username atau password salah.', 'user' => null];
    }

    // Regenerate session ID untuk mencegah session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();

    return ['success' => true, 'error' => null, 'user' => $user];
}

// ─── Logout ─────────────────────────────────────────────────
function logout(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: /ordio/login.php');
    exit;
}

// ─── Guard helpers ──────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role']      ?? '',
    ];
}

/**
 * Guard: redirect ke login jika belum login.
 * Opsional: batasi ke role tertentu.
 */
function requireLogin(string $requiredRole = ''): void {
    if (!isLoggedIn()) {
        header('Location: /ordio/login.php');
        exit;
    }

    if ($requiredRole !== '' && currentRole() !== $requiredRole) {
        // Redirect ke halaman yang sesuai role mereka
        redirectByRole();
    }
}

/**
 * Redirect ke dashboard sesuai role.
 */
function redirectByRole(): void {
    $role = currentRole();
    if ($role === 'admin') {
        header('Location: /ordio/admin/dashboard.php');
    } elseif ($role === 'kasir') {
        header('Location: /ordio/kasir/dashboard.php');
    } else {
        header('Location: /ordio/login.php');
    }
    exit;
}
