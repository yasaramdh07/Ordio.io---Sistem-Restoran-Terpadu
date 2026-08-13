<?php
/**
 * admin/finance.php
 * Halaman Keuangan (Input Pengeluaran, Laba Rugi)
 * Ordio.io
 */

$pageTitle  = 'Keuangan';
$activePage = 'finance';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading mb-1" style="font-weight:700">Keuangan</h2>
        <p class="text-muted-brand mb-0" style="font-size:14px">Catat pengeluaran dan pantau laba rugi.</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalExpense">
            <i class="bi bi-plus-lg me-1"></i> Catat Pengeluaran
        </button>
    </div>
</div>

<!-- ─── Ringkasan ────────────────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-12 mb-3">
        <select class="form-select w-auto d-inline-block" id="filterSummary" style="font-weight:600">
            <option value="today">Hari Ini</option>
            <option value="week">7 Hari Terakhir</option>
            <option value="month">Bulan Ini</option>
            <option value="all">Semua Waktu</option>
        </select>
    </div>
    
    <div class="col-md-4">
        <div class="card p-4 border-0 shadow-sm rounded-4 mb-3" style="background:#e8f5e9">
            <div class="text-muted mb-1" style="font-size:13px;font-weight:700">TOTAL PEMASUKAN</div>
            <h3 class="font-heading m-0" style="color:#2e7d32;font-weight:800" id="valIncome">Rp 0</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 border-0 shadow-sm rounded-4 mb-3" style="background:#ffebee">
            <div class="text-muted mb-1" style="font-size:13px;font-weight:700">TOTAL PENGELUARAN</div>
            <h3 class="font-heading m-0" style="color:#c62828;font-weight:800" id="valExpense">Rp 0</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 border-0 shadow-sm rounded-4 mb-3" style="background:#e3f2fd">
            <div class="text-muted mb-1" style="font-size:13px;font-weight:700">LABA BERSIH</div>
            <h3 class="font-heading m-0" style="color:#1565c0;font-weight:800" id="valProfit">Rp 0</h3>
        </div>
    </div>
</div>

<!-- ─── Tabel Riwayat Transaksi ──────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-heading" style="font-weight:700">Riwayat Transaksi</h5>
        </div>
        <div class="ing-table">
            <table class="w-100">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Nominal</th>
                    </tr>
                </thead>
                <tbody id="financeTbody">
                    <tr><td colspan="5" class="text-center py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Modal Input Pengeluaran ──────────────────────────────── -->
<div class="modal fade" id="modalExpense" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-bottom-0 pb-0">
        <h1 class="modal-title fs-5 font-heading" style="font-weight:700">Catat Pengeluaran</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formExpense">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600;font-size:14px">Kategori</label>
                <input type="text" class="form-control" name="category" placeholder="Contoh: Belanja Pasar" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-weight:600;font-size:14px">Nominal (Rp)</label>
                <input type="number" class="form-control" name="amount" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-weight:600;font-size:14px">Deskripsi Tambahan</label>
                <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btnSubmitExpense" onclick="submitExpense()">
            <span class="btn-text">Simpan Pengeluaran</span>
            <span class="btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
(function(){
'use strict';

const filterSummary = document.getElementById('filterSummary');
const valIncome = document.getElementById('valIncome');
const valExpense = document.getElementById('valExpense');
const valProfit = document.getElementById('valProfit');
const tbody = document.getElementById('financeTbody');

async function loadData() {
    const filter = filterSummary.value;
    try {
        // Load Summary
        const resSum = await fetch(`/ordio/api/finance.php?action=summary&filter=${filter}`);
        const dataSum = await resSum.json();
        if(dataSum.ok) {
            valIncome.textContent = formatRupiah(dataSum.income);
            valExpense.textContent = formatRupiah(dataSum.expense);
            valProfit.textContent = formatRupiah(dataSum.profit);
            valProfit.style.color = dataSum.profit < 0 ? '#c62828' : '#1565c0';
        }

        // Load List
        const resList = await fetch(`/ordio/api/finance.php?action=list&filter=${filter}`);
        const dataList = await resList.json();
        if(dataList.ok) {
            tbody.innerHTML = '';
            if(dataList.records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi.</td></tr>';
            } else {
                dataList.records.forEach(r => {
                    const time = new Date(r.created_at.replace(' ', 'T')).toLocaleString('id-ID', {day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
                    
                    let typeBadge = r.type === 'income' 
                        ? '<span class="badge text-bg-success" style="font-size:11px"><i class="bi bi-arrow-down-left"></i> IN</span>' 
                        : '<span class="badge text-bg-danger" style="font-size:11px"><i class="bi bi-arrow-up-right"></i> OUT</span>';
                        
                    let color = r.type === 'income' ? 'var(--primary)' : '#c62828';
                    
                    tbody.innerHTML += `
                        <tr>
                            <td style="color:var(--muted);font-size:13px">${time}</td>
                            <td>${typeBadge}</td>
                            <td style="font-weight:600">${escHtml(r.category)}</td>
                            <td style="font-size:13px;color:var(--muted)">${escHtml(r.description)}</td>
                            <td class="text-end" style="font-weight:700;color:${color}">${formatRupiah(r.amount)}</td>
                        </tr>
                    `;
                });
            }
        }
    } catch(e) {
        showToast('Koneksi error', 'danger');
    }
}

window.submitExpense = async function() {
    const form = document.getElementById('formExpense');
    if(!form.checkValidity()) { form.reportValidity(); return; }
    
    const btn = document.getElementById('btnSubmitExpense');
    const text = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.btn-spinner');
    
    btn.disabled = true;
    text.classList.add('d-none');
    spinner.classList.remove('d-none');
    
    const formData = new FormData(form);
    formData.append('action', 'add_expense');
    
    try {
        const res = await fetch('/ordio/api/finance.php', { method: 'POST', body: formData });
        const data = await res.json();
        if(data.ok) {
            showToast(data.msg);
            bootstrap.Modal.getInstance(document.getElementById('modalExpense')).hide();
            form.reset();
            loadData();
        } else {
            showToast(data.error, 'danger');
        }
    } catch(e) { 
        showToast('Koneksi error', 'danger'); 
    } finally {
        btn.disabled = false;
        text.classList.remove('d-none');
        spinner.classList.add('d-none');
    }
};

filterSummary.addEventListener('change', loadData);

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}

loadData();
})();
</script>
SCRIPTS;
require_once __DIR__ . '/../includes/admin_footer.php';
