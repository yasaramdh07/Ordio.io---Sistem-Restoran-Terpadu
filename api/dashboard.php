<?php
/**
 * api/dashboard.php
 * API endpoint untuk Dashboard Admin (Ringkasan Cepat)
 * Ordio.io
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin'); // Hanya admin

header('Content-Type: application/json; charset=utf-8');
$db = getDB();

function apiOk(array $data = []): never {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function apiFail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    // 1. Total penjualan hari ini
    $stmt = $db->query("
        SELECT SUM(amount) as today_sales 
        FROM financial_records 
        WHERE type = 'income' AND date(created_at) = date('now','localtime')
    ");
    $todaySales = (int)($stmt->fetchColumn() ?: 0);

    // 2. Jumlah pesanan aktif (menunggu & diproses)
    $stmt = $db->query("
        SELECT COUNT(id) 
        FROM orders 
        WHERE status IN ('menunggu', 'diproses')
    ");
    $activeOrders = (int)$stmt->fetchColumn();

    // 3. Stok bahan baku yang hampir habis
    $stmt = $db->query("
        SELECT id, name, stock_qty, unit, low_stock_threshold 
        FROM ingredients 
        WHERE stock_qty <= low_stock_threshold
        ORDER BY stock_qty ASC
        LIMIT 5
    ");
    $lowStock = $stmt->fetchAll();

    // 4. Data Penjualan 7 hari terakhir (untuk Chart.js)
    // Ambil 7 hari terakhir
    $chartLabels = [];
    $chartData = [];
    
    // SQLite tidak memiliki fungsi generate_series bawaan yang mudah, jadi kita loop dari PHP
    for ($i = 6; $i >= 0; $i--) {
        // Ambil label tanggal (format: DD MMM)
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $label = date('d M', strtotime($dateStr));
        $chartLabels[] = $label;

        $stmt = $db->prepare("
            SELECT SUM(amount) 
            FROM financial_records 
            WHERE type = 'income' AND date(created_at) = ?
        ");
        $stmt->execute([$dateStr]);
        $val = (int)$stmt->fetchColumn();
        $chartData[] = $val;
    }

    apiOk([
        'summary' => [
            'today_sales' => $todaySales,
            'active_orders' => $activeOrders
        ],
        'low_stock' => $lowStock,
        'chart' => [
            'labels' => $chartLabels,
            'data' => $chartData
        ]
    ]);
} catch (Exception $e) {
    apiFail($e->getMessage());
}
