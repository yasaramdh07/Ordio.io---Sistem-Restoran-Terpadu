<?php
/**
 * kasir/order-history.php
 * Riwayat Pesanan Hari Ini — Ordio.io
 */

$pageTitle  = 'Riwayat Pesanan';
$activePage = 'kasir-history';
require_once __DIR__ . '/../includes/kasir_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-family:var(--font-heading);font-weight:800;color:var(--dark);margin-bottom:0.25rem;">Riwayat Pesanan</h1>
        <p class="text-muted-brand mb-0" style="font-size:14px">Menampilkan pesanan yang sudah selesai atau ditolak hari ini.</p>
    </div>
    
    <div>
        <select class="form-select form-select-sm" id="statusFilter" style="border-radius:20px;font-weight:600;color:var(--charcoal)">
            <option value="all">Semua Status</option>
            <option value="selesai">Selesai</option>
            <option value="dibatalkan">Dibatalkan</option>
        </select>
    </div>
</div>

<div class="ing-table">
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Order ID</th>
                <th>Meja</th>
                <th>Nama Pelanggan</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="historyTbody">
            <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
        </tbody>
    </table>
    <div id="historyEmpty" class="empty-state" style="display:none">
        <i class="bi bi-inbox"></i>
        <p>Belum ada riwayat pesanan hari ini.</p>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function() {
'use strict';

let allHistory = [];

async function fetchHistory() {
    try {
        const filter = document.getElementById('statusFilter').value;
        const res = await fetch(`/ordio/api/kasir.php?action=history&status=${filter}`);
        const data = await res.json();
        if (data.ok) {
            allHistory = data.orders;
            renderHistory();
        }
    } catch(e) {
        showToast('Koneksi error saat mengambil riwayat', 'danger');
    }
}

function renderHistory() {
    const tbody = document.getElementById('historyTbody');
    const empty = document.getElementById('historyEmpty');
    
    tbody.innerHTML = '';
    
    if (allHistory.length === 0) {
        empty.style.display = '';
        return;
    }
    empty.style.display = 'none';
    
    allHistory.forEach(o => {
        const time = new Date(o.updated_at.replace(' ', 'T')).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        
        let statusBadge = '';
        if (o.status === 'selesai') {
            statusBadge = '<span class="badge text-bg-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>';
        } else if (o.status === 'dibatalkan') {
            statusBadge = '<span class="badge text-bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Batal</span>';
        }

        tbody.innerHTML += `
            <tr>
                <td style="color:var(--muted);font-size:12px;font-weight:600">${time}</td>
                <td style="font-family:var(--font-heading);font-weight:700">${escHtml(o.order_code)}</td>
                <td><span style="background:var(--light);padding:2px 8px;border-radius:4px;font-weight:700;font-size:12px">${escHtml(o.table_number)}</span></td>
                <td style="font-weight:600;color:var(--dark)">${escHtml(o.customer_name)}</td>
                <td style="font-family:var(--font-heading);font-weight:700;color:var(--primary)">${formatRupiah(o.total_price)}</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}

document.getElementById('statusFilter').addEventListener('change', fetchHistory);

// Init
fetchHistory();

})();
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/kasir_footer.php';
