<?php
/**
 * api/tables.php
 * JSON API untuk CRUD data meja
 * Ordio.io
 *
 * GET  ?action=list          — daftar meja
 * POST action=save           — tambah/edit meja
 * POST action=delete         — hapus meja
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
    'list'   => handleList($db),
    'save'   => handleSave($db),
    'delete' => handleDelete($db),
    default  => apiFail('Unknown action')
};

function handleList(PDO $db): never {
    $rows = $db->query("SELECT * FROM tables ORDER BY table_number ASC")->fetchAll();
    apiOk(['tables' => $rows]);
}

function handleSave(PDO $db): never {
    $id           = (int)($_POST['id']            ?? 0);
    $tableNumber  = trim($_POST['table_number']   ?? '');
    $note         = trim($_POST['note']           ?? '');

    if ($tableNumber === '') apiFail('Nomor/nama meja wajib diisi.');

    try {
        if ($id) {
            $db->prepare("UPDATE tables SET table_number=?, note=? WHERE id=?")
               ->execute([$tableNumber, $note, $id]);
        } else {
            $db->prepare("INSERT INTO tables (table_number, note) VALUES (?,?)")
               ->execute([$tableNumber, $note]);
            $id = (int)$db->lastInsertId();
        }
        apiOk(['id' => $id]);
    } catch (Throwable $e) {
        apiFail('Nomor meja sudah ada.');
    }
}

function handleDelete(PDO $db): never {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');
    $db->prepare("DELETE FROM tables WHERE id=?")->execute([$id]);
    apiOk();
}
