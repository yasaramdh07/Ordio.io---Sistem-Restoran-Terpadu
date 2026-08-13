<?php
/**
 * admin/ingredients.php
 * Manajemen Bahan Baku — Ordio.io
 */

$pageTitle  = 'Bahan Baku';
$activePage = 'ingredients';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Bahan Baku</h1>
        <p class="page-subtitle">Kelola stok bahan baku, satuan, dan ambang batas stok rendah</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div id="lowStockAlert" style="display:none">
            <span class="badge-low-stock">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <span id="lowStockCount">0</span> stok rendah
            </span>
        </div>
        <button class="btn btn-primary" id="btnAddIngredient">
            <i class="bi bi-plus-lg me-1"></i>Tambah Bahan
        </button>
    </div>
</div>

<!-- ─── Toolbar ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari bahan baku…">
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <label class="form-label mb-0" style="font-size:12px;white-space:nowrap">Filter:</label>
        <select class="form-select form-select-sm" id="filterSelect" style="width:auto">
            <option value="all">Semua</option>
            <option value="low">Stok Rendah</option>
            <option value="ok">Stok Aman</option>
        </select>
    </div>
</div>

<!-- ─── Ingredients Table ────────────────────────────────────── -->
<div class="ing-table">
    <table>
        <thead>
            <tr>
                <th style="width:36px">#</th>
                <th>Nama Bahan</th>
                <th>Satuan</th>
                <th>Stok Saat Ini</th>
                <th>Ambang Batas</th>
                <th>Status</th>
                <th style="text-align:right">Aksi</th>
            </tr>
        </thead>
        <tbody id="ingTableBody">
            <!-- populated by JS -->
        </tbody>
    </table>
    <div id="ingEmpty" class="empty-state" style="display:none">
        <i class="bi bi-box-seam"></i>
        <p>Belum ada bahan baku.<br>Klik <strong>Tambah Bahan</strong> untuk mulai.</p>
    </div>
    <div id="ingNoResult" class="empty-state" style="display:none">
        <i class="bi bi-search"></i>
        <p>Tidak ada bahan baku yang cocok dengan pencarian.</p>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Tambah / Edit Bahan Baku
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="ingModal" tabindex="-1" aria-labelledby="ingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ingModalLabel">Tambah Bahan Baku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ingForm" novalidate>
                    <input type="hidden" id="ingId" name="id">

                    <div class="mb-3">
                        <label class="form-label" for="ingName">Nama Bahan *</label>
                        <input type="text" class="form-control" id="ingName" name="name"
                               placeholder="Contoh: Bawang Merah" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ingUnit">Satuan *</label>
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" id="ingUnit" name="unit"
                                   placeholder="kg / gram / liter / pcs…" list="unitSuggestions" required>
                            <datalist id="unitSuggestions">
                                <option value="kg">
                                <option value="gram">
                                <option value="liter">
                                <option value="ml">
                                <option value="pcs">
                                <option value="butir">
                                <option value="lembar">
                                <option value="bungkus">
                                <option value="porsi">
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ingStock">Stok Saat Ini</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="ingStock" name="stock_qty"
                                   placeholder="0" min="0" step="0.01" value="0">
                            <span class="input-group-text" id="ingUnitDisplay">—</span>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" for="ingThreshold">Ambang Batas Stok Rendah</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="ingThreshold" name="low_stock_threshold"
                                   placeholder="0" min="0" step="0.01" value="0">
                            <span class="input-group-text" id="ingThresholdUnit">—</span>
                        </div>
                        <small class="text-muted-brand" style="font-size:11px">
                            Sistem akan menandai merah jika stok &le; nilai ini (0 = nonaktifkan notifikasi)
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveIngredient">
                    <span class="save-text"><i class="bi bi-check-lg me-1"></i>Simpan</span>
                    <span class="save-spinner d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Adjust Stok
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sesuaikan Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1" style="font-size:13px;color:var(--muted)">Bahan: <strong id="adjustIngName">—</strong></p>
                <p class="mb-3" style="font-size:13px;color:var(--muted)">Stok saat ini: <strong id="adjustCurrentStock">—</strong></p>
                <label class="form-label">Tambah / Kurangi Stok</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="adjustDelta" step="0.01" placeholder="Masukkan angka (+/-)">
                    <span class="input-group-text" id="adjustUnit">—</span>
                </div>
                <small class="text-muted-brand" style="font-size:11px">
                    Positif (+) untuk tambah stok, negatif (-) untuk pengurangan
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveAdjust">
                    <i class="bi bi-check-lg me-1"></i>Terapkan
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function () {
'use strict';

// ── State ──────────────────────────────────────────────────
let allIngredients   = [];
let editingIngId     = null;
let adjustingIngId   = null;
let searchQuery      = '';
let filterMode       = 'all';

const ingModal    = new bootstrap.Modal(document.getElementById('ingModal'));
const adjustModal = new bootstrap.Modal(document.getElementById('adjustModal'));

// ── Init ────────────────────────────────────────────────────
loadIngredients();

// ── Loader ──────────────────────────────────────────────────
async function loadIngredients() {
    const params = new URLSearchParams({ action: 'list' });
    if (searchQuery) params.set('search', searchQuery);

    const res  = await fetch('/ordio/api/ingredients.php?' + params);
    const data = await res.json();
    allIngredients = data.ingredients || [];
    renderTable();
    updateLowStockAlert();
}

// ── Render Table ─────────────────────────────────────────────
function renderTable() {
    const tbody   = document.getElementById('ingTableBody');
    const empty   = document.getElementById('ingEmpty');
    const noResult= document.getElementById('ingNoResult');
    tbody.innerHTML = '';

    let items = allIngredients;

    if (filterMode === 'low') {
        items = items.filter(i => i.is_low_stock == 1);
    } else if (filterMode === 'ok') {
        items = items.filter(i => !i.is_low_stock);
    }

    if (!allIngredients.length) {
        empty.style.display    = '';
        noResult.style.display = 'none';
        return;
    }
    empty.style.display = 'none';

    if (!items.length) {
        noResult.style.display = '';
        return;
    }
    noResult.style.display = 'none';

    items.forEach((ing, idx) => {
        const isLow  = ing.is_low_stock == 1;
        const tr     = document.createElement('tr');

        tr.innerHTML = `
            <td style="color:var(--muted);font-size:12px">${idx + 1}</td>
            <td>
                <span class="ing-name">${escHtml(ing.name)}</span>
            </td>
            <td>
                <span class="ing-unit">${escHtml(ing.unit)}</span>
            </td>
            <td>
                <span class="${isLow ? 'stock-low' : 'stock-ok'}">
                    ${formatNum(ing.stock_qty)} ${escHtml(ing.unit)}
                </span>
            </td>
            <td style="color:var(--muted);font-size:13px">
                ${ing.low_stock_threshold > 0
                    ? formatNum(ing.low_stock_threshold) + ' ' + escHtml(ing.unit)
                    : '<span style="opacity:0.4">—</span>'}
            </td>
            <td>
                ${isLow
                    ? '<span class="badge-low-stock"><i class="bi bi-exclamation-triangle me-1"></i>Rendah</span>'
                    : '<span style="color:#27ae60;font-size:13px;font-weight:600">✔ Aman</span>'}
            </td>
            <td>
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn-icon" title="Sesuaikan Stok" onclick="openAdjust(${ing.id},'${escHtml(ing.name)}',${ing.stock_qty},'${escHtml(ing.unit)}')">
                        <i class="bi bi-arrow-left-right"></i>
                    </button>
                    <button class="btn-icon" title="Edit" onclick="openEdit(${ing.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon danger" title="Hapus" onclick="deleteIngredient(${ing.id},'${escHtml(ing.name)}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updateLowStockAlert() {
    const lowCount = allIngredients.filter(i => i.is_low_stock == 1).length;
    const alert    = document.getElementById('lowStockAlert');
    document.getElementById('lowStockCount').textContent = lowCount;
    alert.style.display = lowCount > 0 ? '' : 'none';
}

// ── Open Add ────────────────────────────────────────────────
function openAdd() {
    editingIngId = null;
    document.getElementById('ingModalLabel').textContent = 'Tambah Bahan Baku';
    document.getElementById('ingForm').reset();
    document.getElementById('ingId').value = '';
    document.getElementById('ingUnitDisplay').textContent   = '—';
    document.getElementById('ingThresholdUnit').textContent  = '—';
    ingModal.show();
    setTimeout(() => document.getElementById('ingName').focus(), 400);
}

// ── Open Edit ────────────────────────────────────────────────
function openEdit(id) {
    editingIngId = id;
    const ing = allIngredients.find(i => i.id == id);
    if (!ing) return;

    document.getElementById('ingModalLabel').textContent      = 'Edit Bahan Baku';
    document.getElementById('ingId').value                    = ing.id;
    document.getElementById('ingName').value                  = ing.name;
    document.getElementById('ingUnit').value                  = ing.unit;
    document.getElementById('ingStock').value                 = ing.stock_qty;
    document.getElementById('ingThreshold').value             = ing.low_stock_threshold;
    document.getElementById('ingUnitDisplay').textContent     = ing.unit;
    document.getElementById('ingThresholdUnit').textContent   = ing.unit;
    ingModal.show();
}

// ── Save Ingredient ─────────────────────────────────────────
async function saveIngredient() {
    const name = document.getElementById('ingName').value.trim();
    const unit = document.getElementById('ingUnit').value.trim();
    if (!name) { showToast('Nama bahan wajib diisi.', 'danger'); return; }
    if (!unit) { showToast('Satuan wajib diisi.', 'danger');     return; }

    const saveBtn    = document.getElementById('btnSaveIngredient');
    saveBtn.disabled = true;
    saveBtn.querySelector('.save-text').classList.add('d-none');
    saveBtn.querySelector('.save-spinner').classList.remove('d-none');

    try {
        const formData = new FormData(document.getElementById('ingForm'));
        formData.set('action', 'save');

        const res  = await fetch('/ordio/api/ingredients.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.ok) {
            ingModal.hide();
            showToast(editingIngId ? 'Bahan berhasil diperbarui!' : 'Bahan berhasil ditambahkan!', 'success');
            loadIngredients();
        } else {
            showToast(data.error, 'danger');
        }
    } catch (e) {
        showToast('Gagal terhubung ke server.', 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.querySelector('.save-text').classList.remove('d-none');
        saveBtn.querySelector('.save-spinner').classList.add('d-none');
    }
}

// ── Delete Ingredient ─────────────────────────────────────────
function deleteIngredient(id, name) {
    confirmAction(`Hapus bahan "${name}"?\nPastikan bahan tidak sedang dipakai di resep menu.`, async () => {
        const res  = await fetch('/ordio/api/ingredients.php', {
            method: 'POST',
            body:   new URLSearchParams({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast('Bahan berhasil dihapus.', 'success');
            loadIngredients();
        } else {
            showToast(data.error, 'danger');
        }
    });
}

// ── Adjust Stock ─────────────────────────────────────────────
function openAdjust(id, name, currentStock, unit) {
    adjustingIngId = id;
    document.getElementById('adjustIngName').textContent    = name;
    document.getElementById('adjustCurrentStock').textContent = formatNum(currentStock) + ' ' + unit;
    document.getElementById('adjustUnit').textContent       = unit;
    document.getElementById('adjustDelta').value            = '';
    adjustModal.show();
    setTimeout(() => document.getElementById('adjustDelta').focus(), 400);
}

async function saveAdjust() {
    const delta = parseFloat(document.getElementById('adjustDelta').value);
    if (isNaN(delta) || delta === 0) {
        showToast('Masukkan nilai perubahan stok.', 'warning');
        return;
    }

    const res  = await fetch('/ordio/api/ingredients.php', {
        method: 'POST',
        body:   new URLSearchParams({ action: 'adjust_stock', id: adjustingIngId, delta })
    });
    const data = await res.json();

    if (data.ok) {
        adjustModal.hide();
        showToast(`Stok berhasil disesuaikan. Stok baru: ${formatNum(data.stock_qty)}`, 'success');
        loadIngredients();
    } else {
        showToast(data.error, 'danger');
    }
}

// ── Unit label sync ───────────────────────────────────────────
document.getElementById('ingUnit').addEventListener('input', function () {
    const unit = this.value.trim() || '—';
    document.getElementById('ingUnitDisplay').textContent  = unit;
    document.getElementById('ingThresholdUnit').textContent = unit;
});

// ── Utility ───────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function formatNum(val) {
    return Number(val).toLocaleString('id-ID', { maximumFractionDigits: 3 });
}

// ── Event Listeners ───────────────────────────────────────────
document.getElementById('btnAddIngredient').addEventListener('click', openAdd);
document.getElementById('btnSaveIngredient').addEventListener('click', saveIngredient);
document.getElementById('btnSaveAdjust').addEventListener('click', saveAdjust);

document.getElementById('filterSelect').addEventListener('change', function () {
    filterMode = this.value;
    renderTable();
});

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = this.value.trim();
        loadIngredients();
    }, 350);
});

// Enter to save in ingredient modal
document.getElementById('ingModal').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.target.matches('textarea')) {
        e.preventDefault();
        saveIngredient();
    }
});

})(); // end IIFE
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/admin_footer.php';
