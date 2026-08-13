<?php
/**
 * order/status.php
 * Halaman Pelacakan Status Pesanan — Ordio.io
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Status Pesanan - Ordio.io</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="/ordio/assets/css/ordio.css">
    <link rel="stylesheet" href="/ordio/assets/css/order.css">
    <style>
        .order-meta-card {
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .order-meta-title {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }
        .order-meta-val {
            font-family: var(--font-heading);
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--border);
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-qty {
            font-weight: 700;
            color: var(--primary);
            width: 30px;
        }
        .item-name {
            flex-grow: 1;
            font-weight: 600;
        }
        .item-price {
            font-weight: 600;
        }
        .item-opts {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }
    </style>
</head>
<body>

<div class="order-container">
    <header class="order-header" style="justify-content:center">
        <h1 class="order-header-brand">Pesanan Anda</h1>
    </header>

    <div class="p-4" id="mainContent" style="display:none">
        
        <!-- Order Meta -->
        <div class="order-meta-card">
            <div class="row text-center">
                <div class="col-6" style="border-right: 1px solid var(--border)">
                    <div class="order-meta-title">Order ID</div>
                    <div class="order-meta-val" id="dispCode"></div>
                </div>
                <div class="col-6">
                    <div class="order-meta-title">Meja</div>
                    <div class="order-meta-val" id="dispTable"></div>
                </div>
            </div>
            
            <div class="mt-4 pt-3 text-center" style="border-top: 1px dashed var(--border)">
                <div class="order-meta-title">Atas Nama</div>
                <div class="order-meta-val" style="color:var(--primary)" id="dispName"></div>
            </div>
        </div>

        <h3 style="font-family:var(--font-heading); font-size:1.2rem; font-weight:700; margin-bottom:1rem">
            Status Pesanan
        </h3>

        <!-- Cancelled State -->
        <div id="cancelledState" style="display:none; text-align:center; padding: 2rem 0">
            <div style="width:60px;height:60px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 1rem">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h4 style="font-family:var(--font-heading); font-weight:700; color:#ef4444">Pesanan Dibatalkan</h4>
            <p style="font-size:13px; color:var(--muted)">Maaf, pesanan Anda dibatalkan oleh Kasir. Silakan pesan ulang atau hubungi pelayan.</p>
        </div>

        <!-- Tracking Progress -->
        <div class="track-progress" id="progressTrack">
            <!-- pseudo bar ::before represents background line -->
            <div class="track-progress-bar" id="progressBar"></div>
            
            <div class="track-step" id="step1">
                <div class="track-icon"><i class="bi bi-receipt"></i></div>
                <span class="track-label text-center">Menunggu<br>Konfirmasi</span>
            </div>
            
            <div class="track-step" id="step2">
                <div class="track-icon"><i class="bi bi-fire"></i></div>
                <span class="track-label text-center">Sedang<br>Diproses</span>
            </div>
            
            <div class="track-step" id="step3">
                <div class="track-icon"><i class="bi bi-check-lg"></i></div>
                <span class="track-label text-center">Selesai /<br>Diantar</span>
            </div>
        </div>

        <!-- Rincian Pesanan -->
        <div class="mt-5">
            <h3 style="font-family:var(--font-heading); font-size:1.2rem; font-weight:700; margin-bottom:1rem">
                Rincian Pesanan
            </h3>
            
            <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius-lg); padding:1rem" id="itemsContainer">
                <!-- items rendered here -->
            </div>
            
            <div class="d-flex justify-content-between mt-3 px-2">
                <span style="font-weight:600; color:var(--muted)">Total Pembayaran</span>
                <span style="font-family:var(--font-heading); font-weight:700; font-size:1.2rem; color:var(--primary)" id="dispTotal">Rp 0</span>
            </div>
            <div class="text-end px-2 mt-1">
                <span class="badge text-bg-success">
                    <i class="bi bi-check-circle-fill me-1"></i>Lunas
                </span>
            </div>
        </div>

        <button class="btn btn-outline-primary w-100 mt-5 py-2" onclick="window.location.href='/ordio/order/'">
            Buat Pesanan Baru
        </button>

    </div><!-- .p-4 -->
    
    <!-- Loading Screen -->
    <div id="loadingData" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:70vh; color:var(--muted)">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p>Mencari data pesanan...</p>
    </div>
</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const orderCode = urlParams.get('code');

if (!orderCode) {
    window.location.href = '/ordio/order/';
}

// ─── Polling Status ──────────────────────────────────────────
let currentStatus = '';

async function fetchStatus() {
    try {
        const res = await fetch(`/ordio/api/order.php?action=status&code=${orderCode}`);
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById('loadingData').style.display = 'none';
            document.getElementById('mainContent').style.display = 'block';
            
            renderOrder(data.order);
            updateProgress(data.order.status);
            
            // if finished or cancelled, stop polling
            if (data.order.status === 'selesai' || data.order.status === 'dibatalkan') {
                return; 
            }
        } else {
            document.getElementById('loadingData').innerHTML = `<p class="text-danger"><i class="bi bi-exclamation-circle me-1"></i> ${data.error}</p><a href="/ordio/order/" class="btn btn-outline-primary btn-sm mt-3">Kembali</a>`;
        }
    } catch(e) {
        console.error(e);
    }
    
    // poll again in 5 seconds if not finished
    setTimeout(fetchStatus, 5000);
}

function renderOrder(order) {
    document.getElementById('dispCode').textContent = order.order_code;
    document.getElementById('dispTable').textContent = order.table_number;
    document.getElementById('dispName').textContent = order.customer_name;
    document.getElementById('dispTotal').textContent = formatRupiah(order.total_price);
    
    const cont = document.getElementById('itemsContainer');
    cont.innerHTML = '';
    
    order.items.forEach(item => {
        let optText = item.options.map(o => o.value_name).join(', ');
        let noteHtml = item.note ? `<div class="item-opts">Catatan: ${escHtml(item.note)}</div>` : '';
        let optHtml = optText ? `<div class="item-opts">Varian: ${escHtml(optText)}</div>` : '';
        
        cont.innerHTML += `
            <div class="item-row">
                <div class="item-qty">${item.qty}x</div>
                <div class="item-name">
                    ${escHtml(item.menu_name)}
                    ${optHtml}
                    ${noteHtml}
                </div>
                <div class="item-price">${formatRupiah(item.price_at_order * item.qty)}</div>
            </div>
        `;
    });
}

function updateProgress(status) {
    if (status === currentStatus) return;
    currentStatus = status;
    
    const s1 = document.getElementById('step1');
    const s2 = document.getElementById('step2');
    const s3 = document.getElementById('step3');
    const bar = document.getElementById('progressBar');
    const track = document.getElementById('progressTrack');
    const cancelled = document.getElementById('cancelledState');
    
    // Reset classes
    [s1, s2, s3].forEach(el => el.className = 'track-step');
    track.style.display = 'flex';
    cancelled.style.display = 'none';
    
    if (status === 'menunggu') {
        s1.classList.add('active');
        bar.style.width = '0%';
    } 
    else if (status === 'diproses') {
        s1.classList.add('done');
        s2.classList.add('active');
        bar.style.width = '50%';
    }
    else if (status === 'selesai') {
        s1.classList.add('done');
        s2.classList.add('done');
        s3.classList.add('done');
        bar.style.width = '100%';
    }
    else if (status === 'dibatalkan') {
        track.style.display = 'none';
        cancelled.style.display = 'block';
    }
}

function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

// Start immediately
fetchStatus();
</script>
</body>
</html>
