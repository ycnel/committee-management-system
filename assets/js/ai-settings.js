/**
 * assets/js/ai-settings.js
 * ------------------------------------------------------------------
 * Powers modules/workload/ai_settings.php: live weight sliders and
 * saving the Smart AI Workload Distribution factor configuration.
 * ------------------------------------------------------------------
 */

(function () {
  const form = document.getElementById('weightsForm');
  if (!form) return;

  form.querySelectorAll('.factor-weight').forEach(slider => {
    const badge = slider.closest('tr').querySelector('.weight-value');
    slider.addEventListener('input', () => { badge.textContent = slider.value; });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const factors = [];
    form.querySelectorAll('.factor-weight').forEach(slider => {
      const key = slider.getAttribute('data-key');
      const enabledInput = form.querySelector(`.factor-enabled[data-key="${key}"]`);
      factors.push({
        factor_key: key,
        weight: parseInt(slider.value, 10),
        is_enabled: enabledInput ? enabledInput.checked : true,
      });
    });

    const csrfToken = window.APP_CSRF_TOKEN || '';
    appPost(window.APP_URL + '/modules/workload/ajax_ai_weights_save.php', {
      factors: JSON.stringify(factors),
      csrf_token: csrfToken,
    }).then(data => {
      if (data.success) {
        appToast('success', data.message);
      } else if (!data.session_expired) {
        Swal.fire('Error', data.message, 'error');
      }
    });
  });
})();
