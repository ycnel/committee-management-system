/**
 * assets/js/app.js
 * ------------------------------------------------------------------
 * Global, site-wide JavaScript: sidebar toggle, delete confirmations,
 * generic AJAX helper, and DataTables defaults. Module-specific logic
 * lives in assets/js/<module>.js and is loaded on top of this file.
 * ------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {

  /* Client-side inactivity guard. The server remains authoritative, while
     this gives the user an immediate and clear expiry message. */
  let idleTimer;
  let idleWarningTimer;
  let idleExpiryShown = false;
  let idleWarningShown = false;
  const idleTimeout = Number(window.IDLE_TIMEOUT || 900) * 1000;
  const warningSeconds = Math.min(
    Number(window.IDLE_WARNING_SECONDS || 30),
    Math.max(1, Math.floor(idleTimeout / 1000) - 1)
  );

  function expireForInactivity() {
    if (idleExpiryShown) return;
    idleExpiryShown = true;
    const idleMinutes = Math.ceil(idleTimeout / 60000);
    const message = 'You have been inactive for ' + idleMinutes + ' minutes.';
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Session Expiring',
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Stay signed in',
        cancelButtonText: 'Log out',
        allowOutsideClick: false
      }).then(function (result) {
        if (!result.isConfirmed) {
          window.location.href = window.APP_URL + '/logout.php?timeout=1';
          return;
        }
        // "Stay" only works if the server-side session is still alive.
        fetch(window.APP_URL + '/auth/session_check.php', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store'
        })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.session_expired || data.session_replaced || !data.success) {
              window.location.href = window.APP_URL + '/logout.php?timeout=1';
              return;
            }
            idleExpiryShown = false;
            idleWarningShown = false;
            resetIdleTimer();
          })
          .catch(function () {
            window.location.href = window.APP_URL + '/logout.php?timeout=1';
          });
      });
    } else {
      window.alert(message + ' Please log in again.');
      window.location.href = window.APP_URL + '/logout.php?timeout=1';
    }
  }

  function resetIdleTimer() {
    if (window.SESSION_TIMEOUT_BYPASS === true || idleExpiryShown) return;
    idleWarningShown = false;
    window.clearTimeout(idleTimer);
    window.clearTimeout(idleWarningTimer);
    idleWarningTimer = window.setTimeout(showIdleWarning, idleTimeout - (warningSeconds * 1000));
  }

  function showIdleWarning() {
    if (idleExpiryShown || idleWarningShown) return;
    idleWarningShown = true;

    const countdownText = '<p>You will be logged out in <strong><span id="idleCountdown">'
      + warningSeconds + '</span> seconds</strong> because of inactivity.</p>';

    Swal.fire({
      icon: 'warning',
      title: 'Session Expiring Soon',
      html: countdownText,
      showCancelButton: true,
      confirmButtonText: 'Stay signed in',
      cancelButtonText: 'Log out',
      allowOutsideClick: false,
      timer: warningSeconds * 1000,
      timerProgressBar: true,
      didOpen: function () {
        const countdown = document.getElementById('idleCountdown');
        const countdownTimer = window.setInterval(function () {
          if (!countdown) return;
          countdown.textContent = Math.max(0, Math.ceil(Swal.getTimerLeft() / 1000));
        }, 250);
        Swal.getPopup().dataset.countdownTimer = String(countdownTimer);
      },
      willClose: function () {
        const popup = Swal.getPopup();
        if (popup && popup.dataset.countdownTimer) {
          window.clearInterval(Number(popup.dataset.countdownTimer));
        }
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        fetch(window.APP_URL + '/auth/session_check.php', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          cache: 'no-store'
        })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.session_expired || data.session_replaced || !data.success) {
              expireForInactivity();
              return;
            }
            resetIdleTimer();
          })
          .catch(function () {
            expireForInactivity();
          });
        return;
      }
      if (result.dismiss === Swal.DismissReason.cancel) {
        window.location.href = window.APP_URL + '/logout.php';
        return;
      }
      expireForInactivity();
    });
  }

  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (eventName) {
    document.addEventListener(eventName, resetIdleTimer, { passive: true });
  });
  resetIdleTimer();

  /* Check idle pages so a session replaced on another browser is logged out
     without waiting for the user to click a control. */
  let sessionExpiryShown = false;
  function checkActiveSession() {
    if (sessionExpiryShown || !window.APP_URL) return;

    fetch(window.APP_URL + '/auth/session_check.php', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-Session-Heartbeat': '1'
      },
      cache: 'no-store'
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.session_expired && !data.session_replaced) return;
        sessionExpiryShown = true;
        Swal.fire({
          icon: 'warning',
          title: 'Session Expired',
          text: data.message || 'Your account was logged in elsewhere. Please log in again.',
          confirmButtonText: 'Go to Login'
        }).then(function () {
          window.location.href = window.APP_URL + '/login.php?session_replaced=1';
        });
      })
      .catch(function () {
        // A temporary network failure should not log the user out.
      });
  }

  window.setInterval(checkActiveSession, 1000);

  /* ---------- Sidebar toggle (desktop collapse / mobile slide-in) ---------- */
  const toggleDesktop = document.getElementById('sidebarToggleDesktop');
  const toggleMobile  = document.getElementById('sidebarToggleMobile');

  if (toggleDesktop) {
    toggleDesktop.addEventListener('click', function () {
      document.body.classList.toggle('sidebar-collapsed');
    });
  }
  if (toggleMobile) {
    toggleMobile.addEventListener('click', function () {
      document.body.classList.toggle('sidebar-mobile-open');
    });
  }

  /* ---------- Generic "confirm delete" wiring ----------
     Any element with [data-confirm-delete] and [data-delete-url]
     will show a SweetAlert2 confirmation, then POST to the URL
     (with CSRF token) via fetch and reload/redirect on success.

     Uses event delegation on document so it keeps working for rows
     injected later by AJAX (e.g. module live-search table refreshes)
     without needing to be re-bound, and without double-binding.
     Modules that want a custom post-delete action (e.g. reload just
     the table instead of the whole page) can call
     window.registerDeleteHandler(fn) to override the default reload.
  ------------------------------------------------------------- */
  let onDeleteSuccess = function () { window.location.reload(); };
  window.registerDeleteHandler = function (fn) { onDeleteSuccess = fn; };

  document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-confirm-delete]');
    if (!el) return;
    e.preventDefault();

    const url = el.getAttribute('data-delete-url');
    const label = el.getAttribute('data-confirm-delete') || 'this record';
    const csrfToken = window.APP_CSRF_TOKEN || '';

    Swal.fire({
      title: 'Are you sure?',
      text: 'This will permanently delete ' + label + '. This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#a4302a',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it'
    }).then(function (result) {
      if (!result.isConfirmed) return;

      appFetchJson(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'csrf_token=' + encodeURIComponent(csrfToken)
      }).then(function (data) {
        if (data.success) {
          appToast('success', data.message || 'Deleted successfully.');
          onDeleteSuccess();
        } else if (!data.session_expired) {
          Swal.fire('Error', data.message || 'Unable to delete this record.', 'error');
        }
      });
    });
  });

  /* ---------- Default DataTables initialization for tables marked .data-table ---------- */
  if (window.jQuery && jQuery.fn.DataTable) {
    jQuery('.data-table').each(function () {
      if (!jQuery.fn.DataTable.isDataTable(this)) {
        jQuery(this).DataTable({
          pageLength: 10,
          responsive: true,
          language: { search: '', searchPlaceholder: 'Search...' }
        });
      }
    });
  }
});

/**
 * ------------------------------------------------------------------
 * Robust AJAX helpers (network-error root-cause fixes, client side)
 * ------------------------------------------------------------------
 * Every helper below ALWAYS resolves (never rejects) with a normalized
 * {success, message, ...} object. This means every existing call site
 * across the app that does `.then(data => { if (data.success) ... else
 * Swal.fire('Error', data.message...) })` automatically gets accurate,
 * specific error messages instead of a generic "network error" — without
 * needing to change each module file individually.
 *
 * The three failure cases are now distinguished instead of collapsed into
 * one generic message:
 *   1. Request never reached the server (offline, server down, wrong URL)
 *      -> "Could not reach the server..." (a genuine network problem)
 *   2. Server responded but not with valid JSON (a PHP fatal error/warning
 *      leaked HTML into the response — should no longer happen after the
 *      server-side fixes, but if it ever does, this says so explicitly)
 *   3. Server responded with valid JSON but success:false (validation
 *      error, permission denied, etc.) -> shows the server's real message
 *
 * If the server reports the session expired, the user is notified and
 * redirected to the login page automatically instead of the form just
 * silently failing.
 * ------------------------------------------------------------------ */

function appFetchJson(url, options) {
  return fetch(url, options)
    .then(function (response) {
      return response.text().then(function (text) {
        let data;
        try {
          data = text ? JSON.parse(text) : {};
        } catch (parseErr) {
          // The server responded, but the body wasn't valid JSON — most
          // likely an HTML error page. Surface that clearly rather than
          // pretending it was a network failure.
          console.error('appFetchJson: non-JSON response from', url, text.substring(0, 500));
          return {
            success: false,
            message: response.ok
              ? 'The server sent back an unexpected response. Please refresh the page and try again.'
              : 'Server error (HTTP ' + response.status + '). Please try again or contact your administrator.',
          };
        }
        if (!response.ok && data.message === undefined) {
          data.message = 'Server error (HTTP ' + response.status + ').';
        }
        return data;
      });
    })
    .then(function (data) {
      if (data && (data.session_expired || data.session_replaced)) {
        Swal.fire({
          icon: 'warning',
          title: 'Session Expired',
          text: data.message || 'Your account was logged in elsewhere. Please log in again.',
          confirmButtonText: 'Go to Login'
        }).then(function () {
          window.location.href = window.APP_URL + '/login.php?session_replaced=1';
        });
      }
      return data;
    })
    .catch(function (err) {
      // A true network-level failure: the request never got a response at
      // all (server unreachable, DNS failure, connection refused, etc.)
      console.error('appFetchJson: network failure for', url, err);
      return {
        success: false,
        message: 'Could not reach the server. Please check that your local server (XAMPP/Apache/MySQL) is running, then try again.',
      };
    });
}

/**
 * POST a plain object as application/x-www-form-urlencoded.
 * Always resolves; never rejects (see appFetchJson above).
 * @param {string} url
 * @param {Object} data
 * @returns {Promise<Object>}
 */
function appPost(url, data) {
  const params = new URLSearchParams(data);
  return appFetchJson(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  });
}

/**
 * POST a <form> element as multipart/form-data (required whenever the form
 * includes a file input, e.g. document/CSV uploads). Always resolves;
 * never rejects.
 * @param {string} url
 * @param {HTMLFormElement} formElement
 * @returns {Promise<Object>}
 */
function appPostForm(url, formElement) {
  return appFetchJson(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new FormData(formElement)
  });
}

/**
 * GET a URL and parse JSON, with the same robust error handling as
 * appPost(). Used by every module's live-search/table-refresh calls.
 * @param {string} url
 * @returns {Promise<Object>}
 */
function appGet(url) {
  return appFetchJson(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
}

/** Small toast helper reusable by module scripts. */
function appToast(icon, message) {
  Swal.fire({ icon, title: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
}

/**
 * Live password-policy feedback, mirroring includes/functions.php's
 * validatePasswordPolicy() (8+ chars, 1 uppercase, 1 special char).
 * This is convenience/UX only — the server re-validates independently
 * and is the actual source of truth; this never replaces that check.
 * Renders a small checklist under the given input as the user types.
 */
function initPasswordPolicyHint(inputId) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const hint = document.createElement('div');
  hint.className = 'form-text password-policy-hint';
  hint.innerHTML = [
    '<span data-rule="len"><i class="bi bi-circle"></i> At least 8 characters</span><br>',
    '<span data-rule="upper"><i class="bi bi-circle"></i> One uppercase letter</span><br>',
    '<span data-rule="special"><i class="bi bi-circle"></i> One special character (e.g. ! @ # $ %)</span>',
  ].join('');
  input.insertAdjacentElement('afterend', hint);

  function check() {
    const val = input.value;
    const rules = {
      len: val.length >= 8,
      upper: /[A-Z]/.test(val),
      special: /[!@#$%^&*()\-_=+\[\]{};:'",.<>/?\\|`~]/.test(val),
    };
    Object.entries(rules).forEach(([key, passed]) => {
      const el = hint.querySelector(`[data-rule="${key}"]`);
      if (!el) return;
      const icon = el.querySelector('i');
      icon.className = passed ? 'bi bi-check-circle-fill text-success' : 'bi bi-circle';
      el.classList.toggle('text-success', passed);
      el.classList.toggle('text-muted', !passed);
    });
  }

  input.addEventListener('input', check);
  check();
}
