<?php
/**
 * admin/reports.php
 * Halaman Cetak Laporan PDF
 * Ordio.io
 */

$pageTitle  = 'Laporan & Export';
$activePage = 'reports';
$extraHead  = '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-heading mb-1" style="font-weight:700">Laporan</h2>
        <p class="text-muted-brand mb-0" style="font-size:14px">Export rekap penjualan, keuangan, dan stok (PDF).</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form id="reportForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600">Jenis Laporan</label>
                <select class="form-select" id="reportType" required>
                    <option value="sales">Laporan Penjualan</option>
                    <option value="finance">Laporan Keuangan Laba/Rugi</option>
                    <option value="stock">Laporan Pemakaian Stok</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-weight:600">Periode</label>
                <select class="form-select" id="reportPeriod" required>
                    <option value="today">Hari Ini</option>
                    <option value="week">7 Hari Terakhir</option>
                    <option value="month">Bulan Ini</option>
                    <option value="all">Semua Waktu</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-primary w-100" onclick="generateReport()" id="btnGenerate">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Generate & Download PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template PDF yang di-hide dari UI -->
<div id="pdfContainer" style="display:none;">
    <div id="pdfContent" style="padding: 30px; font-family: 'Quicksand', sans-serif; color:#333;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #f36638; padding-bottom:15px; margin-bottom:20px;">
            <div>
                <h1 style="font-family:'Baloo 2', sans-serif; color:#f36638; margin:0; line-height:1;">Ordio.io</h1>
                <p style="margin:0; font-size:12px; color:#666;">Sistem Restoran Terpadu</p>
            </div>
            <div style="text-align:right">
                <h3 style="margin:0; font-size:16px; font-weight:700;" id="pdfTitle">JUDUL LAPORAN</h3>
                <p style="margin:0; font-size:12px; color:#666;" id="pdfPeriod">Periode: -</p>
            </div>
        </div>
        
        <div id="pdfTableContainer">
            <!-- Table will be injected here -->
        </div>
        
        <div style="margin-top: 40px; font-size: 10px; color: #999; text-align: center; border-top:1px solid #ddd; padding-top:10px;">
            Dicetak pada: <?php echo date('d M Y H:i'); ?> oleh <?php echo htmlspecialchars($user['full_name']); ?>
        </div>
    </div>
</div>

<style>
/* Style ini akan ikut di-render ke PDF */
.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.report-table th {
    background-color: #f1f5f9;
    padding: 8px 10px;
    border: 1px solid #ddd;
    text-align: left;
    font-weight: 700;
}
.report-table td {
    padding: 8px 10px;
    border: 1px solid #ddd;
}
</style>

<?php
$pageScripts = <<<'SCRIPTS'
<script>
window.generateReport = async function() {
    const type = document.getElementById('reportType').value;
    const period = document.getElementById('reportPeriod').value;
    const btn = document.getElementById('btnGenerate');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';
    
    try {
        const res = await fetch(`/ordio/api/reports.php?type=${type}&period=${period}`);
        const data = await res.json();
        
        if (data.ok) {
            // Set konten PDF
            document.getElementById('pdfTitle').innerText = data.title.toUpperCase();
            document.getElementById('pdfPeriod').innerText = "Periode: " + data.period;
            document.getElementById('pdfTableContainer').innerHTML = data.html;
            
            // Generate PDF pake html2pdf
            const element = document.getElementById('pdfContent');
            const opt = {
              margin:       [0.5, 0.5, 0.5, 0.5],
              filename:     `Ordio_${type}_${period}.pdf`,
              image:        { type: 'jpeg', quality: 0.98 },
              html2canvas:  { scale: 2 },
              jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            // Temporarily show to render, html2pdf needs it in DOM (can be off-screen or display block with opacity 0, but here we clone it or let html2pdf handle hidden elements if supported)
            // It's safer to temporarily display it off-screen
            const container = document.getElementById('pdfContainer');
            container.style.display = 'block';
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            
            await html2pdf().set(opt).from(element).save();
            
            container.style.display = 'none';
            showToast('Laporan PDF berhasil didownload.');
        } else {
            showToast(data.error, 'danger');
        }
    } catch(e) {
        showToast('Terjadi kesalahan koneksi.', 'danger');
        console.error(e);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-file-earmark-pdf me-1"></i> Generate & Download PDF';
    }
};
</script>
SCRIPTS;
require_once __DIR__ . '/../includes/admin_footer.php';
