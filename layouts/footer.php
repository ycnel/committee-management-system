<?php
/**
 * layouts/footer.php
 * ------------------------------------------------------------------
 * Shared footer + JS includes. Closes the .main-content wrapper that
 * layouts/sidebar.php's including page is expected to have opened.
 * ------------------------------------------------------------------
 */
?>
  </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<script src="<?= e(vendorAsset('bootstrap/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(vendorAsset('jquery/jquery-3.7.1.min.js', 'https://code.jquery.com/jquery-3.7.1.min.js')) ?>"></script>
<script src="<?= e(vendorAsset('datatables/jquery.dataTables.min.js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js')) ?>"></script>
<script src="<?= e(vendorAsset('datatables/dataTables.bootstrap5.min.js', 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js')) ?>"></script>
<script src="<?= e(vendorAsset('sweetalert2/sweetalert2.min.js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11')) ?>"></script>
<script src="<?= e(vendorAsset('chartjs/chart.umd.min.js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js')) ?>"></script>
<script>
  // FIX (network-error root cause #0 — the primary one): every module's JS
  // file builds its AJAX URLs as `window.APP_URL + '/modules/.../ajax_x.php'`,
  // but window.APP_URL was never actually being defined anywhere in the app.
  // That made every single one of those URLs evaluate to the literal string
  // "undefined/modules/.../ajax_x.php" — a broken relative path that the
  // browser tried to resolve against the current page, essentially always
  // missing. This affected every Add/Edit/Delete/Search/Filter/Sort/
  // Pagination/QR-scan action across every module, which is why data
  // appeared "not saved" and forms intermittently reported network errors.
  window.APP_URL = <?= json_encode(rtrim(APP_URL, '/')) ?>;
  window.APP_CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;
  window.IDLE_TIMEOUT = <?= (int)IDLE_TIMEOUT ?>;
  window.IDLE_WARNING_SECONDS = <?= (int)IDLE_WARNING_SECONDS ?>;
</script>
<script src="<?= e(APP_URL) ?>/assets/js/app.js"></script>

<?php
// Render any flash messages queued this request as SweetAlert2 toasts.
$flashMessages = getFlashMessages();
if (!empty($flashMessages)):
    foreach ($flashMessages as $msg):
        $icon = in_array($msg['type'], ['danger', 'error'], true) ? 'error'
              : ($msg['type'] === 'warning' ? 'warning' : ($msg['type'] === 'success' ? 'success' : 'info'));
?>
<script>
Swal.fire({
  icon: '<?= e($icon) ?>',
  title: <?= json_encode($msg['message']) ?>,
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3500,
  timerProgressBar: true
});
</script>
<?php
    endforeach;
endif;
?>

<?php if (!empty($extraJs)) foreach ($extraJs as $js): ?>
<script src="<?= e($js) ?>"></script>
<?php endforeach; ?>

<style>
    /* Keeps the sidebar layout filling the viewport height now that
       there's no footer bar pinning things at the bottom. */
    .app-wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
    }
</style>

</body>
</html>