/**
 * assets/js/ai-ollama-settings.js
 * ------------------------------------------------------------------
 * Powers the "Google Gemini AI" panel on modules/workload/ai_settings.php:
 * saving the enable flag / model / timeout, and the Test AI
 * Connection button. Kept separate from ai-settings.js (which owns the
 * rule-based factor-weight sliders) so the two panels never interfere.
 * ------------------------------------------------------------------
 */

(function () {
  const form = document.getElementById('aiSettingsForm');
  const testBtn = document.getElementById('btnTestAiConnection');
  const statusBadge = document.getElementById('aiStatusBadge');
  const statusDetail = document.getElementById('aiStatusDetail');
  const responseTimeEl = document.getElementById('aiResponseTime');
  const testResultEl = document.getElementById('aiTestResult');
  if (!form) return;

  function setStatusBadge(state, text) {
    if (!statusBadge) return;
    statusBadge.className = 'badge ' + (state === 'online' ? 'bg-success' : state === 'offline' ? 'bg-danger' : 'bg-secondary');
    statusBadge.textContent = text;
  }

  function runTest(showInline) {
    setStatusBadge('checking', 'Checking…');
    if (statusDetail) statusDetail.textContent = 'Checking…';
    appGet(window.APP_URL + '/modules/workload/ajax_test_ai_connection.php').then(data => {
      if (!data.success) {
        setStatusBadge('offline', 'Offline');
        if (statusDetail) statusDetail.textContent = data.message || 'Could not run the connection test.';
        if (showInline && testResultEl) testResultEl.innerHTML = '<span class="text-danger">' + (data.message || 'Test failed.') + '</span>';
        return;
      }
      const s = data.status;
      const online = !!s.online;
      const modelFound = !!s.model_found;
      setStatusBadge(online && modelFound ? 'online' : 'offline', online ? (modelFound ? 'Online' : 'Model Missing') : 'Offline');
      if (statusDetail) statusDetail.textContent = s.message || '—';
      if (responseTimeEl) responseTimeEl.textContent = (s.response_time_ms !== undefined ? s.response_time_ms + ' ms' : '—');
      if (showInline && testResultEl) {
        testResultEl.innerHTML = online
          ? (modelFound ? '<span class="text-success"><i class="bi bi-check-circle"></i> Connected.</span>' : '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Server online, model not pulled.</span>')
          : '<span class="text-danger"><i class="bi bi-x-circle"></i> Not reachable.</span>';
      }
    }).catch(() => {
      setStatusBadge('offline', 'Offline');
      if (statusDetail) statusDetail.textContent = 'Could not run the connection test.';
    });
  }

  if (testBtn) {
    testBtn.addEventListener('click', function () { runTest(true); });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(form));
    payload.gemini_enabled = document.getElementById('gemini_enabled').checked ? '1' : '0';
    appPost(window.APP_URL + '/modules/workload/ajax_save_ai_settings.php', payload).then(data => {
      if (data.success) {
        appToast('success', data.message || 'AI settings saved.');
        runTest(false);
      } else if (!data.session_expired) {
        Swal.fire('Error', data.message, 'error');
      }
    });
  });

  // Check status once on page load.
  runTest(false);
})();
