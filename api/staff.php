<?php
/**
 * api/staff.php
 * JSON API untuk CRUD akun kasir (staff)
 * Ordio.io
 *
 * GET  ?action=list               — daftar semua kasir
 * POST action=save                — buat/update kasir
 * POST action=toggle              — toggle is_active (soft delete)
 * POST action=reset_password      — reset password kasir
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET'
    ? ($_GET['action']  ?? 'list')
    : ($_POST['action'] ?? '');

function apiOk(array $data = []): never {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function apiFail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

match ($action) {
    'list'           => handleList($db),
    'save'           => handleSave($db),
    'toggle'         => handleToggle($db),
    'reset_password' => handleResetPassword($db),
    default          => apiFail('Unknown action')
};

// ─── List ────────────────────────────────────────────────────
function handleList(PDO $db): never {
    $search = trim($_GET['search'] ?? '');
    $sql    = "SELECT id, username, full_name, is_active, created_at
               FROM users WHERE role = 'kasir'";
    $params = [];

    if ($search !== '') {
        $sql    .= " AND (username LIKE ? OR full_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY full_name ASC, username ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    apiOk(['staff' => $stmt->fetchAll()]);
}

// ─── Save (Create / Update) ───────────────────────────────────
function handleSave(PDO $db): never {
    $id       = (int)($_POST['id']        ?? 0);
    $username = trim($_POST['username']   ?? '');
    $fullName = trim($_POST['full_name']  ?? '');
    $password = $_POST['password']         ?? '';
    $isActive = (int)($_POST['is_active'] ?? 1);

    if ($username === '') apiFail('Username wajib diisi.');
    if ($fullName === '') apiFail('Nama lengkap wajib diisi.');

    // Validasi username: hanya alphanumeric + underscore
    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
        apiFail('Username hanya boleh huruf, angka, dan underscore (3–32 karakter).');
    }

    if ($id === 0) {
        // Create — password wajib
        if (strlen($password) < 6) {
            apiFail('Password minimal 6 karakter.');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $db->prepare("
                INSERT INTO users (username, password_hash, role, full_name, is_active)
                VALUES (?, ?, 'kasir', ?, ?)
            ")->execute([$username, $hash, $fullName, $isActive]);
            apiOk(['id' => (int)$db->lastInsertId()]);
        } catch (Throwable $e) {
            apiFail('Username sudah digunakan. Pilih username lain.');
        }
    } else {
        // Update — password opsional (skip jika kosong)
        // Pastikan tidak bisa edit akun admin lewat sini
        $existing = $db->prepare("SELECT role FROM users WHERE id=?")->execute([$id])
            ? $db->prepare("SELECT role FROM users WHERE id=?")
            : null;

        $stmt = $db->prepare("SELECT role, username FROM users WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) apiFail('Akun tidak ditemukan.', 404);
        if ($row['role'] !== 'kasir') apiFail('Hanya akun kasir yang bisa diedit di sini.');

        // Cek username konflik (kecuali username sendiri)
        $conflict = $db->prepare("SELECT id FROM users WHERE username=? AND id != ?");
        $conflict->execute([$username, $id]);
        if ($conflict->fetch()) apiFail('Username sudah digunakan. Pilih username lain.');

        if ($password !== '') {
            if (strlen($password) < 6) apiFail('Password minimal 6 karakter.');
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("
                UPDATE users SET username=?, full_name=?, password_hash=?, is_active=? WHERE id=?
            ")->execute([$username, $fullName, $hash, $isActive, $id]);
        } else {
            $db->prepare("
                UPDATE users SET username=?, full_name=?, is_active=? WHERE id=?
            ")->execute([$username, $fullName, $isActive, $id]);
        }

        apiOk(['id' => $id]);
    }
}

// ─── Toggle is_active ─────────────────────────────────────────
function handleToggle(PDO $db): never {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');

    $stmt = $db->prepare("SELECT role FROM users WHERE id=?");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
    if (!$row)              apiFail('Akun tidak ditemukan.', 404);
    if ($row['role'] !== 'kasir') apiFail('Hanya akun kasir yang bisa diubah status-nya.');

    $db->prepare("
        UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?
    ")->execute([$id]);

    $st = $db->prepare("SELECT is_active FROM users WHERE id=?");
    $st->execute([$id]);
    apiOk(['is_active' => (int)$st->fetch()['is_active']]);
}

// ─── Reset Password (admin override) ─────────────────────────
function handleResetPassword(PDO $db): never {
    $id       = (int)($_POST['id']       ?? 0);
    $password = $_POST['new_password']   ?? '';

    if (!$id)               apiFail('ID tidak valid.');
    if (strlen($password) < 6) apiFail('Password baru minimal 6 karakter.');

    $stmt = $db->prepare("SELECT role FROM users WHERE id=?");
    $stmt->execute([$id]);
    $row  = $stmt->fetch();
    if (!$row)              apiFail('Akun tidak ditemukan.', 404);
    if ($row['role'] !== 'kasir') apiFail('Hanya password kasir yang bisa direset di sini.');

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);

    apiOk();
}
