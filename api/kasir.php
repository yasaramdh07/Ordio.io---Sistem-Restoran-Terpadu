<?php
/**
 * api/kasir.php
 * API endpoint untuk Dashboard Kasir dan Order History
 * Ordio.io
 */

require_once __DIR__ . '/../includes/auth.php';
// Hanya role admin dan kasir yang boleh akses
if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'kasir'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET'
    ? ($_GET['action']  ?? '')
    : ($_POST['action'] ?? '');

$userId = $_SESSION['user_id'];

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
    'active_orders'  => handleActiveOrders($db),
    'history'        => handleHistory($db),
    'process_order'  => handleProcessOrder($db, $userId),
    'complete_order' => handleCompleteOrder($db),
    'cancel_order'   => handleCancelOrder($db),
    default          => apiFail('Unknown action')
};

// ─── GET: Ambil order aktif (menunggu / diproses) ─────────────
function handleActiveOrders(PDO $db): never {
    $stmt = $db->query("
        SELECT id, order_code, customer_name, table_number, status, total_price, created_at
        FROM orders
        WHERE status IN ('menunggu', 'diproses')
        ORDER BY created_at ASC
    ");
    $orders = $stmt->fetchAll();

    if ($orders) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        
        $itemsQuery = $db->prepare("
            SELECT oi.id as item_id, oi.order_id, oi.qty, oi.note, m.name as menu_name
            FROM order_items oi
            JOIN menus m ON m.id = oi.menu_id
            WHERE oi.order_id IN ($placeholders)
        ");
        $itemsQuery->execute($orderIds);
        $allItems = $itemsQuery->fetchAll();

        // Get options for items
        $itemIds = array_column($allItems, 'item_id');
        $allOpts = [];
        if (!empty($itemIds)) {
            $optPlaceholders = implode(',', array_fill(0, count($itemIds), '?'));
            $optQuery = $db->prepare("
                SELECT oio.order_item_id, mov.value_name
                FROM order_item_options oio
                JOIN menu_option_values mov ON mov.id = oio.option_value_id
                WHERE oio.order_item_id IN ($optPlaceholders)
            ");
            $optQuery->execute($itemIds);
            $optsRows = $optQuery->fetchAll();
            
            foreach ($optsRows as $or) {
                $itId = $or['order_item_id'];
                if (!isset($allOpts[$itId])) $allOpts[$itId] = [];
                $allOpts[$itId][] = $or['value_name'];
            }
        }

        // Group items by order
        $itemsByOrder = [];
        foreach ($allItems as $it) {
            $oid = $it['order_id'];
            $iid = $it['item_id'];
            $it['options'] = isset($allOpts[$iid]) ? implode(', ', $allOpts[$iid]) : '';
            if (!isset($itemsByOrder[$oid])) $itemsByOrder[$oid] = [];
            $itemsByOrder[$oid][] = $it;
        }

        foreach ($orders as &$ord) {
            $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
        }
        unset($ord);
    }

    apiOk(['orders' => $orders]);
}

// ─── GET: History Order Hari Ini ──────────────────────────────
function handleHistory(PDO $db): never {
    $status = $_GET['status'] ?? 'all';
    $where = "date(created_at) = date('now','localtime') AND status IN ('selesai', 'dibatalkan')";
    $params = [];
    
    if ($status === 'selesai' || $status === 'dibatalkan') {
        $where .= " AND status = ?";
        $params[] = $status;
    }

    $stmt = $db->prepare("
        SELECT id, order_code, customer_name, table_number, status, total_price, created_at, updated_at
        FROM orders
        WHERE $where
        ORDER BY updated_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    apiOk(['orders' => $orders]);
}

// ─── POST: Terima & Proses Pesanan (Deduct Stock) ─────────────
function handleProcessOrder(PDO $db, int $userId): never {
    $orderId = (int)($_POST['id'] ?? 0);
    if (!$orderId) apiFail('Order ID tidak valid.');

    try {
        $db->beginTransaction();

        $stmtOrder = $db->prepare("SELECT status, order_code FROM orders WHERE id = ?");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch();

        if (!$order) throw new Exception("Pesanan tidak ditemukan.");
        if ($order['status'] !== 'menunggu') throw new Exception("Pesanan sudah diproses atau selesai.");

        // Dapatkan semua item di pesanan ini
        $stmtItems = $db->prepare("SELECT menu_id, qty FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $items = $stmtItems->fetchAll();

        $warnings = [];

        // Hitung total ingredient yang butuh dikurangi berdasarkan resep
        $ingredientDeductions = []; // [ingredient_id => total_qty_needed]
        
        $stmtRecipe = $db->prepare("SELECT ingredient_id, qty_used FROM menu_ingredients WHERE menu_id = ?");
        
        foreach ($items as $item) {
            $stmtRecipe->execute([$item['menu_id']]);
            $recipes = $stmtRecipe->fetchAll();
            
            foreach ($recipes as $r) {
                $ingId = $r['ingredient_id'];
                $qtyNeeded = $r['qty_used'] * $item['qty'];
                
                if (!isset($ingredientDeductions[$ingId])) {
                    $ingredientDeductions[$ingId] = 0;
                }
                $ingredientDeductions[$ingId] += $qtyNeeded;
            }
        }

        // Cek stok dan lakukan deduksi
        $stmtCheckStock = $db->prepare("SELECT name, stock_qty FROM ingredients WHERE id = ?");
        $stmtUpdateStock = $db->prepare("UPDATE ingredients SET stock_qty = stock_qty - ? WHERE id = ?");
        $stmtLogStock = $db->prepare("
            INSERT INTO stock_logs (ingredient_id, change_qty, type, reference_order_id, created_by)
            VALUES (?, ?, 'out', ?, ?)
        ");

        foreach ($ingredientDeductions as $ingId => $totalNeeded) {
            $stmtCheckStock->execute([$ingId]);
            $ing = $stmtCheckStock->fetch();
            
            if ($ing) {
                if ($ing['stock_qty'] < $totalNeeded) {
                    $warnings[] = "Stok {$ing['name']} kurang! Sisa: {$ing['stock_qty']}, butuh: $totalNeeded.";
                }
                
                // Tetap kurangi (bisa minus)
                $stmtUpdateStock->execute([$totalNeeded, $ingId]);
                
                // Log
                $stmtLogStock->execute([$ingId, $totalNeeded, $orderId, $userId]);
            }
        }

        // Update status order
        $db->prepare("UPDATE orders SET status = 'diproses', updated_at = datetime('now','localtime') WHERE id = ?")
           ->execute([$orderId]);

        $db->commit();
        
        $res = ['msg' => 'Pesanan berhasil diproses.'];
        if (count($warnings) > 0) {
            $res['warning'] = implode(" ", $warnings);
        }
        
        apiOk($res);

    } catch (Exception $e) {
        $db->rollBack();
        apiFail($e->getMessage());
    }
}

// ─── POST: Pesanan Selesai ────────────────────────────────────
function handleCompleteOrder(PDO $db): never {
    $orderId = (int)($_POST['id'] ?? 0);
    if (!$orderId) apiFail('Order ID tidak valid.');

    $userId = $_SESSION['user_id'];

    try {
        $db->beginTransaction();
        
        // Cek order
        $stmtOrder = $db->prepare("SELECT order_code, total_price, status FROM orders WHERE id = ?");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch();

        if (!$order || $order['status'] !== 'diproses') {
            throw new Exception("Pesanan tidak dapat diselesaikan.");
        }

        // Update status order
        $db->prepare("UPDATE orders SET status = 'selesai', updated_at = datetime('now','localtime') WHERE id = ?")
           ->execute([$orderId]);

        // Insert ke financial_records sebagai income
        $db->prepare("
            INSERT INTO financial_records (type, category, amount, description, reference_order_id, created_by)
            VALUES ('income', 'Penjualan', ?, ?, ?, ?)
        ")->execute([
            $order['total_price'],
            "Penjualan via Order " . $order['order_code'],
            $orderId,
            $userId
        ]);

        $db->commit();
        apiOk(['msg' => 'Pesanan telah selesai dan dicatat sebagai pemasukan.']);
    } catch (Exception $e) {
        $db->rollBack();
        apiFail($e->getMessage());
    }
}

// ─── POST: Batalkan Pesanan ───────────────────────────────────
function handleCancelOrder(PDO $db): never {
    $orderId = (int)($_POST['id'] ?? 0);
    if (!$orderId) apiFail('Order ID tidak valid.');

    // Asumsi: pembatalan tidak mengembalikan stok (sesuai PRD: kalau udah diproses, bahan udah kepake)
    // Walaupun dibatalkan saat status 'menunggu', stok memang belum dipotong.
    $db->prepare("UPDATE orders SET status = 'dibatalkan', updated_at = datetime('now','localtime') WHERE id = ?")
       ->execute([$orderId]);

    apiOk(['msg' => 'Pesanan dibatalkan.']);
}
