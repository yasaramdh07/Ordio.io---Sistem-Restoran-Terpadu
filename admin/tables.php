<?php
/**
 * admin/tables.php
 * Manajemen Data Meja — Ordio.io
 */

$pageTitle  = 'Data Meja';
$activePage = 'tables';
require_once __DIR__ . '/../includes/admin_header.php';

$db         = getDB();
$totalTables= $db->query("SELECT COUNT(*) FROM tables")->fetchColumn();
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Data Meja</h1>
        <p class="page-subtitle">Daftar meja sebagai referensi internal — total <strong><?= $totalTables ?></strong> meja terdaftar</p>
    </div>
    <button class="btn btn-primary" id="btnAddTable">
        <i class="bi bi-plus-lg me-1"></i>Tambah Meja
    </button>
</div>

<!-- ─── Toolbar ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3">
    <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari nomor/nama meja…">
    </div>
</div>

<!-- ─── Info Banner ──────────────────────────────────────────── -->
<div class="alert alert-info d-flex align-items-start gap-2 mb-3"
     style="background:#f0f4ff;border-color:#c7d7fa;color:#1e40af;border-radius:var(--radius-md)">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div style="font-size:13px">
        <strong>Catatan:</strong> Data meja di sini hanya untuk referensi internal.
        QR code yang dicetak bersifat generic (satu untuk semua meja) — generate di menu
        <a href="/ordio/admin/qr-generator.php" style="color:var(--primary);font-weight:600">QR Generator</a>.
    </div>
</div>

<!-- ─── Tables Grid ──────────────────────────────────────────── -->
<div id="tableGrid" class="row g-3">
    <!-- populated by JS -->
</div>
<div id="tableEmpty" class="empty-state" style="display:none">
    <i class="bi bi-table"></i>
    <p>Belum ada data meja.<br>Klik <strong>Tambah Meja</strong> untuk mulai.</p>
</div>
<div id="tableNoResult" class="empty-state" style="display:none">
    <i class="bi bi-search"></i>
    <p>Tidak ada meja yang cocok dengan pencarian.</p>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Tambah / Edit Meja
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="tableModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tableModalLabel">Tambah Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="tableForm" novalidate>
                    <input type="hidden" id="tableId" name="id">

                    <div class="mb-3">
                        <label class="form-label" for="tableNumber">Nomor / Nama Meja *</label>
                        <input type="text" class="form-control" id="tableNumber" name="table_number"
                               placeholder="Contoh: 1, 2, VIP-A, Outdoor 3…" required>
                        <small class="text-muted-brand" style="font-size:11px">Bisa angka atau nama deskriptif</small>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" for="tableNote">Catatan (opsional)</label>
                        <input type="text" class="form-control" id="tableNote" name="note"
                               placeholder="Contoh: Pojok kiri, kapasitas 4 orang">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveTable">
                    <span class="save-text"><i class="bi bi-check-lg me-1"></i>Simpan</span>
                    <span class="save-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.table-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1.5px solid var(--border);
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    animation: fadeInCard 0.25s ease both;
}
.table-card:hover {
    box-shadow: var(--shadow-md);
    border-color: rgba(243,102,56,0.25);
    transform: translateY(-2px);
}
.table-num-badge {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-md);
    background: linear-gradient(135deg, var(--primary-light), #ffd4c0);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-heading);
    font-size: 1.1rem;
    font-weight: 800;
    flex-shrink: 0;
    border: 1.5px solid rgba(243,102,56,0.2);
}
.table-info { flex: 1; min-width: 0; }
.table-number-text {
    font-family: var(--font-heading);
    font-weight: 700;
    color: var(--charcoal);
    font-size: 1rem;
}
.table-note-text {
    font-size: 12.5px;
    color: var(--muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.table-actions { display: flex; gap: 0.3rem; }
</style>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function () {
'use strict';

let allTables   = [];
let editingId   = null;
let searchQuery = '';

const tableModal = new bootstrap.Modal(document.getElementById('tableModal'));

loadTables();

async function loadTables() {
    const res  = await fetch('/ordio/api/tables.php?action=list');
    const data = await res.json();
    allTables  = data.tables || [];
    renderGrid();
}

function renderGrid() {
    const grid    = document.getElementById('tableGrid');
    const empty   = document.getElementById('tableEmpty');
    const noResult= document.getElementById('tableNoResult');
    grid.innerHTML = '';

    let items = allTables;
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        items = items.filter(t =>
            t.table_number.toLowerCase().includes(q) ||
            (t.note || '').toLowerCase().includes(q)
        );
    }

    if (!allTables.length) {
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

    items.forEach((t, idx) => {
        const col = document.createElement('div');
        col.className = 'col-6 col-md-4 col-lg-3';
        col.style.animationDelay = (idx * 0.04) + 's';

        // Badge: pakai 2 char pertama nomor meja
        const badge = t.table_number.trim().substring(0, 3);

        col.innerHTML = `
            <div class="table-card">
                <div class="table-num-badge">${escHtml(badge)}</div>
                <div class="table-info">
                    <div class="table-number-text">Meja ${escHtml(t.table_number)}</div>
                    <div class="table-note-text">${escHtml(t.note || '—')}</div>
                </div>
                <div class="table-actions">
                    <button class="btn-icon" title="Edit" onclick="openEdit(${t.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon danger" title="Hapus" onclick="deleteTable(${t.id},'${escHtml(t.table_number)}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(col);
    });
}

function openAdd() {
    editingId = null;
    document.getElementById('tableModalLabel').textContent = 'Tambah Meja';
    document.getElementById('tableForm').reset();
    document.getElementById('tableId').value = '';
    tableModal.show();
    setTimeout(() => document.getElementById('tableNumber').focus(), 400);
}

function openEdit(id) {
    editingId = id;
    const t = allTables.find(x => x.id == id);
    if (!t) return;
    document.getElementById('tableModalLabel').textContent = 'Edit Meja';
    document.getElementById('tableId').value              = t.id;
    document.getElementById('tableNumber').value          = t.table_number;
    document.getElementById('tableNote').value            = t.note;
    tableModal.show();
    setTimeout(() => document.getElementById('tableNumber').focus(), 400);
}

async function saveTable() {
    const number = document.getElementById('tableNumber').value.trim();
    if (!number) { showToast('Nomor/nama meja wajib diisi.', 'danger'); return; }

    const saveBtn = document.getElementById('btnSaveTable');
    saveBtn.disabled = true;
    saveBtn.querySelector('.save-text').classList.add('d-none');
    saveBtn.querySelector('.save-spinner').classList.remove('d-none');

    try {
        const formData = new FormData(document.getElementById('tableForm'));
        formData.set('action', 'save');
        const res  = await fetch('/ordio/api/tables.php', { method:'POST', body: formData });
        const data = await res.json();
        if (data.ok) {
            tableModal.hide();
            showToast(editingId ? 'Meja berhasil diperbarui!' : 'Meja berhasil ditambahkan!', 'success');
            loadTables();
        } else {
            showToast(data.error, 'danger');
        }
    } catch {
        showToast('Gagal terhubung ke server.', 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.querySelector('.save-text').classList.remove('d-none');
        saveBtn.querySelector('.save-spinner').classList.add('d-none');
    }
}

function deleteTable(id, number) {
    confirmAction(`Hapus Meja "${number}"?`, async () => {
        const res  = await fetch('/ordio/api/tables.php', {
            method: 'POST',
            body:   new URLSearchParams({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast('Meja berhasil dihapus.', 'success');
            loadTables();
        } else {
            showToast(data.error, 'danger');
        }
    });
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

document.getElementById('btnAddTable').addEventListener('click', openAdd);
document.getElementById('btnSaveTable').addEventListener('click', saveTable);

document.getElementById('tableModal').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); saveTable(); }
});

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { searchQuery = this.value.trim(); renderGrid(); }, 250);
});

})();
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/admin_footer.php';
