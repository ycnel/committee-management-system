/**
 * assets/js/system-backup.js
 * ------------------------------------------------------------------
 * modules/system/backup.php — Super Admin only.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var backupBtn = document.getElementById('btnRunBackup');
    if (backupBtn) {
      backupBtn.addEventListener('click', function () {
        var url = window.APP_URL + '/modules/system/ajax_backup.php?csrf_token=' + encodeURIComponent(window.APP_CSRF_TOKEN);
        window.location.href = url; // native browser download, not fetch()
      });
    }

    var confirmInput = document.getElementById('restoreConfirmPhrase');
    var restoreBtn = document.getElementById('btnRunRestore');
    var restoreForm = document.getElementById('restoreForm');
    var resultBox = document.getElementById('restoreResult');
    if (!confirmInput || !restoreBtn || !restoreForm) return;

    confirmInput.addEventListener('input', function () {
      restoreBtn.disabled = confirmInput.value.trim() !== 'RESTORE';
    });

    restoreForm.addEventListener('submit', function (event) {
      event.preventDefault();
      if (confirmInput.value.trim() !== 'RESTORE') return;

      if (window.Swal) {
        Swal.fire({
          title: 'Restore the database?',
          html: 'This will overwrite existing data for every table in the uploaded file. This cannot be undone except by restoring an earlier backup.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, restore now',
          confirmButtonColor: '#c62828'
        }).then(function (result) {
          if (result.isConfirmed) runRestore();
        });
      } else if (window.confirm('This will overwrite existing data. Continue?')) {
        runRestore();
      }
    });

    function runRestore() {
      restoreBtn.disabled = true;
      restoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Restoring…';
      resultBox.innerHTML = '';

      var formData = new FormData(restoreForm);
      fetch(window.APP_URL + '/modules/system/ajax_restore.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var ok = !!(data && data.success);
          resultBox.innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + ' py-2 px-3 small mb-0">'
            + (data && data.message ? data.message.replace(/</g, '&lt;') : (ok ? 'Restore complete.' : 'Restore failed.'))
            + '</div>';
          if (ok) {
            restoreForm.reset();
          }
        })
        .catch(function () {
          resultBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Network error during restore. Check the server error log.</div>';
        })
        .finally(function () {
          restoreBtn.disabled = true; // stays disabled until confirm phrase is re-typed
          restoreBtn.innerHTML = '<i class="bi bi-upload me-1"></i> Upload & Restore';
          confirmInput.value = '';
        });
    }
  });
})();
