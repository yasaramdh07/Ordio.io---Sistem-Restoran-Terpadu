<?php
/**
 * order/index.php
 * Halaman Utama Pemesanan Pelanggan — Ordio.io
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pesan - Ordio.io</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ordio/assets/css/ordio.css">
    <link rel="stylesheet" href="/ordio/assets/css/order.css">
</head>
<body>

<div class="order-container">
    
    <!-- ─── Header ─── -->
    <header class="order-header">
        <h1 class="order-header-brand">Ordio.io</h1>
        <div class="order-header-info" id="headerInfo" style="display:none">
            <i class="bi bi-person me-1"></i><span id="displayCustomerName"></span> 
            <span class="mx-1">•</span> 
            <i class="bi bi-geo-alt me-1"></i>Meja <span id="displayTableNumber"></span>
        </div>
    </header>

    <!-- ─── Categories ─── -->
    <div class="order-categories-wrap">
        <div class="order-categories" id="catContainer">
            <!-- populated by JS -->
        </div>
    </div>

    <!-- ─── Menu Catalog ─── -->
    <div class="order-catalog" id="menuContainer">
        <!-- populated by JS -->
    </div>

    <!-- ─── Sticky Cart Button ─── -->
    <div class="order-cart-sticky" id="cartSticky">
        <div class="order-cart-btn" onclick="openCart()">
            <div class="order-cart-btn-left">
                <div class="order-cart-badge" id="cartBadge">0</div>
                <span>Lihat Keranjang</span>
            </div>
            <div class="order-cart-total" id="cartTotalBtn">Rp 0</div>
        </div>
    </div>

</div><!-- end .order-container -->


<!-- ════════════════════════════════════════════════════════════
     OVERLAY & BOTTOM SHEETS
════════════════════════════════════════════════════════════ -->
<div class="order-overlay" id="overlay"></div>

<!-- 1. Welcome Overlay (Force Input Name & Table) -->
<div class="welcome-overlay" id="welcomeOverlay">
    <div class="welcome-card">
        <div class="welcome-logo"><i class="bi bi-qr-code-scan"></i></div>
        <h2 class="welcome-title">Selamat Datang!</h2>
        <p class="welcome-desc">Silakan isi nama dan nomor meja Anda untuk mulai memesan.</p>
        
        <form id="welcomeForm" onsubmit="event.preventDefault(); startOrdering();">
            <div class="mb-3 text-start">
                <label class="form-label" style="font-weight:600;font-size:13px">Nama Anda</label>
                <input type="text" id="inputName" class="form-control form-control-lg" required placeholder="Contoh: Budi">
            </div>
            <div class="mb-4 text-start">
                <label class="form-label" style="font-weight:600;font-size:13px">Nomor / Nama Meja</label>
                <input type="text" id="inputTable" class="form-control form-control-lg" required placeholder="Lihat nomor di meja Anda">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100" style="border-radius:12px;font-family:var(--font-heading)">
                Mulai Pesan <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>
    </div>
</div>

<!-- 2. Bottom Sheet: Options (Variants) -->
<div class="order-bottom-sheet" id="sheetOptions">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="optMenuTitle">Nama Menu</h3>
        <button class="btn-close" onclick="closeSheet('sheetOptions')"></button>
    </div>
    <div class="sheet-body" id="optContainer">
        <!-- populated by JS -->
    </div>
    <div class="sheet-footer">
        <!-- Qty and Add to cart -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span style="font-weight:600;font-size:14px">Jumlah</span>
            <div class="qty-control">
                <button class="qty-btn" onclick="updateOptQty(-1)"><i class="bi bi-dash"></i></button>
                <div class="qty-num" id="optQty">1</div>
                <button class="qty-btn" onclick="updateOptQty(1)"><i class="bi bi-plus"></i></button>
            </div>
        </div>
        <!-- Notes -->
        <div class="mb-3">
            <textarea class="sheet-note-input" id="optNote" rows="2" placeholder="Catatan tambahan (opsional)..."></textarea>
        </div>
        <button class="btn btn-primary w-100 py-3" style="border-radius:12px;font-family:var(--font-heading)" onclick="confirmOptions()">
            Tambah ke Keranjang • <span id="optTotalPrice">Rp 0</span>
        </button>
    </div>
</div>

<!-- 3. Bottom Sheet: Cart -->
<div class="order-bottom-sheet" id="sheetCart" style="height:90vh">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Keranjang Anda</h3>
        <button class="btn-close" onclick="closeSheet('sheetCart')"></button>
    </div>
    <div class="sheet-body">
        <div id="cartItemsContainer">
            <!-- populated by JS -->
        </div>
        <div id="cartEmptyState" class="text-center py-5" style="display:none">
            <i class="bi bi-cart-x text-muted" style="font-size:3rem"></i>
            <p class="mt-3 text-muted" style="font-size:14px">Keranjang masih kosong.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <div class="d-flex justify-content-between mb-2" style="font-size:14px;color:var(--muted)">
            <span>Total Item</span>
            <span id="cartTotalItemsSum" style="font-weight:700;color:var(--charcoal)">0</span>
        </div>
        <div class="d-flex justify-content-between mb-3" style="font-size:1.1rem">
            <span style="font-family:var(--font-heading);font-weight:700">Total Harga</span>
            <span id="cartGrandTotal" style="font-family:var(--font-heading);font-weight:800;color:var(--primary)">Rp 0</span>
        </div>
        <button class="btn btn-primary w-100 py-3" id="btnSubmitOrder" style="border-radius:12px;font-family:var(--font-heading)" onclick="submitOrder()">
            Pesan Sekarang <i class="bi bi-check-circle-fill ms-2"></i>
        </button>
    </div>
</div>

<!-- Loading Submitting -->
<div class="loading-overlay" id="loadingSubmit" style="display:none">
    <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem"></div>
    <h4 class="mt-3 font-heading text-dark">Memproses Pesanan...</h4>
    <p class="text-muted-brand" style="font-size:13px">Mohon tunggu sebentar, pembayaran sedang diproses secara otomatis.</p>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="orderToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-600" id="orderToastBody" style="font-family:var(--font-body)"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
// ─── STATE ───────────────────────────────────────────────────
let catalogData = { categories: [], menus: [] };
let cart = []; 
let currentCustomer = { name: '', table: '' };
let activeMenuContext = null; // untuk modal options
let activeOptQty = 1;
let activeOptPrice = 0;

// ─── INIT ────────────────────────────────────────────────────
(function init() {
    // Check sessionStorage
    const storedName = sessionStorage.getItem('ordio_customer_name');
    const storedTable= sessionStorage.getItem('ordio_table_number');
    
    if (storedName && storedTable) {
        currentCustomer.name = storedName;
        currentCustomer.table = storedTable;
        document.getElementById('displayCustomerName').textContent = storedName;
        document.getElementById('displayTableNumber').textContent = storedTable;
        document.getElementById('headerInfo').style.display = 'block';
        document.getElementById('welcomeOverlay').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('welcomeOverlay').style.display = 'none';
        }, 400);
    }

    loadCatalog();
    
    // Bind overlay click to close sheets
    document.getElementById('overlay').addEventListener('click', () => {
        closeSheet('sheetOptions');
        closeSheet('sheetCart');
    });
})();

// ─── LOAD CATALOG ────────────────────────────────────────────
async function loadCatalog() {
    try {
        const res = await fetch('/ordio/api/order.php?action=catalog');
        const data = await res.json();
        if (data.ok) {
            catalogData = data;
            renderCategories();
            renderMenus();
        } else {
            showToast('Gagal memuat menu: ' + data.error, 'danger');
        }
    } catch (e) {
        showToast('Koneksi terputus.', 'danger');
    }
}

// ─── UI RENDERERS ────────────────────────────────────────────
function renderCategories() {
    const cont = document.getElementById('catContainer');
    cont.innerHTML = `<button class="order-cat-btn active" onclick="filterCategory('all', this)">Semua Menu</button>`;
    
    catalogData.categories.forEach(cat => {
        cont.innerHTML += `<button class="order-cat-btn" onclick="filterCategory(${cat.id}, this)">${escHtml(cat.name)}</button>`;
    });
}

function filterCategory(catId, btnEl) {
    document.querySelectorAll('.order-cat-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    
    // Scroll smoothly to section if not 'all'
    if (catId === 'all') {
        window.scrollTo({top: 0, behavior: 'smooth'});
    } else {
        const el = document.getElementById('cat-section-' + catId);
        if (el) {
            const offset = 110; // header + tabs height approx
            const bodyRect = document.body.getBoundingClientRect().top;
            const elementRect = el.getBoundingClientRect().top;
            const elementPosition = elementRect - bodyRect;
            const offsetPosition = elementPosition - offset;
            window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
        }
    }
}

function renderMenus() {
    const cont = document.getElementById('menuContainer');
    cont.innerHTML = '';
    
    if (catalogData.menus.length === 0) {
        cont.innerHTML = '<div class="text-center text-muted py-5">Belum ada menu yang tersedia.</div>';
        return;
    }

    // Group menus by category
    const grouped = {};
    catalogData.menus.forEach(m => {
        const cid = m.category_id || 0;
        if (!grouped[cid]) grouped[cid] = [];
        grouped[cid].push(m);
    });

    // Render grouped
    const catMap = {};
    catalogData.categories.forEach(c => catMap[c.id] = c.name);
    catMap[0] = 'Lainnya';

    for (const cid in grouped) {
        const catName = catMap[cid] || 'Lainnya';
        let html = `<h2 class="order-cat-title" id="cat-section-${cid}">${escHtml(catName)}</h2>`;
        
        grouped[cid].forEach(m => {
            const imgHtml = m.image_path 
                ? `<img src="/ordio/${escHtml(m.image_path)}" class="order-menu-img" loading="lazy">`
                : `<div class="order-menu-img-placeholder"><i class="bi bi-image-alt"></i></div>`;
            
            // Pass JSON to JS function via dataset
            html += `
                <div class="order-menu-card">
                    ${imgHtml}
                    <div class="order-menu-info">
                        <h3 class="order-menu-name">${escHtml(m.name)}</h3>
                        <p class="order-menu-desc">${escHtml(m.description)}</p>
                        <div class="order-menu-footer">
                            <span class="order-menu-price">${formatRupiah(m.price)}</span>
                            <button class="order-btn-add" onclick="handleAddMenu(${m.id})">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        cont.innerHTML += html;
    }
}

// ─── ADD TO CART LOGIC ───────────────────────────────────────
function handleAddMenu(menuId) {
    const menu = catalogData.menus.find(m => m.id == menuId);
    if (!menu) return;

    if (menu.options && menu.options.length > 0) {
        // Has options, open bottom sheet
        openOptionsSheet(menu);
    } else {
        // No options, add directly to cart
        addToCart({
            menu_id: menu.id,
            name: menu.name,
            price_at_order: menu.price,
            qty: 1,
            note: '',
            options: [],
            options_text: ''
        });
        showToast('Ditambahkan ke keranjang');
    }
}

// ─── OPTIONS BOTTOM SHEET ────────────────────────────────────
function openOptionsSheet(menu) {
    activeMenuContext = menu;
    activeOptQty = 1;
    activeOptPrice = menu.price;

    document.getElementById('optMenuTitle').textContent = menu.name;
    document.getElementById('optQty').textContent = activeOptQty;
    document.getElementById('optNote').value = '';
    
    const cont = document.getElementById('optContainer');
    cont.innerHTML = '';

    menu.options.forEach(opt => {
        const isReq = opt.is_required == 1;
        let html = `
            <div class="opt-group" data-opt-id="${opt.id}" data-req="${isReq}">
                <div class="opt-group-header">
                    <h4 class="opt-title">${escHtml(opt.option_name)}</h4>
                    <span class="${isReq ? 'opt-req' : 'opt-opt'}">${isReq ? 'Wajib Pilih 1' : 'Opsional'}</span>
                </div>
        `;
        
        opt.values.forEach(val => {
            const ep = val.extra_price > 0 ? `+${formatRupiah(val.extra_price)}` : '';
            html += `
                <div class="opt-item" onclick="toggleOption(this, ${opt.id}, ${val.id}, ${val.extra_price}, '${escHtml(val.value_name)}')">
                    <div class="opt-item-left">
                        <div class="custom-radio"></div>
                        <span class="opt-item-name">${escHtml(val.value_name)}</span>
                    </div>
                    <span class="opt-item-price">${ep}</span>
                </div>
            `;
        });
        html += `</div>`;
        cont.innerHTML += html;
    });

    updateOptTotalPrice();
    openSheet('sheetOptions');
}

function toggleOption(itemEl, optId, valId, extraPrice, valName) {
    const group = itemEl.closest('.opt-group');
    const isSelected = itemEl.classList.contains('selected');
    
    // Ini dibuat mirip radio button behavior (pilih 1 per group)
    // Walaupun skema bisa diubah multi-select, PRD mengarah ke radio style
    const siblings = group.querySelectorAll('.opt-item');
    siblings.forEach(s => s.classList.remove('selected'));
    
    if (!isSelected) {
        itemEl.classList.add('selected');
        itemEl.dataset.valId = valId;
        itemEl.dataset.price = extraPrice;
        itemEl.dataset.name  = valName;
    } else {
        // If optional, allow deselecting
        if (group.dataset.req == '0') {
            itemEl.classList.remove('selected');
            delete itemEl.dataset.valId;
            delete itemEl.dataset.price;
            delete itemEl.dataset.name;
        } else {
            // Force select if required
            itemEl.classList.add('selected');
        }
    }
    updateOptTotalPrice();
}

function updateOptQty(delta) {
    activeOptQty += delta;
    if (activeOptQty < 1) activeOptQty = 1;
    document.getElementById('optQty').textContent = activeOptQty;
    updateOptTotalPrice();
}

function updateOptTotalPrice() {
    let extra = 0;
    const selected = document.querySelectorAll('#optContainer .opt-item.selected');
    selected.forEach(el => {
        extra += parseFloat(el.dataset.price || 0);
    });
    activeOptPrice = activeMenuContext.price + extra;
    document.getElementById('optTotalPrice').textContent = formatRupiah(activeOptPrice * activeOptQty);
}

function confirmOptions() {
    // Validate required
    const groups = document.querySelectorAll('#optContainer .opt-group[data-req="1"]');
    for (let g of groups) {
        if (!g.querySelector('.opt-item.selected')) {
            const title = g.querySelector('.opt-title').textContent;
            showToast(`Harap pilih ${title}`, 'warning');
            return;
        }
    }

    // Collect options
    const selectedVals = [];
    const selectedText = [];
    document.querySelectorAll('#optContainer .opt-item.selected').forEach(el => {
        selectedVals.push(parseInt(el.dataset.valId));
        selectedText.push(el.dataset.name);
    });

    addToCart({
        menu_id: activeMenuContext.id,
        name: activeMenuContext.name,
        price_at_order: activeOptPrice,
        qty: activeOptQty,
        note: document.getElementById('optNote').value.trim(),
        options: selectedVals,
        options_text: selectedText.join(', ')
    });

    closeSheet('sheetOptions');
    showToast('Ditambahkan ke keranjang');
}

// ─── CART LOGIC ──────────────────────────────────────────────
function addToCart(item) {
    // Check if exactly same item exists (same menu, same options, same note)
    const existingIdx = cart.findIndex(c => 
        c.menu_id === item.menu_id && 
        c.note === item.note && 
        JSON.stringify(c.options) === JSON.stringify(item.options)
    );

    if (existingIdx >= 0) {
        cart[existingIdx].qty += item.qty;
    } else {
        cart.push(item);
    }
    updateCartUI();
}

function updateCartUI() {
    const badge = document.getElementById('cartBadge');
    const sticky= document.getElementById('cartSticky');
    const totalBtn = document.getElementById('cartTotalBtn');
    
    let totalQty = 0;
    let grandTotal = 0;
    
    cart.forEach(c => {
        totalQty += c.qty;
        grandTotal += (c.price_at_order * c.qty);
    });

    badge.textContent = totalQty;
    totalBtn.textContent = formatRupiah(grandTotal);

    if (totalQty > 0) {
        sticky.classList.add('show');
    } else {
        sticky.classList.remove('show');
        closeSheet('sheetCart');
    }
}

function openCart() {
    renderCartSheet();
    openSheet('sheetCart');
}

function renderCartSheet() {
    const cont = document.getElementById('cartItemsContainer');
    const empty = document.getElementById('cartEmptyState');
    const totalItems = document.getElementById('cartTotalItemsSum');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const btnSubmit = document.getElementById('btnSubmitOrder');

    cont.innerHTML = '';
    
    if (cart.length === 0) {
        empty.style.display = 'block';
        totalItems.textContent = '0';
        grandTotalEl.textContent = 'Rp 0';
        btnSubmit.disabled = true;
        return;
    }

    empty.style.display = 'none';
    btnSubmit.disabled = false;

    let sumQty = 0;
    let sumTotal = 0;

    cart.forEach((c, idx) => {
        sumQty += c.qty;
        sumTotal += (c.price_at_order * c.qty);
        
        // Find image from catalog
        const menu = catalogData.menus.find(m => m.id == c.menu_id);
        const img = menu && menu.image_path ? `/ordio/${menu.image_path}` : '';
        
        const imgHtml = img 
            ? `<img src="${img}" class="cart-item-img">`
            : `<div class="cart-item-img" style="display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:1.5rem"><i class="bi bi-image-alt"></i></div>`;
            
        let optHtml = '';
        if (c.options_text) optHtml += `<div>Varian: ${escHtml(c.options_text)}</div>`;
        if (c.note) optHtml += `<div>Catatan: ${escHtml(c.note)}</div>`;

        cont.innerHTML += `
            <div class="cart-item">
                ${imgHtml}
                <div class="cart-item-info">
                    <h4 class="cart-item-name">${escHtml(c.name)}</h4>
                    ${optHtml ? `<div class="cart-item-opts">${optHtml}</div>` : ''}
                    <div class="cart-item-price">${formatRupiah(c.price_at_order)}</div>
                    
                    <div class="cart-item-actions">
                        <div class="qty-control" style="background:#fff; border:1px solid var(--border)">
                            <button class="qty-btn" style="width:28px;height:28px" onclick="changeCartQty(${idx}, -1)"><i class="bi bi-dash"></i></button>
                            <div class="qty-num" style="font-size:0.95rem">${c.qty}</div>
                            <button class="qty-btn" style="width:28px;height:28px" onclick="changeCartQty(${idx}, 1)"><i class="bi bi-plus"></i></button>
                        </div>
                        <button class="btn btn-sm text-danger px-2 py-1" onclick="removeCartItem(${idx})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    totalItems.textContent = sumQty;
    grandTotalEl.textContent = formatRupiah(sumTotal);
}

function changeCartQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty < 1) {
        cart.splice(idx, 1); // remove if qty 0
    }
    updateCartUI();
    renderCartSheet();
}

function removeCartItem(idx) {
    cart.splice(idx, 1);
    updateCartUI();
    renderCartSheet();
}

// ─── SUBMIT ORDER ────────────────────────────────────────────
function startOrdering() {
    const name = document.getElementById('inputName').value.trim();
    const table = document.getElementById('inputTable').value.trim();
    
    if (name && table) {
        currentCustomer.name = name;
        currentCustomer.table = table;
        
        sessionStorage.setItem('ordio_customer_name', name);
        sessionStorage.setItem('ordio_table_number', table);
        
        document.getElementById('displayCustomerName').textContent = name;
        document.getElementById('displayTableNumber').textContent = table;
        document.getElementById('headerInfo').style.display = 'block';
        
        document.getElementById('welcomeOverlay').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('welcomeOverlay').style.display = 'none';
        }, 400);
    }
}

async function submitOrder() {
    if (cart.length === 0) return;
    
    const loading = document.getElementById('loadingSubmit');
    loading.style.display = 'flex';
    
    const payload = {
        customer_name: currentCustomer.name,
        table_number: currentCustomer.table,
        cart: cart
    };

    try {
        const res = await fetch('/ordio/api/order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'submit', ...payload })
        });
        const data = await res.json();
        
        if (data.ok) {
            // Berhasil! Clear session cart (just array, session storage is keeping name/table)
            cart = [];
            
            // Simulasi loading payment 2 detik
            setTimeout(() => {
                window.location.href = `/ordio/order/status.php?code=${data.order_code}`;
            }, 2000);
        } else {
            loading.style.display = 'none';
            showToast(data.error, 'danger');
        }
    } catch (e) {
        loading.style.display = 'none';
        showToast('Koneksi terputus saat mengirim pesanan.', 'danger');
    }
}


// ─── UTILS & ANIMATION ───────────────────────────────────────
function openSheet(id) {
    document.getElementById('overlay').classList.add('show');
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSheet(id) {
    document.getElementById(id).classList.remove('show');
    // Check if other sheet is open before removing overlay
    const anyOpen = document.querySelectorAll('.order-bottom-sheet.show').length;
    if (anyOpen === 0) {
        document.getElementById('overlay').classList.remove('show');
        document.body.style.overflow = '';
    }
}

function showToast(message, type = 'success') {
    const el = document.getElementById('orderToast');
    const body = document.getElementById('orderToastBody');
    
    el.className = `toast align-items-center text-bg-${type} border-0`;
    body.textContent = message;
    
    const toast = bootstrap.Toast.getOrCreateInstance(el, {delay: 3000});
    toast.show();
}

function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
