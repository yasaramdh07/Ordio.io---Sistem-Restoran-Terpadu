<?php
/**
 * admin/staff.php
 * Manajemen Staff / Kasir — Ordio.io
 */

$pageTitle  = 'Manajemen Staff';
$activePage = 'staff';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Staff / Kasir</h1>
        <p class="page-subtitle">Kelola akun kasir — nonaktifkan daripada hapus agar histori pesanan terjaga</p>
    </div>
    <button class="btn btn-primary" id="btnAddStaff">
        <i class="bi bi-plus-lg me-1"></i>Tambah Kasir
    </button>
</div>

<!-- ─── Toolbar ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3">
    <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari nama atau username…">
    </div>
    <div class="ms-auto d-flex gap-2 align-items-center">
        <select class="form-select form-select-sm" id="filterStatus" style="width:auto">
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>
</div>

<!-- ─── Staff Table ───────────────────────────────────────────── -->
<div class="ing-table">
    <table>
        <thead>
            <tr>
                <th style="width:36px">#</th>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Bergabung</th>
                <th>Status</th>
                <th style="text-align:right">Aksi</th>
            </tr>
        </thead>
        <tbody id="staffTableBody"></tbody>
    </table>
    <div id="staffEmpty" class="empty-state" style="display:none">
        <i class="bi bi-people"></i>
        <p>Belum ada akun kasir.<br>Klik <strong>Tambah Kasir</strong> untuk memulai.</p>
    </div>
    <div id="staffNoResult" class="empty-state" style="display:none">
        <i class="bi bi-search"></i>
        <p>Tidak ada kasir yang cocok dengan pencarian.</p>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Tambah / Edit Kasir
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="staffModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffModalLabel">Tambah Kasir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="staffForm" novalidate>
                    <input type="hidden" id="staffId" name="id">

                    <div class="mb-3">
                        <label class="form-label" for="staffFullName">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="staffFullName" name="full_name"
                               placeholder="Contoh: Budi Santoso" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="staffUsername">Username *</label>
                        <input type="text" class="form-control" id="staffUsername" name="username"
                               placeholder="a–z, 0–9, _ (min 3 karakter)" required>
                        <small class="text-muted-brand" style="font-size:11px">
                            Hanya huruf, angka, dan underscore. Tidak bisa diubah setelah dibuat.
                        </small>
                    </div>

                    <div class="mb-3" id="passwordRow">
                        <label class="form-label" for="staffPassword">
                            Password *
                            <span id="passwordOptLabel" style="display:none;font-weight:400;color:var(--muted)">
                                (kosongkan jika tidak ingin mengubah)
                            </span>
                        </label>
                        <div style="position:relative">
                            <input type="password" class="form-control" id="staffPassword" name="password"
                                   placeholder="Min. 6 karakter" autocomplete="new-password">
                            <button type="button"
                                    style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;padding:0"
                                    onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password'; this.querySelector('i').className = this.previousElementSibling.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash'">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Status</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active"
                                       id="staffActive" value="1" checked>
                                <label class="form-check-label text-success fw-600" for="staffActive">● Aktif</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_active"
                                       id="staffInactive" value="0">
                                <label class="form-check-label text-secondary fw-600" for="staffInactive">● Nonaktif</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveStaff">
                    <span class="save-text"><i class="bi bi-check-lg me-1"></i>Simpan</span>
                    <span class="save-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Reset Password
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="resetPwModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:var(--muted)">
                    Reset password untuk: <strong id="resetTargetName">—</strong>
                </p>
                <label class="form-label" for="newPassword">Password Baru *</label>
                <input type="text" class="form-control" id="newPassword"
                       placeholder="Min. 6 karakter" autocomplete="off">
                <small class="text-muted-brand" style="font-size:11px">
                    Password baru akan langsung aktif. Informasikan ke kasir yang bersangkutan.
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="btnConfirmReset">
                    <i class="bi bi-key me-1"></i>Reset Password
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

let allStaff    = [];
let editingId   = null;
let resetingId  = null;
let searchQuery = '';
let filterStatus= 'all';

const staffModal  = new bootstrap.Modal(document.getElementById('staffModal'));
const resetPwModal= new bootstrap.Modal(document.getElementById('resetPwModal'));

// ── Init ────────────────────────────────────────────────────
loadStaff();

// ── Loader ──────────────────────────────────────────────────
async function loadStaff() {
    const params = new URLSearchParams({ action: 'list' });
    if (searchQuery) params.set('search', searchQuery);
    const res  = await fetch('/ordio/api/staff.php?' + params);
    const data = await res.json();
    allStaff   = data.staff || [];
    renderTable();
}

// ── Render ───────────────────────────────────────────────────
function renderTable() {
    const tbody   = document.getElementById('staffTableBody');
    const empty   = document.getElementById('staffEmpty');
    const noResult= document.getElementById('staffNoResult');
    tbody.innerHTML = '';

    let items = allStaff;
    if (filterStatus === 'active')   items = items.filter(s => s.is_active == 1);
    if (filterStatus === 'inactive') items = items.filter(s => s.is_active == 0);

    if (!allStaff.length) {
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

    items.forEach((s, idx) => {
        const isActive = s.is_active == 1;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="color:var(--muted);font-size:12px">${idx + 1}</td>
            <td>
                <div style="display:flex;align-items:center;gap:0.6rem">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--dark));display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                        ${escHtml(getInitials(s.full_name))}
                    </div>
                    <span class="ing-name">${escHtml(s.full_name)}</span>
                </div>
            </td>
            <td><span class="ing-unit">${escHtml(s.username)}</span></td>
            <td style="font-size:13px;color:var(--muted)">${formatDate(s.created_at)}</td>
            <td>
                ${isActive
                    ? '<span style="color:#27ae60;font-weight:700;font-size:13px">● Aktif</span>'
                    : '<span style="color:#95a5a6;font-weight:700;font-size:13px">● Nonaktif</span>'}
            </td>
            <td>
                <div class="d-flex gap-1 justify-content-end">
                    <button class="btn-icon" title="Reset Password" onclick="openReset(${s.id},'${escHtml(s.full_name)}')">
                        <i class="bi bi-key"></i>
                    </button>
                    <button class="btn-icon" title="Edit" onclick="openEdit(${s.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon ${isActive ? 'danger' : ''}"
                            title="${isActive ? 'Nonaktifkan' : 'Aktifkan kembali'}"
                            onclick="toggleStaff(${s.id},'${escHtml(s.full_name)}',${isActive})">
                        <i class="bi bi-${isActive ? 'person-dash' : 'person-check'}"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// ── Open Add ────────────────────────────────────────────────
function openAdd() {
    editingId = null;
    document.getElementById('staffModalLabel').textContent = 'Tambah Kasir';
    document.getElementById('staffForm').reset();
    document.getElementById('staffId').value = '';
    document.getElementById('staffUsername').disabled = false;
    document.getElementById('passwordOptLabel').style.display = 'none';
    document.getElementById('staffPassword').required = true;
    staffModal.show();
    setTimeout(() => document.getElementById('staffFullName').focus(), 400);
}

// ── Open Edit ────────────────────────────────────────────────
function openEdit(id) {
    editingId = id;
    const s = allStaff.find(x => x.id == id);
    if (!s) return;

    document.getElementById('staffModalLabel').textContent = 'Edit Kasir';
    document.getElementById('staffId').value              = s.id;
    document.getElementById('staffFullName').value        = s.full_name;
    document.getElementById('staffUsername').value        = s.username;
    document.getElementById('staffUsername').disabled     = false; // bisa edit username
    document.getElementById('staffPassword').value        = '';
    document.getElementById('staffPassword').required     = false;
    document.getElementById('passwordOptLabel').style.display = '';
    document.querySelector(`input[name="is_active"][value="${s.is_active}"]`).checked = true;

    staffModal.show();
    setTimeout(() => document.getElementById('staffFullName').focus(), 400);
}

// ── Save ─────────────────────────────────────────────────────
async function saveStaff() {
    const name = document.getElementById('staffFullName').value.trim();
    const user = document.getElementById('staffUsername').value.trim();
    const pass = document.getElementById('staffPassword').value;

    if (!name) { showToast('Nama lengkap wajib diisi.', 'danger'); return; }
    if (!user) { showToast('Username wajib diisi.', 'danger');     return; }
    if (!editingId && pass.length < 6) {
        showToast('Password minimal 6 karakter.', 'danger');
        return;
    }
    if (editingId && pass !== '' && pass.length < 6) {
        showToast('Password minimal 6 karakter.', 'danger');
        return;
    }

    const saveBtn = document.getElementById('btnSaveStaff');
    saveBtn.disabled = true;
    saveBtn.querySelector('.save-text').classList.add('d-none');
    saveBtn.querySelector('.save-spinner').classList.remove('d-none');

    try {
        const formData = new FormData(document.getElementById('staffForm'));
        formData.set('action', 'save');

        const res  = await fetch('/ordio/api/staff.php', { method:'POST', body: formData });
        const data = await res.json();

        if (data.ok) {
            staffModal.hide();
            showToast(editingId ? 'Kasir berhasil diperbarui!' : 'Kasir berhasil ditambahkan!', 'success');
            loadStaff();
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

// ── Toggle Active ────────────────────────────────────────────
function toggleStaff(id, name, isActive) {
    const verb = isActive ? 'nonaktifkan' : 'aktifkan kembali';
    confirmAction(`${verb.charAt(0).toUpperCase() + verb.slice(1)} akun "${name}"?`, async () => {
        const res  = await fetch('/ordio/api/staff.php', {
            method: 'POST',
            body:   new URLSearchParams({ action: 'toggle', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast(`Akun "${name}" berhasil di${verb}.`, 'success');
            loadStaff();
        } else {
            showToast(data.error, 'danger');
        }
    });
}

// ── Reset Password ────────────────────────────────────────────
function openReset(id, name) {
    resetingId = id;
    document.getElementById('resetTargetName').textContent = name;
    document.getElementById('newPassword').value = '';
    resetPwModal.show();
    setTimeout(() => document.getElementById('newPassword').focus(), 400);
}

async function confirmReset() {
    const newPw = document.getElementById('newPassword').value;
    if (newPw.length < 6) {
        showToast('Password baru minimal 6 karakter.', 'danger');
        return;
    }
    const res  = await fetch('/ordio/api/staff.php', {
        method: 'POST',
        body:   new URLSearchParams({ action: 'reset_password', id: resetingId, new_password: newPw })
    });
    const data = await res.json();
    if (data.ok) {
        resetPwModal.hide();
        showToast('Password berhasil direset!', 'success');
    } else {
        showToast(data.error, 'danger');
    }
}

// ── Utilities ────────────────────────────────────────────────
function getInitials(name) {
    return name.trim().split(/\s+/).slice(0,2).map(w => w[0].toUpperCase()).join('');
}
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
}

// ── Events ───────────────────────────────────────────────────
document.getElementById('btnAddStaff').addEventListener('click', openAdd);
document.getElementById('btnSaveStaff').addEventListener('click', saveStaff);
document.getElementById('btnConfirmReset').addEventListener('click', confirmReset);
document.getElementById('filterStatus').addEventListener('change', function () {
    filterStatus = this.value;
    renderTable();
});

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = this.value.trim();
        loadStaff();
    }, 350);
});

document.getElementById('staffModal').addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.target.matches('textarea')) {
        e.preventDefault();
        saveStaff();
    }
});

document.getElementById('resetPwModal').addEventListener('keydown', e => {
    if (e.key === 'Enter') confirmReset();
});

})();
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/admin_footer.php';
