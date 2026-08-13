</div> <!-- end .kasir-content -->

<!-- Toast notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="kasirToast" class="toast align-items-center border-0 text-bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-600" id="kasirToastBody" style="font-family:var(--font-body)"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showToast(message, type = 'success') {
    const el = document.getElementById('kasirToast');
    const body = document.getElementById('kasirToastBody');
    
    el.className = `toast align-items-center border-0 text-bg-${type}`;
    body.textContent = message;
    
    const toast = bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 });
    toast.show();
}
</script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
