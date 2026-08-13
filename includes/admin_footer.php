    </div><!-- .admin-content -->
</div><!-- .admin-main -->

<!-- Toast notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="adminToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-600" id="adminToastBody" style="font-family:var(--font-body)"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Sidebar toggle ──────────────────────────────────────────
(function () {
    const sidebar  = document.getElementById('adminSidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const isMobile = () => window.innerWidth < 992;

    function openSidebar() {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('active');
    }
    function closeSidebar() {
        sidebar.classList.remove('mobile-open', 'collapsed');
        overlay.classList.remove('active');
    }

    toggleBtn.addEventListener('click', function () {
        if (isMobile()) {
            sidebar.classList.contains('mobile-open') ? closeSidebar() : openSidebar();
        } else {
            sidebar.classList.toggle('collapsed');
        }
    });

    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
    });
})();

// ── Toast helper ────────────────────────────────────────────
function showToast(message, type = 'success') {
    const el   = document.getElementById('adminToast');
    const body = document.getElementById('adminToastBody');

    const colorMap = {
        success: 'text-bg-success',
        danger:  'text-bg-danger',
        warning: 'text-bg-warning',
        info:    'text-bg-primary',
    };

    // Reset classes
    el.className = 'toast align-items-center border-0 ' + (colorMap[type] || colorMap.info);
    body.textContent = message;

    const toast = bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 });
    toast.show();
}

// ── Confirm dialog helper ───────────────────────────────────
function confirmAction(message, callback) {
    if (window.confirm(message)) callback();
}

// ── Format Rupiah ───────────────────────────────────────────
function formatRupiah(val) {
    return 'Rp ' + Number(val).toLocaleString('id-ID');
}
</script>

<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
