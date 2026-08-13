<?php
/**
 * api/ingredients.php
 * JSON API untuk CRUD bahan baku
 * Ordio.io
 *
 * GET  ?action=list[&search=X]    — daftar bahan baku
 * POST action=save                — buat/update bahan baku
 * POST action=delete&id=X        — hapus bahan baku
 * POST action=adjust_stock        — adjust stock (tambah/kurang qty)
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
    'list'          => handleList($db),
    'save'          => handleSave($db),
    'delete'        => handleDelete($db),
    'adjust_stock'  => handleAdjustStock($db),
    default         => apiFail('Unknown action')
};

function handleList(PDO $db): never {
    $search = trim($_GET['search'] ?? '');
    $sql    = "SELECT * FROM ingredients WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql    .= " AND name LIKE ?";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Tandai low stock
    foreach ($items as &$item) {
        $item['is_low_stock'] = (
            $item['low_stock_threshold'] > 0 &&
            $item['stock_qty'] <= $item['low_stock_threshold']
        ) ? 1 : 0;
    }
    unset($item);

    apiOk(['ingredients' => $items]);
}

function handleSave(PDO $db): never {
    $id        = (int)($_POST['id']                  ?? 0);
    $name      = trim($_POST['name']                  ?? '');
    $unit      = trim($_POST['unit']                  ?? 'pcs');
    $stockQty  = max(0, (float)($_POST['stock_qty']   ?? 0));
    $threshold = max(0, (float)($_POST['low_stock_threshold'] ?? 0));

    if ($name === '') apiFail('Nama bahan baku wajib diisi.');
    if ($unit === '') apiFail('Satuan wajib diisi.');

    try {
        if ($id) {
            $db->prepare("
                UPDATE ingredients
                SET name=?, unit=?, stock_qty=?, low_stock_threshold=?,
                    updated_at=datetime('now','localtime')
                WHERE id=?
            ")->execute([$name, $unit, $stockQty, $threshold, $id]);
        } else {
            $db->prepare("
                INSERT INTO ingredients (name, unit, stock_qty, low_stock_threshold)
                VALUES (?,?,?,?)
            ")->execute([$name, $unit, $stockQty, $threshold]);
            $id = (int)$db->lastInsertId();
        }
        apiOk(['id' => $id]);
    } catch (Throwable $e) {
        error_log('[Ordio API/ingredients save] ' . $e->getMessage());
        apiFail('Gagal menyimpan: ' . $e->getMessage());
    }
}

function handleDelete(PDO $db): never {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');

    // Cek apakah dipakai di resep
    $used = $db->prepare("SELECT COUNT(*) FROM menu_ingredients WHERE ingredient_id=?");
    $used->execute([$id]);
    if ($used->fetchColumn() > 0) {
        apiFail('Bahan ini masih dipakai di resep menu. Hapus terlebih dahulu dari semua menu.');
    }

    $db->prepare("DELETE FROM ingredients WHERE id=?")->execute([$id]);
    apiOk();
}

function handleAdjustStock(PDO $db): never {
    $id     = (int)($_POST['id']     ?? 0);
    $delta  = (float)($_POST['delta'] ?? 0);

    if (!$id) apiFail('ID tidak valid.');

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE ingredients
            SET stock_qty  = MAX(0, stock_qty + ?),
                updated_at = datetime('now','localtime')
            WHERE id=?
        ")->execute([$delta, $id]);

        // Catat ke stock_logs
        $type = $delta >= 0 ? 'in' : 'out';
        $absDelta = abs($delta);
        $userId = $_SESSION['user_id'] ?? 1;

        $db->prepare("
            INSERT INTO stock_logs (ingredient_id, change_qty, type, reference_order_id, created_by)
            VALUES (?, ?, ?, NULL, ?)
        ")->execute([$id, $absDelta, $type, $userId]);

        $db->commit();

        $stmt = $db->prepare("SELECT stock_qty, low_stock_threshold FROM ingredients WHERE id=?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();

        apiOk([
            'stock_qty'    => $row['stock_qty'],
            'is_low_stock' => ($row['low_stock_threshold'] > 0 && $row['stock_qty'] <= $row['low_stock_threshold']) ? 1 : 0,
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('[Ordio API/ingredients adjust_stock] ' . $e->getMessage());
        apiFail('Gagal menyesuaikan stok.');
    }
}
