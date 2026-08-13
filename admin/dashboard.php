<?php
/**
 * admin/dashboard.php
 * Ringkasan admin, statistik cepat, dan chart
 * Ordio.io
 */

$pageTitle  = 'Dashboard Admin';
$activePage = 'dashboard';
// Chart.js CDN in extraHead
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-heading mb-1" style="font-weight:700">Dashboard</h2>
        <p class="text-muted-brand mb-0" style="font-size:14px">Ringkasan performa restoran Anda hari ini.</p>
    </div>
    <div id="loadingIndicator" style="display:none">
        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
    </div>
</div>

<!-- ─── Quick Stats ──────────────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card p-3 border-0 shadow-sm rounded-4 h-100" style="background:var(--primary-light);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted mb-1" style="font-size:12px;font-weight:700">PENJUALAN HARI INI</div>
                    <h3 class="font-heading m-0" style="font-weight:800;color:var(--primary)" id="valTodaySales">Rp 0</h3>
                </div>
                <div class="fs-1 text-primary opacity-50"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card p-3 border-0 shadow-sm rounded-4 h-100" style="background:#e3f2fd;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted mb-1" style="font-size:12px;font-weight:700">PESANAN AKTIF</div>
                    <h3 class="font-heading m-0" style="font-weight:800;color:#1565c0" id="valActiveOrders">0</h3>
                </div>
                <div class="fs-1 opacity-50" style="color:#1565c0"><i class="bi bi-bell"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Chart & Warnings ─────────────────────────────────────── -->
<div class="row">
    <!-- Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="font-heading" style="font-weight:700">Penjualan 7 Hari Terakhir</h6>
                <div style="position: relative; height:300px; width:100%">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Warning -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="font-heading mb-3" style="font-weight:700;color:#c62828">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Stok Menipis
                </h6>
                <div id="lowStockContainer">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function(){
'use strict';

const valTodaySales = document.getElementById('valTodaySales');
const valActiveOrders = document.getElementById('valActiveOrders');
const lowStockContainer = document.getElementById('lowStockContainer');
const indicator = document.getElementById('loadingIndicator');

let chartInstance = null;

async function loadDashboard() {
    indicator.style.display = 'block';
    try {
        const res = await fetch('/ordio/api/dashboard.php');
        const data = await res.json();
        
        if (data.ok) {
            // Stats
            valTodaySales.textContent = formatRupiah(data.summary.today_sales);
            valActiveOrders.textContent = data.summary.active_orders;
            
            // Low Stock
            if(data.low_stock.length === 0) {
                lowStockContainer.innerHTML = '<div class="text-center text-muted mt-4" style="font-size:13px">Semua stok aman.</div>';
            } else {
                let html = '<div class="list-group list-group-flush">';
                data.low_stock.forEach(item => {
                    html += `
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0 pb-2 mb-2" style="border-bottom:1px dashed var(--border) !important">
                            <div>
                                <div style="font-weight:600;font-size:14px">${escHtml(item.name)}</div>
                                <div style="font-size:11px;color:var(--muted)">Batas: ${item.low_stock_threshold} ${item.unit}</div>
                            </div>
                            <span class="badge text-bg-danger rounded-pill">${item.stock_qty} ${item.unit}</span>
                        </div>
                    `;
                });
                html += '</div>';
                lowStockContainer.innerHTML = html;
            }
            
            // Chart
            renderChart(data.chart.labels, data.chart.data);
        }
    } catch(e) {
        console.error("Dashboard error", e);
    } finally {
        indicator.style.display = 'none';
    }
}

function renderChart(labels, data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: data,
                borderColor: '#f36638',
                backgroundColor: 'rgba(243, 102, 56, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#f36638',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatRupiah(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if(value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                            if(value >= 1000) return (value/1000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                }
            }
        }
    });
}

function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

loadDashboard();

})();
</script>
SCRIPTS;
require_once __DIR__ . '/../includes/admin_footer.php';
