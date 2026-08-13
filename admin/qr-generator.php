<?php
/**
 * admin/qr-generator.php
 * QR Generator — Ordio.io
 */

$pageTitle  = 'QR Generator';
$activePage = 'qr';
require_once __DIR__ . '/../includes/admin_header.php';

// Generate URL untuk order
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// Base URL asumsi ordio ada di /ordio/
$orderUrl = $protocol . "://" . $host . "/ordio/order/index.php";
?>

<!-- ─── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">QR Generator</h1>
        <p class="page-subtitle">Cetak QR Code ini untuk ditaruh di semua meja restoran.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-5">
        <div class="card p-4 text-center" style="border-radius: var(--radius-lg); border: 1.5px solid var(--border);">
            <div id="qrcode" class="d-flex justify-content-center mb-4 mt-2"></div>
            
            <h5 class="font-heading mb-1" style="color: var(--charcoal);">Scan untuk Pesan</h5>
            <p class="text-muted-brand" style="font-size: 13px; margin-bottom: 1.5rem;">
                URL: <a href="<?= htmlspecialchars($orderUrl) ?>" target="_blank" style="word-break: break-all; color: var(--primary);"><?= htmlspecialchars($orderUrl) ?></a>
            </p>
            
            <button id="btnDownload" class="btn btn-primary w-100 py-2">
                <i class="bi bi-download me-2"></i>Download PNG
            </button>
            <p class="mt-3 mb-0" style="font-size: 11px; color: var(--muted);">
                Sistem menggunakan 1 QR Code yang sama untuk semua meja. Pelanggan akan memasukkan nomor meja mereka secara manual saat memesan.
            </p>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPTS'
<!-- Load QRCode.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
'use strict';

const qrContainer = document.getElementById("qrcode");
const orderUrl = qrContainer.nextElementSibling.querySelector('a').href; // Mengambil URL aman yang dirender PHP

// Generate QR Code
const qrcode = new QRCode(qrContainer, {
    text: orderUrl,
    width: 256,
    height: 256,
    colorDark : "#1a1a1a", // Charcoal
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});

// Tunggu canvas digambar lalu bind event download
setTimeout(() => {
    const btnDownload = document.getElementById('btnDownload');
    btnDownload.addEventListener('click', function() {
        const canvas = qrContainer.querySelector('canvas');
        if (canvas) {
            const imageURI = canvas.toDataURL("image/png");
            const link = document.createElement("a");
            link.href = imageURI;
            link.download = "Ordio-QRCode.png";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            // Fallback (kalau library cuma gambar <img> tanpa <canvas>)
            const img = qrContainer.querySelector('img');
            if(img && img.src) {
                const link = document.createElement("a");
                link.href = img.src;
                link.download = "Ordio-QRCode.png";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                showToast('Gagal mengunduh QR Code', 'danger');
            }
        }
    });
}, 500);

})();
</script>
SCRIPTS;

require_once __DIR__ . '/../includes/admin_footer.php';
