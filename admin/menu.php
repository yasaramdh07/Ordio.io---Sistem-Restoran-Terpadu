<?php
/**
 * admin/menu.php
 * Manajemen Menu — Ordio.io
 */

$pageTitle  = 'Manajemen Menu';
$activePage = 'menu';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Menu</h1>
        <p class="page-subtitle">Kelola daftar menu, harga, bahan baku & opsi pesanan</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" id="btnManageCategories">
            <i class="bi bi-tags me-1"></i>Kategori
        </button>
        <button class="btn btn-primary" id="btnAddMenu">
            <i class="bi bi-plus-lg me-1"></i>Tambah Menu
        </button>
    </div>
</div>

<!-- ─── Toolbar ──────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <!-- Search -->
    <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Cari menu…">
    </div>

    <!-- Category Tabs -->
    <div class="filter-tabs" id="categoryTabs">
        <button class="filter-tab active" data-cat-id="">Semua</button>
    </div>
</div>

<!-- ─── Menu Grid ─────────────────────────────────────────────── -->
<div id="menuGrid" class="menu-grid">
    <!-- populated by JS -->
</div>
<div id="menuEmpty" class="empty-state" style="display:none">
    <i class="bi bi-journal-x"></i>
    <p>Belum ada menu yang ditambahkan.<br>Klik <strong>Tambah Menu</strong> untuk mulai.</p>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Tambah / Edit Menu
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="menuModalLabel">Tambah Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Custom Tabs -->
            <div class="modal-tabs">
                <button class="modal-tab-btn active" data-tab="tab-info">
                    <i class="bi bi-info-circle me-1"></i>Informasi
                </button>
                <button class="modal-tab-btn" data-tab="tab-ingredients">
                    <i class="bi bi-box-seam me-1"></i>Bahan Baku
                </button>
                <button class="modal-tab-btn" data-tab="tab-options">
                    <i class="bi bi-sliders me-1"></i>Opsi Menu
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body" style="padding-top:1rem">
                <form id="menuForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="menuId" name="id" value="">
                    <input type="hidden" id="existingImage" name="existing_image" value="">
                    <input type="hidden" name="ingredients_json" id="ingredientsJson">
                    <input type="hidden" name="options_json" id="optionsJson">

                    <!-- ── Tab 1: Informasi Dasar ── -->
                    <div class="modal-tab-pane active" id="tab-info">
                        <div class="row g-3">

                            <!-- Nama + Kategori -->
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="menuName">Nama Menu *</label>
                                <input type="text" class="form-control" id="menuName" name="name"
                                       placeholder="Contoh: Nasi Goreng Spesial" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="menuCategory">Kategori</label>
                                <select class="form-select" id="menuCategory" name="category_id">
                                    <option value="">— Tanpa Kategori —</option>
                                </select>
                            </div>

                            <!-- Harga + Status -->
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="menuPrice">Harga (Rp) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="menuPrice" name="price"
                                           placeholder="0" min="0" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Status</label>
                                <div class="d-flex gap-3 pt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_active"
                                               id="statusActive" value="1" checked>
                                        <label class="form-check-label" for="statusActive">
                                            <span class="text-success fw-600">● Aktif</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_active"
                                               id="statusInactive" value="0">
                                        <label class="form-check-label" for="statusInactive">
                                            <span class="text-secondary fw-600">● Nonaktif</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div class="col-12">
                                <label class="form-label" for="menuDesc">Deskripsi</label>
                                <textarea class="form-control" id="menuDesc" name="description"
                                          rows="2" placeholder="Deskripsi singkat menu…"></textarea>
                            </div>

                            <!-- Foto -->
                            <div class="col-12">
                                <label class="form-label">Foto Menu</label>
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="img-preview-wrap" id="imgPreviewWrap" style="max-width:160px;flex-shrink:0"
                                         onclick="document.getElementById('menuImage').click()">
                                        <div class="img-preview-placeholder" id="imgPlaceholder">
                                            <i class="bi bi-image-alt"></i>
                                            <span>Klik untuk upload</span>
                                        </div>
                                        <img id="imgPreview" style="display:none;width:100%;height:100%;object-fit:cover" alt="Preview">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" id="menuImage" name="image"
                                               accept="image/jpeg,image/png,image/webp" style="display:none">
                                        <p class="text-muted-brand" style="font-size:12px;margin:0">
                                            Format: JPG, PNG, WebP<br>Ukuran maks: 3MB<br>
                                            Rasio ideal: 4:3 (misal 800×600px)
                                        </p>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                                onclick="document.getElementById('menuImage').click()">
                                            <i class="bi bi-upload me-1"></i>Pilih Foto
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 ms-1"
                                                id="btnRemoveImage" style="display:none"
                                                onclick="removeImage()">
                                            <i class="bi bi-x me-1"></i>Hapus Foto
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div><!-- row -->
                    </div><!-- tab-info -->

                    <!-- ── Tab 2: Bahan Baku ── -->
                    <div class="modal-tab-pane" id="tab-ingredients">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0 font-heading">Bahan Baku yang Dipakai</h6>
                                <small class="text-muted-brand">Masukkan bahan & qty per satu porsi</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddIngRow">
                                <i class="bi bi-plus me-1"></i>Tambah Bahan
                            </button>
                        </div>
                        <div id="ingredientRows">
                            <!-- dynamic rows -->
                        </div>
                        <div id="ingEmptyMsg" class="text-center py-4" style="color:var(--muted);font-size:13px">
                            <i class="bi bi-box-seam d-block mb-2" style="font-size:2rem;opacity:0.3"></i>
                            Belum ada bahan baku ditambahkan
                        </div>
                    </div><!-- tab-ingredients -->

                    <!-- ── Tab 3: Opsi Menu ── -->
                    <div class="modal-tab-pane" id="tab-options">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0 font-heading">Opsi Pesanan</h6>
                                <small class="text-muted-brand">Contoh: Level Pedas, Ukuran Porsi, Topping</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddOption">
                                <i class="bi bi-plus me-1"></i>Tambah Opsi
                            </button>
                        </div>
                        <div id="optionGroups">
                            <!-- dynamic groups -->
                        </div>
                        <div id="optEmptyMsg" class="text-center py-4" style="color:var(--muted);font-size:13px">
                            <i class="bi bi-sliders d-block mb-2" style="font-size:2rem;opacity:0.3"></i>
                            Belum ada opsi ditambahkan
                        </div>
                    </div><!-- tab-options -->

                </form>
            </div><!-- modal-body -->

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveMenu">
                    <span class="save-text"><i class="bi bi-check-lg me-1"></i>Simpan</span>
                    <span class="save-spinner d-none">
                        <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…
                    </span>
                </button>
            </div>

        </div><!-- modal-content -->
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL: Kelola Kategori
════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kelola Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-3">
                    <input type="text" class="form-control form-control-sm" id="newCatName" placeholder="Nama kategori baru…">
                    <button class="btn btn-primary btn-sm" id="btnAddCategory">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <ul class="list-group list-group-flush" id="categoryList" style="border-radius:var(--radius-md);overflow:hidden;border:1.5px solid var(--border)">
                    <!-- dynamic -->
                </ul>
                <div id="catEmptyMsg" class="text-center py-3" style="color:var(--muted);font-size:13px">
                    Belum ada kategori
                </div>
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
let allMenus        = [];
let categories      = [];
let ingredientsMaster = []; // semua bahan baku
let activeCatId     = '';
let searchQuery     = '';
let editingMenuId   = null;

const menuModal     = new bootstrap.Modal(document.getElementById('menuModal'));
const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));

// ── Init ────────────────────────────────────────────────────
async function init() {
    await Promise.all([loadCategories(), loadIngredientsMaster()]);
    await loadMenus();
}

// ── Data loaders ────────────────────────────────────────────
async function loadCategories() {
    const res  = await fetch('/ordio/api/menus.php?action=categories');
    const data = await res.json();
    categories = data.categories || [];
    renderCategoryTabs();
    renderCategorySelect();
    renderCategoryModalList();
}

async function loadIngredientsMaster() {
    const res  = await fetch('/ordio/api/menus.php?action=ingredients_list');
    const data = await res.json();
    ingredientsMaster = data.ingredients || [];
}

async function loadMenus() {
    const params = new URLSearchParams({ action: 'list' });
    if (activeCatId) params.set('category_id', activeCatId);
    if (searchQuery)  params.set('search', searchQuery);

    const res  = await fetch('/ordio/api/menus.php?' + params);
    const data = await res.json();
    allMenus   = data.menus || [];
    renderMenuGrid();
}

// ── Render helpers ───────────────────────────────────────────
function renderCategoryTabs() {
    const container = document.getElementById('categoryTabs');
    // Keep the "Semua" button, re-add others
    container.innerHTML = `<button class="filter-tab ${activeCatId === '' ? 'active' : ''}" data-cat-id="">Semua</button>`;
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'filter-tab' + (activeCatId == cat.id ? ' active' : '');
        btn.dataset.catId = cat.id;
        btn.textContent = cat.name;
        container.appendChild(btn);
    });
    container.querySelectorAll('.filter-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            activeCatId = btn.dataset.catId;
            container.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadMenus();
        });
    });
}

function renderCategorySelect() {
    const sel = document.getElementById('menuCategory');
    const cur = sel.value;
    sel.innerHTML = '<option value="">— Tanpa Kategori —</option>';
    categories.forEach(cat => {
        const opt = new Option(cat.name, cat.id);
        sel.appendChild(opt);
    });
    sel.value = cur;
}

function renderCategoryModalList() {
    const ul  = document.getElementById('categoryList');
    const msg = document.getElementById('catEmptyMsg');
    ul.innerHTML = '';
    if (!categories.length) { msg.style.display=''; return; }
    msg.style.display = 'none';
    categories.forEach(cat => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center justify-content-between py-2 px-3';
        li.style.fontFamily = 'var(--font-body)';
        li.style.fontSize   = '14px';
        li.innerHTML = `
            <span>${escHtml(cat.name)}</span>
            <button class="btn-icon danger" onclick="deleteCategory(${cat.id},'${escHtml(cat.name)}')">
                <i class="bi bi-trash"></i>
            </button>
        `;
        ul.appendChild(li);
    });
}

function renderMenuGrid() {
    const grid  = document.getElementById('menuGrid');
    const empty = document.getElementById('menuEmpty');
    grid.innerHTML = '';

    if (!allMenus.length) {
        empty.style.display = '';
        return;
    }
    empty.style.display = 'none';

    allMenus.forEach((m, idx) => {
        const card = document.createElement('div');
        card.className = 'menu-card';
        card.style.animationDelay = (idx * 0.05) + 's';

        const imgHtml = m.image_path
            ? `<img class="menu-card-img" src="/ordio/${escHtml(m.image_path)}?v=${Date.now()}" alt="${escHtml(m.name)}" loading="lazy">`
            : `<div class="menu-card-img-placeholder"><i class="bi bi-image-alt"></i></div>`;

        card.innerHTML = `
            <div style="position:relative">
                ${imgHtml}
                <span class="menu-card-status ${m.is_active == 1 ? 'active' : 'inactive'}" title="${m.is_active == 1 ? 'Aktif' : 'Nonaktif'}"></span>
            </div>
            <div class="menu-card-body">
                <div class="menu-card-category">${escHtml(m.category_name || 'Tanpa Kategori')}</div>
                <h3 class="menu-card-name">${escHtml(m.name)}</h3>
                <p class="menu-card-desc">${escHtml(m.description || '—')}</p>
                <div class="menu-card-price">${formatRupiah(m.price)}</div>
            </div>
            <div class="menu-card-footer">
                <label class="toggle-switch" title="${m.is_active == 1 ? 'Nonaktifkan' : 'Aktifkan'}">
                    <input type="checkbox" ${m.is_active == 1 ? 'checked' : ''} onchange="toggleMenu(${m.id}, this)">
                    <span class="toggle-slider"></span>
                </label>
                <div class="ms-auto d-flex gap-1">
                    <button class="btn-icon" title="Edit" onclick="openEdit(${m.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon danger" title="Hapus" onclick="deleteMenu(${m.id},'${escHtml(m.name)}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// ── Modal: Open Add ──────────────────────────────────────────
function openAdd() {
    editingMenuId = null;
    document.getElementById('menuModalLabel').textContent = 'Tambah Menu';
    resetForm();
    switchTab('tab-info');
    menuModal.show();
}

// ── Modal: Open Edit ─────────────────────────────────────────
async function openEdit(id) {
    editingMenuId = id;
    document.getElementById('menuModalLabel').textContent = 'Edit Menu';
    resetForm();
    switchTab('tab-info');

    try {
        const res  = await fetch(`/ordio/api/menus.php?action=get&id=${id}`);
        const data = await res.json();
        if (!data.ok) { showToast(data.error, 'danger'); return; }

        const m = data.menu;
        document.getElementById('menuId').value       = m.id;
        document.getElementById('menuName').value     = m.name;
        document.getElementById('menuDesc').value     = m.description;
        document.getElementById('menuPrice').value    = m.price;
        document.getElementById('menuCategory').value = m.category_id || '';
        document.getElementById('existingImage').value= m.image_path;
        document.querySelector(`input[name="is_active"][value="${m.is_active}"]`).checked = true;

        if (m.image_path) {
            document.getElementById('imgPreview').src          = '/ordio/' + m.image_path;
            document.getElementById('imgPreview').style.display = 'block';
            document.getElementById('imgPlaceholder').style.display = 'none';
            document.getElementById('btnRemoveImage').style.display = '';
        }

        // Render bahan baku
        (data.ingredients || []).forEach(ing => {
            addIngredientRow(ing.ingredient_id, ing.qty_used, ing.ing_name, ing.unit);
        });

        // Render options
        (data.options || []).forEach(opt => {
            addOptionGroup(opt.option_name, opt.is_required, opt.values);
        });

        menuModal.show();
    } catch (e) {
        showToast('Gagal memuat data menu.', 'danger');
    }
}

// ── Reset Form ───────────────────────────────────────────────
function resetForm() {
    document.getElementById('menuForm').reset();
    document.getElementById('menuId').value = '';
    document.getElementById('existingImage').value = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPlaceholder').style.display = '';
    document.getElementById('btnRemoveImage').style.display = 'none';
    document.getElementById('ingredientRows').innerHTML = '';
    document.getElementById('optionGroups').innerHTML   = '';
    updateIngEmptyMsg();
    updateOptEmptyMsg();
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(tabId) {
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabId);
    });
    document.querySelectorAll('.modal-tab-pane').forEach(pane => {
        pane.classList.toggle('active', pane.id === tabId);
    });
}

// ── Ingredient rows ───────────────────────────────────────────
function addIngredientRow(ingId = '', qty = '', name = '', unit = '') {
    const row = document.createElement('div');
    row.className = 'ing-form-row';
    const options = ingredientsMaster.map(i =>
        `<option value="${i.id}" ${i.id == ingId ? 'selected' : ''}>${escHtml(i.name)} (${escHtml(i.unit)})</option>`
    ).join('');

    const unitLabel = ingId ? (ingredientsMaster.find(i => i.id == ingId)?.unit || '') : '';

    row.innerHTML = `
        <select class="form-select form-select-sm ing-select" style="flex:2">
            <option value="">Pilih bahan…</option>
            ${options}
        </select>
        <input type="number" class="form-control form-control-sm qty-input" style="flex:1;min-width:80px"
               placeholder="Qty" value="${qty}" step="0.01" min="0">
        <span class="unit-label ing-unit-label">${escHtml(unitLabel)}</span>
        <button type="button" style="background:none;border:none;color:var(--muted);cursor:pointer;padding:0.2rem"
                onclick="this.closest('.ing-form-row').remove();updateIngEmptyMsg()">
            <i class="bi bi-x-circle"></i>
        </button>
    `;

    // Update unit label on select change
    row.querySelector('.ing-select').addEventListener('change', function () {
        const found = ingredientsMaster.find(i => i.id == this.value);
        row.querySelector('.ing-unit-label').textContent = found ? found.unit : '';
    });

    document.getElementById('ingredientRows').appendChild(row);
    updateIngEmptyMsg();
}

function updateIngEmptyMsg() {
    const rows = document.querySelectorAll('#ingredientRows .ing-form-row');
    document.getElementById('ingEmptyMsg').style.display = rows.length ? 'none' : '';
}

function collectIngredients() {
    return Array.from(document.querySelectorAll('#ingredientRows .ing-form-row')).map(row => ({
        ingredient_id: row.querySelector('.ing-select').value,
        qty:           parseFloat(row.querySelector('.qty-input').value) || 0,
    })).filter(r => r.ingredient_id && r.qty > 0);
}

// ── Option groups ─────────────────────────────────────────────
let optGroupCounter = 0;

function addOptionGroup(name = '', isRequired = 0, values = []) {
    const idx   = optGroupCounter++;
    const group = document.createElement('div');
    group.className  = 'option-group-card';
    group.dataset.idx = idx;

    group.innerHTML = `
        <div class="option-group-header">
            <input type="text" class="form-control form-control-sm opt-name"
                   placeholder="Nama opsi (contoh: Level Pedas)" value="${escHtml(name)}" style="flex:1">
            <select class="form-select form-select-sm opt-required" style="width:120px">
                <option value="0" ${!isRequired ? 'selected':''}>Opsional</option>
                <option value="1" ${isRequired ? 'selected':''}>Wajib pilih</option>
            </select>
            <button type="button" style="background:none;border:none;color:#c0392b;cursor:pointer;padding:0.2rem;font-size:1rem"
                    onclick="this.closest('.option-group-card').remove();updateOptEmptyMsg()">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="values-container ps-1"></div>
        <button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="addValueRow(this)">
            <i class="bi bi-plus me-1"></i>Tambah Value
        </button>
    `;

    document.getElementById('optionGroups').appendChild(group);

    // Add existing values
    if (values && values.length) {
        const btn = group.querySelector('button[onclick*="addValueRow"]');
        values.forEach(v => addValueRow(btn, v.value_name, v.extra_price));
    } else {
        // Add one empty row to start
        const btn = group.querySelector('button[onclick*="addValueRow"]');
        addValueRow(btn);
    }

    updateOptEmptyMsg();
}

function addValueRow(btn, name = '', extraPrice = 0) {
    const container = btn.closest('.option-group-card').querySelector('.values-container');
    const row = document.createElement('div');
    row.className = 'option-value-row';
    row.innerHTML = `
        <input type="text" class="form-control form-control-sm val-name"
               placeholder="Nama value (contoh: Level 1)" value="${escHtml(name)}" style="flex:1">
        <div class="input-group input-group-sm" style="width:150px">
            <span class="input-group-text">+Rp</span>
            <input type="number" class="form-control val-price" placeholder="0" value="${extraPrice}" min="0">
        </div>
        <button type="button" style="background:none;border:none;color:var(--muted);cursor:pointer;padding:0.2rem"
                onclick="this.closest('.option-value-row').remove()">
            <i class="bi bi-dash-circle"></i>
        </button>
    `;
    container.appendChild(row);
}

function updateOptEmptyMsg() {
    const groups = document.querySelectorAll('#optionGroups .option-group-card');
    document.getElementById('optEmptyMsg').style.display = groups.length ? 'none' : '';
}

function collectOptions() {
    return Array.from(document.querySelectorAll('#optionGroups .option-group-card')).map(group => ({
        option_name: group.querySelector('.opt-name').value.trim(),
        is_required: parseInt(group.querySelector('.opt-required').value),
        values: Array.from(group.querySelectorAll('.option-value-row')).map(row => ({
            value_name:  row.querySelector('.val-name').value.trim(),
            extra_price: parseInt(row.querySelector('.val-price').value) || 0,
        })).filter(v => v.value_name),
    })).filter(g => g.option_name);
}

// ── Image handling ────────────────────────────────────────────
function removeImage() {
    document.getElementById('menuImage').value = '';
    document.getElementById('existingImage').value = '';
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPlaceholder').style.display = '';
    document.getElementById('btnRemoveImage').style.display = 'none';
}

document.getElementById('menuImage').addEventListener('change', function () {
    if (!this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imgPreview').src = e.target.result;
        document.getElementById('imgPreview').style.display = 'block';
        document.getElementById('imgPlaceholder').style.display = 'none';
        document.getElementById('btnRemoveImage').style.display = '';
    };
    reader.readAsDataURL(this.files[0]);
});

// ── Save Menu ─────────────────────────────────────────────────
async function saveMenu() {
    const name  = document.getElementById('menuName').value.trim();
    const price = document.getElementById('menuPrice').value;
    if (!name)  { showToast('Nama menu wajib diisi.', 'danger'); switchTab('tab-info'); return; }
    if (!price) { showToast('Harga wajib diisi.', 'danger'); switchTab('tab-info'); return; }

    // Inject JSON fields
    document.getElementById('ingredientsJson').value = JSON.stringify(collectIngredients());
    document.getElementById('optionsJson').value     = JSON.stringify(collectOptions());

    const saveBtn    = document.getElementById('btnSaveMenu');
    const saveText   = saveBtn.querySelector('.save-text');
    const saveSpinner= saveBtn.querySelector('.save-spinner');

    saveBtn.disabled = true;
    saveText.classList.add('d-none');
    saveSpinner.classList.remove('d-none');

    try {
        const formData = new FormData(document.getElementById('menuForm'));
        formData.set('action', 'save');

        const res  = await fetch('/ordio/api/menus.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.ok) {
            menuModal.hide();
            showToast(editingMenuId ? 'Menu berhasil diperbarui!' : 'Menu berhasil ditambahkan!', 'success');
            loadMenus();
        } else {
            showToast(data.error || 'Terjadi kesalahan.', 'danger');
        }
    } catch (e) {
        showToast('Gagal terhubung ke server.', 'danger');
    } finally {
        saveBtn.disabled = false;
        saveText.classList.remove('d-none');
        saveSpinner.classList.add('d-none');
    }
}

// ── Toggle menu active ────────────────────────────────────────
async function toggleMenu(id, checkbox) {
    const res  = await fetch('/ordio/api/menus.php', {
        method: 'POST',
        body:   new URLSearchParams({ action: 'toggle', id })
    });
    const data = await res.json();
    if (!data.ok) {
        checkbox.checked = !checkbox.checked; // revert
        showToast(data.error, 'danger');
    } else {
        const statusDot = checkbox.closest('.menu-card').querySelector('.menu-card-status');
        statusDot.className = 'menu-card-status ' + (data.is_active ? 'active' : 'inactive');
    }
}

// ── Delete menu ───────────────────────────────────────────────
function deleteMenu(id, name) {
    confirmAction(`Hapus menu "${name}"?\nTindakan ini tidak bisa dibatalkan.`, async () => {
        const res  = await fetch('/ordio/api/menus.php', {
            method: 'POST',
            body:   new URLSearchParams({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.ok) {
            showToast('Menu berhasil dihapus.', 'success');
            loadMenus();
        } else {
            showToast(data.error, 'danger');
        }
    });
}

// ── Category management ───────────────────────────────────────
async function addCategory() {
    const input = document.getElementById('newCatName');
    const name  = input.value.trim();
    if (!name) return;

    const res  = await fetch('/ordio/api/menus.php', {
        method: 'POST',
        body:   new URLSearchParams({ action: 'save_category', name })
    });
    const data = await res.json();
    if (data.ok) {
        input.value = '';
        await loadCategories();
        showToast('Kategori ditambahkan!', 'success');
    } else {
        showToast(data.error, 'danger');
    }
}

async function deleteCategory(id, name) {
    confirmAction(`Hapus kategori "${name}"?\nMenu yang menggunakan kategori ini tidak akan dihapus.`, async () => {
        const res  = await fetch('/ordio/api/menus.php', {
            method: 'POST',
            body:   new URLSearchParams({ action: 'delete_category', id })
        });
        const data = await res.json();
        if (data.ok) {
            await loadCategories();
            showToast('Kategori dihapus.', 'success');
        } else {
            showToast(data.error, 'danger');
        }
    });
}

// ── Utility ───────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

// ── Event Listeners ───────────────────────────────────────────
document.getElementById('btnAddMenu').addEventListener('click', openAdd);
document.getElementById('btnSaveMenu').addEventListener('click', saveMenu);
document.getElementById('btnAddIngRow').addEventListener('click', () => addIngredientRow());
document.getElementById('btnAddOption').addEventListener('click', () => addOptionGroup());
document.getElementById('btnManageCategories').addEventListener('click', () => categoryModal.show());
document.getElementById('btnAddCategory').addEventListener('click', addCategory);
document.getElementById('newCatName').addEventListener('keydown', e => { if (e.key === 'Enter') addCategory(); });

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searchQuery = this.value.trim();
        loadMenus();
    }, 350);
});

document.querySelectorAll('.modal-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

// ── Start ─────────────────────────────────────────────────────
init();

})(); // end IIFE
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/admin_footer.php';
