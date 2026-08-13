<?php
/**
 * api/reports.php
 * Endpoint untuk men-generate HTML data Laporan
 * Ordio.io
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');
$db = getDB();

$type = $_GET['type'] ?? '';
$period = $_GET['period'] ?? 'today';

$where = "";
$periodLabel = "";
if ($period === 'today') {
    $where = "date(created_at) = date('now','localtime')";
    $periodLabel = "Hari Ini (" . date('d M Y') . ")";
} elseif ($period === 'week') {
    $where = "date(created_at) >= date('now','localtime', '-7 days')";
    $periodLabel = "7 Hari Terakhir";
} elseif ($period === 'month') {
    $where = "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now','localtime')";
    $periodLabel = "Bulan Ini (" . date('M Y') . ")";
} else {
    $where = "1=1";
    $periodLabel = "Semua Waktu";
}

function apiOk(array $data = []): never {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function apiFail(string $msg): never {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

try {
    if ($type === 'sales') {
        // Laporan Penjualan (Hanya order yang selesai)
        $whereOrder = str_replace('created_at', 'updated_at', $where);
        
        $stmt = $db->query("SELECT id, order_code, customer_name, total_price, updated_at FROM orders WHERE status = 'selesai' AND $whereOrder ORDER BY updated_at ASC");
        $orders = $stmt->fetchAll();
        
        $totalOmzet = 0;
        $html = '<table class="report-table">
                    <thead><tr><th>Waktu</th><th>Order ID</th><th>Pelanggan</th><th>Nominal</th></tr></thead>
                    <tbody>';
                    
        foreach ($orders as $o) {
            $totalOmzet += $o['total_price'];
            $time = date('d/m/Y H:i', strtotime($o['updated_at']));
            $html .= "<tr><td>{$time}</td><td>{$o['order_code']}</td><td>{$o['customer_name']}</td><td>Rp " . number_format($o['total_price'], 0, ',', '.') . "</td></tr>";
        }
        
        if(count($orders) === 0) $html .= '<tr><td colspan="4" style="text-align:center">Tidak ada transaksi penjualan.</td></tr>';
        
        $html .= '</tbody>
                  <tfoot><tr><th colspan="3" style="text-align:right">Total Pendapatan:</th><th>Rp '.number_format($totalOmzet,0,',','.').'</th></tr></tfoot>
                  </table>';
                  
        apiOk(['html' => $html, 'period' => $periodLabel, 'title' => 'Laporan Penjualan']);
        
    } elseif ($type === 'finance') {
        // Laporan Keuangan
        $stmt = $db->query("SELECT type, category, amount, description, created_at FROM financial_records WHERE $where ORDER BY created_at ASC");
        $records = $stmt->fetchAll();
        
        $in = 0; $out = 0;
        $html = '<table class="report-table">
                    <thead><tr><th>Tanggal</th><th>Tipe</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th></tr></thead>
                    <tbody>';
                    
        foreach ($records as $r) {
            $time = date('d/m/Y H:i', strtotime($r['created_at']));
            if($r['type']==='income'){
                $in += $r['amount'];
                $tipe = 'Pemasukan';
                $color = '#2e7d32';
            }else{
                $out += $r['amount'];
                $tipe = 'Pengeluaran';
                $color = '#c62828';
            }
            $html .= "<tr><td>{$time}</td><td style='color:{$color}'>{$tipe}</td><td>{$r['category']}</td><td>{$r['description']}</td><td style='color:{$color}'>Rp " . number_format($r['amount'], 0, ',', '.') . "</td></tr>";
        }
        
        if(count($records) === 0) $html .= '<tr><td colspan="5" style="text-align:center">Tidak ada catatan keuangan.</td></tr>';
        
        $laba = $in - $out;
        $html .= '</tbody>
                  </table>
                  <div style="margin-top:20px; text-align:right; font-weight:bold; font-size:14px;">
                     <div>Total Pemasukan: Rp '.number_format($in,0,',','.').'</div>
                     <div style="color:#c62828">Total Pengeluaran: Rp '.number_format($out,0,',','.').'</div>
                     <div style="margin-top:10px; font-size:16px;">Laba Bersih: Rp '.number_format($laba,0,',','.').'</div>
                  </div>';
                  
        apiOk(['html' => $html, 'period' => $periodLabel, 'title' => 'Laporan Keuangan']);
        
    } elseif ($type === 'stock') {
        // Laporan Pemakaian Stok
        $stmt = $db->query("
            SELECT s.ingredient_id, i.name, i.unit, SUM(s.change_qty) as total_used 
            FROM stock_logs s
            JOIN ingredients i ON i.id = s.ingredient_id
            WHERE s.type = 'out' AND " . str_replace('created_at','s.created_at',$where) . "
            GROUP BY s.ingredient_id
            ORDER BY total_used DESC
        ");
        $usage = $stmt->fetchAll();
        
        $html = '<table class="report-table">
                    <thead><tr><th>Bahan Baku</th><th>Satuan</th><th>Total Terpakai</th></tr></thead>
                    <tbody>';
                    
        foreach ($usage as $u) {
            $html .= "<tr><td>{$u['name']}</td><td>{$u['unit']}</td><td>{$u['total_used']}</td></tr>";
        }
        
        if(count($usage) === 0) $html .= '<tr><td colspan="3" style="text-align:center">Tidak ada pemakaian stok tercatat.</td></tr>';
        
        $html .= '</tbody></table>';
        apiOk(['html' => $html, 'period' => $periodLabel, 'title' => 'Laporan Pemakaian Bahan Baku']);
    } else {
        apiFail("Tipe laporan tidak valid.");
    }
} catch (Exception $e) {
    apiFail($e->getMessage());
}
