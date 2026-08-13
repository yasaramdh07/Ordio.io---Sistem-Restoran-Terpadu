<?php
/**
 * api/order.php
 * Endpoint untuk halaman pesanan pelanggan (tanpa auth login)
 * Ordio.io
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET'
    ? ($_GET['action']  ?? '')
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
    'catalog' => handleCatalog($db),
    'submit'  => handleSubmit($db),
    'status'  => handleStatus($db),
    default   => apiFail('Unknown action')
};

// ─── GET Catalog ─────────────────────────────────────────────
function handleCatalog(PDO $db): never {
    // 1. Get Categories
    $categories = $db->query("SELECT id, name FROM menu_categories ORDER BY name ASC")->fetchAll();

    // 2. Get Active Menus
    $menusQuery = $db->query("
        SELECT id, category_id, name, price, description, image_path 
        FROM menus 
        WHERE is_active = 1 
        ORDER BY name ASC
    ");
    $menus = $menusQuery->fetchAll();

    // 3. Get Options for these menus
    $optionsMap = [];
    if (count($menus) > 0) {
        $opts = $db->query("
            SELECT mo.id AS option_id, mo.menu_id, mo.option_name, mo.is_required,
                   mov.id AS value_id, mov.value_name, mov.extra_price
            FROM menu_options mo
            LEFT JOIN menu_option_values mov ON mov.option_id = mo.id
            ORDER BY mo.id, mov.id
        ");
        
        foreach ($opts->fetchAll() as $row) {
            $mid = $row['menu_id'];
            $oid = $row['option_id'];
            
            if (!isset($optionsMap[$mid])) $optionsMap[$mid] = [];
            
            if (!isset($optionsMap[$mid][$oid])) {
                $optionsMap[$mid][$oid] = [
                    'id'          => $oid,
                    'option_name' => $row['option_name'],
                    'is_required' => $row['is_required'],
                    'values'      => [],
                ];
            }
            if ($row['value_id']) {
                $optionsMap[$mid][$oid]['values'][] = [
                    'id'          => $row['value_id'],
                    'value_name'  => $row['value_name'],
                    'extra_price' => $row['extra_price'],
                ];
            }
        }
    }

    // Attach options to menus
    foreach ($menus as &$menu) {
        $mid = $menu['id'];
        $menu['options'] = isset($optionsMap[$mid]) ? array_values($optionsMap[$mid]) : [];
    }
    unset($menu);

    apiOk([
        'categories' => $categories,
        'menus'      => $menus
    ]);
}

// ─── POST Submit Order ────────────────────────────────────────
function handleSubmit(PDO $db): never {
    // Ambil input JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) apiFail('Invalid JSON payload');

    $customerName = trim($data['customer_name'] ?? '');
    $tableNumber  = trim($data['table_number'] ?? '');
    $cart         = $data['cart'] ?? [];

    if ($customerName === '') apiFail('Nama wajib diisi.');
    if ($tableNumber === '')  apiFail('Nomor meja wajib diisi.');
    if (empty($cart))         apiFail('Keranjang kosong.');

    // Validasi apakah meja valid? (Berdasarkan PRD meja dari referensi admin)
    // Walaupun PRD bilang pelanggan ngetik manual, kita bisa cek atau bebas. Kita bebaskan saja sesuai brief.

    // Generate Order Code (misal: ORD-YYMMDD-XXXX)
    $today = date('ymd');
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE date(created_at) = date('now','localtime')");
    $countToday = $stmt->fetchColumn();
    $seq = str_pad($countToday + 1, 4, '0', STR_PAD_LEFT);
    $orderCode = "ORD-{$today}-{$seq}";

    try {
        $db->beginTransaction();

        // 1. Insert Order (status = menunggu)
        $stmtOrder = $db->prepare("
            INSERT INTO orders (order_code, customer_name, table_number, status, total_price)
            VALUES (?, ?, ?, 'menunggu', 0)
        ");
        $stmtOrder->execute([$orderCode, $customerName, $tableNumber]);
        $orderId = (int)$db->lastInsertId();

        $grandTotal = 0;

        // 2. Insert Items & Options
        $stmtItem = $db->prepare("
            INSERT INTO order_items (order_id, menu_id, qty, price_at_order, note)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtOpt = $db->prepare("
            INSERT INTO order_item_options (order_item_id, option_value_id)
            VALUES (?, ?)
        ");

        foreach ($cart as $item) {
            $menuId = (int)($item['menu_id'] ?? 0);
            $qty    = (int)($item['qty'] ?? 1);
            $note   = trim($item['note'] ?? '');
            $opts   = $item['options'] ?? []; // Array of option_value_id

            if ($qty < 1) continue;

            // Dapatkan harga menu
            $menuQuery = $db->prepare("SELECT price FROM menus WHERE id = ? AND is_active = 1");
            $menuQuery->execute([$menuId]);
            $menuRow = $menuQuery->fetch();
            if (!$menuRow) throw new Exception("Menu ID $menuId tidak valid atau nonaktif.");
            
            $basePrice = $menuRow['price'];
            $itemTotal = $basePrice;

            // Proses opsi dan tambah harga
            $optPrices = 0;
            if (!empty($opts)) {
                $placeholders = implode(',', array_fill(0, count($opts), '?'));
                $optQuery = $db->prepare("SELECT id, extra_price FROM menu_option_values WHERE id IN ($placeholders)");
                $optQuery->execute($opts);
                $optRows = $optQuery->fetchAll();
                
                foreach ($optRows as $or) {
                    $optPrices += $or['extra_price'];
                }
            }

            $priceAtOrder = $basePrice + $optPrices;
            $subtotal = $priceAtOrder * $qty;
            $grandTotal += $subtotal;

            $stmtItem->execute([$orderId, $menuId, $qty, $priceAtOrder, $note]);
            $orderItemId = (int)$db->lastInsertId();

            // Insert mapping options
            foreach ($opts as $optValId) {
                $stmtOpt->execute([$orderItemId, $optValId]);
            }
        }

        // 3. Update total_price di order
        $db->prepare("UPDATE orders SET total_price = ? WHERE id = ?")->execute([$grandTotal, $orderId]);

        $db->commit();
        apiOk(['order_code' => $orderCode]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("[Ordio Submit] " . $e->getMessage());
        apiFail('Gagal memproses pesanan: ' . $e->getMessage());
    }
}

// ─── GET Status ───────────────────────────────────────────────
function handleStatus(PDO $db): never {
    $code = trim($_GET['code'] ?? '');
    if (!$code) apiFail('Order code diperlukan.');

    $stmt = $db->prepare("
        SELECT id, order_code, customer_name, table_number, status, total_price, payment_status, created_at 
        FROM orders 
        WHERE order_code = ?
    ");
    $stmt->execute([$code]);
    $order = $stmt->fetch();

    if (!$order) apiFail('Pesanan tidak ditemukan.', 404);

    // Ambil items
    $stmtItems = $db->prepare("
        SELECT oi.id, oi.qty, oi.price_at_order, oi.note, m.name AS menu_name
        FROM order_items oi
        JOIN menus m ON m.id = oi.menu_id
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$order['id']]);
    $items = $stmtItems->fetchAll();

    foreach ($items as &$it) {
        // Ambil opsi yang dipilih
        $stOpt = $db->prepare("
            SELECT mov.value_name, mo.option_name
            FROM order_item_options oio
            JOIN menu_option_values mov ON mov.id = oio.option_value_id
            JOIN menu_options mo ON mo.id = mov.option_id
            WHERE oio.order_item_id = ?
        ");
        $stOpt->execute([$it['id']]);
        $it['options'] = $stOpt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($it);

    $order['items'] = $items;
    apiOk(['order' => $order]);
}
