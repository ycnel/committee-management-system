/**
 * assets/js/performance.js
 * ------------------------------------------------------------------
 * Powers modules/performance/index.php: the "Snapshot" quick action
 * that records current KPIs into committee_performance.
 * ------------------------------------------------------------------
 */

(function () {
  document.querySelectorAll('.btn-snapshot').forEach(btn => {
    btn.addEventListener('click', function () {
      const committeeId = btn.getAttribute('data-id');
      const csrfToken = window.APP_CSRF_TOKEN || '';
      btn.disabled = true;
      appPost(window.APP_URL + '/modules/performance/ajax_snapshot.php', { committee_id: committeeId, csrf_token: csrfToken })
        .then(data => {
          btn.disabled = false;
          if (data.success) {
            appToast('success', data.message);
            setTimeout(() => window.location.reload(), 900);
          } else if (!data.session_expired) {
            Swal.fire('Error', data.message, 'error');
          }
        });
    });
  });
})();
