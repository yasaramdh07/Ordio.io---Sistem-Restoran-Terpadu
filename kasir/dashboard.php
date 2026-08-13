<?php
/**
 * kasir/dashboard.php
 * Dashboard Kasir — Ordio.io
 */

$pageTitle  = 'Dashboard Kasir';
$activePage = 'kasir-dashboard';
require_once __DIR__ . '/../includes/kasir_header.php';
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-family:var(--font-heading);font-weight:800;color:var(--dark);margin-bottom:0.25rem;">Pesanan Aktif</h1>
        <p class="text-muted-brand mb-0" style="font-size:14px">Pantau pesanan pelanggan secara real-time</p>
    </div>
    <div id="liveIndicator" class="d-flex align-items-center gap-2 px-3 py-2" style="background:#e8f5e9;color:#2e7d32;border-radius:20px;font-size:12px;font-weight:700">
        <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true" style="width:10px;height:10px"></span>
        <span class="d-none d-sm-inline">LIVE POLLING</span>
    </div>
</div>

<!-- ─── Kanban Board ─────────────────────────────────────────── -->
<div class="row g-4 h-100 pb-5">
    
    <!-- Kolom: Menunggu -->
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h5 class="m-0 font-heading" style="font-weight:700;color:var(--dark)">Menunggu Konfirmasi</h5>
            <span class="badge text-bg-warning rounded-pill" id="countMenunggu">0</span>
        </div>
        <div class="p-2 p-md-3" style="background:#f1f5f9; border-radius:var(--radius-lg); min-height:60vh; border:1px solid #e2e8f0" id="colMenunggu">
            <div class="text-center text-muted py-5" style="font-size:13px">Loading...</div>
        </div>
    </div>
    
    <!-- Kolom: Diproses -->
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h5 class="m-0 font-heading" style="font-weight:700;color:var(--dark)">Sedang Diproses</h5>
            <span class="badge text-bg-primary rounded-pill" id="countDiproses">0</span>
        </div>
        <div class="p-2 p-md-3" style="background:#f1f5f9; border-radius:var(--radius-lg); min-height:60vh; border:1px solid #e2e8f0" id="colDiproses">
            <div class="text-center text-muted py-5" style="font-size:13px">Loading...</div>
        </div>
    </div>
    
</div>

<style>
.order-card {
    background: #fff;
    border-radius: var(--radius-md);
    padding: 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    transition: transform 0.2s, box-shadow 0.2s;
    animation: fadeIn 0.3s ease-out forwards;
}
.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.06);
}
.card-table-badge {
    background: var(--primary-light);
    color: var(--primary);
    font-family: var(--font-heading);
    font-size: 1.5rem;
    font-weight: 800;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    flex-shrink: 0;
}
.card-item-row {
    font-size: 13px;
    padding: 0.5rem 0;
    border-bottom: 1px dashed var(--border);
}
.card-item-row:last-child {
    border-bottom: none;
}
.card-item-opts {
    font-size: 11px;
    color: var(--muted);
    margin-top: 2px;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function() {
'use strict';

let lastDataStr = '';
let pollingTimer = null;

async function fetchOrders() {
    try {
        const res = await fetch('/ordio/api/kasir.php?action=active_orders');
        const data = await res.json();
        
        if (data.ok) {
            const newDataStr = JSON.stringify(data.orders);
            if (newDataStr !== lastDataStr) {
                renderOrders(data.orders);
                lastDataStr = newDataStr;
            }
        }
    } catch(e) {
        console.error("Polling error:", e);
    }
    
    // Poll again
    pollingTimer = setTimeout(fetchOrders, 4000);
}

function renderOrders(orders) {
    const colMenunggu = document.getElementById('colMenunggu');
    const colDiproses = document.getElementById('colDiproses');
    
    let htmlMenunggu = '';
    let htmlDiproses = '';
    let countMenunggu = 0;
    let countDiproses = 0;
    
    orders.forEach(o => {
        let itemsHtml = '';
        o.items.forEach(it => {
            let metaHtml = '';
            if (it.options) metaHtml += `<div>Varian: ${escHtml(it.options)}</div>`;
            if (it.note) metaHtml += `<div>Note: ${escHtml(it.note)}</div>`;
            
            itemsHtml += `
                <div class="card-item-row d-flex gap-2">
                    <div style="font-weight:800;color:var(--primary);width:24px;text-align:right">${it.qty}x</div>
                    <div style="flex-grow:1">
                        <div style="font-weight:700;color:var(--dark)">${escHtml(it.menu_name)}</div>
                        ${metaHtml ? `<div class="card-item-opts">${metaHtml}</div>` : ''}
                    </div>
                </div>
            `;
        });

        const time = new Date(o.created_at.replace(' ', 'T')).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        
        let actions = '';
        if (o.status === 'menunggu') {
            actions = `
                <div class="d-flex gap-2 mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <button class="btn btn-outline-danger btn-sm" style="flex:1;font-weight:600" id="btnCancel_${o.id}" onclick="cancelOrder(${o.id}, '${o.order_code}')">
                        <span class="btn-text">Tolak</span>
                        <span class="spinner-border spinner-border-sm d-none btn-spinner"></span>
                    </button>
                    <button class="btn btn-primary btn-sm" style="flex:2;font-weight:600" id="btnProcess_${o.id}" onclick="processOrder(${o.id}, '${o.order_code}')">
                        <span class="btn-text"><i class="bi bi-fire me-1"></i> Proses Pesanan</span>
                        <span class="spinner-border spinner-border-sm d-none btn-spinner"></span>
                    </button>
                </div>
            `;
        } else if (o.status === 'diproses') {
            actions = `
                <div class="d-flex gap-2 mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <button class="btn btn-success btn-sm w-100" style="font-weight:600;font-size:14px" id="btnComplete_${o.id}" onclick="completeOrder(${o.id}, '${o.order_code}')">
                        <span class="btn-text"><i class="bi bi-check2-all me-1"></i> Selesai & Antarkan</span>
                        <span class="spinner-border spinner-border-sm d-none btn-spinner"></span>
                    </button>
                </div>
            `;
        }

        const card = `
            <div class="order-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-table-badge" title="Meja ${escHtml(o.table_number)}">${escHtml(o.table_number.substring(0,3))}</div>
                        <div>
                            <div style="font-size:11px;color:var(--muted);font-weight:700;letter-spacing:0.5px">${escHtml(o.order_code)} • ${time}</div>
                            <div style="font-family:var(--font-heading);font-size:1.15rem;font-weight:800;color:var(--charcoal)">
                                ${escHtml(o.customer_name)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="background:#f8fafc;border-radius:8px;padding:0.5rem 0.75rem;margin-bottom:0.75rem">
                    ${itemsHtml}
                </div>
                
                <div class="d-flex justify-content-between align-items-center px-1">
                    <span style="font-size:12px;color:var(--muted);font-weight:600">Total Bill</span>
                    <span style="font-family:var(--font-heading);font-weight:800;color:var(--primary);font-size:1.1rem">${formatRupiah(o.total_price)}</span>
                </div>
                
                ${actions}
            </div>
        `;

        if (o.status === 'menunggu') {
            htmlMenunggu += card;
            countMenunggu++;
        } else {
            htmlDiproses += card;
            countDiproses++;
        }
    });

    if (countMenunggu === 0) htmlMenunggu = `
        <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="height:400px">
            <i class="bi bi-inbox" style="font-size:3rem;opacity:0.5;margin-bottom:1rem"></i>
            <span style="font-size:13px;font-weight:600">Tidak ada pesanan menunggu.</span>
        </div>`;
        
    if (countDiproses === 0) htmlDiproses = `
        <div class="d-flex flex-column align-items-center justify-content-center text-muted" style="height:400px">
            <i class="bi bi-emoji-smile" style="font-size:3rem;opacity:0.5;margin-bottom:1rem"></i>
            <span style="font-size:13px;font-weight:600">Semua pesanan sudah diantar.</span>
        </div>`;

    colMenunggu.innerHTML = htmlMenunggu;
    colDiproses.innerHTML = htmlDiproses;
    document.getElementById('countMenunggu').textContent = countMenunggu;
    document.getElementById('countDiproses').textContent = countDiproses;
}

// ─── Actions ─────────────────────────────────────────────────
window.processOrder = async function(id, code) {
    if(!confirm(`Proses pesanan ${code}? Stok bahan baku akan dipotong otomatis sesuai resep.`)) return;
    
    const btn = document.getElementById(`btnProcess_${id}`);
    if (btn) {
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-spinner').classList.remove('d-none');
    }
    
    try {
        const res = await fetch('/ordio/api/kasir.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'process_order', id })
        });
        const data = await res.json();
        
        if (data.ok) {
            if (data.warning) showToast(data.warning, 'warning');
            else showToast(data.msg, 'success');
            
            lastDataStr = ''; // force re-render
            clearTimeout(pollingTimer);
            fetchOrders();
        } else {
            showToast(data.error, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.querySelector('.btn-text').classList.remove('d-none');
                btn.querySelector('.btn-spinner').classList.add('d-none');
            }
        }
    } catch(e) { 
        showToast('Koneksi error', 'danger');
        if (btn) {
            btn.disabled = false;
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.btn-spinner').classList.add('d-none');
        }
    }
};

window.completeOrder = async function(id, code) {
    if(!confirm(`Selesaikan pesanan ${code}? (Pastikan sudah diantar & dibayar)`)) return;
    
    const btn = document.getElementById(`btnComplete_${id}`);
    if (btn) {
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-spinner').classList.remove('d-none');
    }
    
    try {
        const res = await fetch('/ordio/api/kasir.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'complete_order', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast(data.msg, 'success');
            lastDataStr = '';
            clearTimeout(pollingTimer);
            fetchOrders();
        } else {
            showToast(data.error, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.querySelector('.btn-text').classList.remove('d-none');
                btn.querySelector('.btn-spinner').classList.add('d-none');
            }
        }
    } catch(e) { 
        showToast('Koneksi error', 'danger');
        if (btn) {
            btn.disabled = false;
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.btn-spinner').classList.add('d-none');
        }
    }
};

window.cancelOrder = async function(id, code) {
    if(!confirm(`Tolak pesanan ${code}? Pesanan tidak dapat dikembalikan lagi.`)) return;
    
    const btn = document.getElementById(`btnCancel_${id}`);
    if (btn) {
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-spinner').classList.remove('d-none');
    }
    
    try {
        const res = await fetch('/ordio/api/kasir.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'cancel_order', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast(data.msg, 'success');
            lastDataStr = '';
            clearTimeout(pollingTimer);
            fetchOrders();
        } else {
            showToast(data.error, 'danger');
            if (btn) {
                btn.disabled = false;
                btn.querySelector('.btn-text').classList.remove('d-none');
                btn.querySelector('.btn-spinner').classList.add('d-none');
            }
        }
    } catch(e) { 
        showToast('Koneksi error', 'danger');
        if (btn) {
            btn.disabled = false;
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.btn-spinner').classList.add('d-none');
        }
    }
};

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}

// Start polling
fetchOrders();

})();
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/kasir_footer.php';
