(function () {
  function submitRemoval(button, values) {
    appFetchJson(window.APP_URL + '/modules/jurisdictions/' + values.endpoint, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(Object.assign({
        csrf_token: window.APP_CSRF_TOKEN || '',
        jurisdiction_id: button.dataset.jurisdictionId,
        id: button.dataset.jurisdictionId
      }, values.data || {})).toString()
    }).then(function (data) {
      if (!data.success) {
        if (!data.session_expired) {
          Swal.fire('Unable to complete request', data.message || 'Please try again.', 'error');
        }
        return;
      }
      if (values.request) {
        Swal.fire('Request submitted', data.message, 'success').then(function () {
          window.location.href = window.APP_URL + '/modules/jurisdictions/requests.php?request_id='
            + encodeURIComponent(data.request_id);
        });
      } else {
        Swal.fire('Jurisdiction removed', data.message, 'success').then(function () {
          window.location.href = window.APP_URL + '/modules/jurisdictions/index.php';
        });
      }
    });
  }

  const removeButton = document.getElementById('removeJurisdictionButton');
  if (removeButton) {
    removeButton.addEventListener('click', function () {
      Swal.fire({
        title: 'Remove jurisdiction?',
        text: 'Deleting this jurisdiction is a sensitive administrative action. Enter your current account password to continue.',
        icon: 'warning',
        input: 'password',
        inputPlaceholder: 'Current account password',
        inputAttributes: { autocomplete: 'current-password', autocapitalize: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Verify and remove',
        confirmButtonColor: '#a4302a',
        inputValidator: function (value) {
          return value ? null : 'Enter your current password to continue.';
        }
      }).then(function (result) {
        if (result.isConfirmed) {
          submitRemoval(removeButton, {
            endpoint: 'ajax_delete.php',
            data: { current_password: result.value }
          });
        }
      });
    });
  }

  const requestButton = document.getElementById('requestJurisdictionRemovalButton');
  if (requestButton) {
    requestButton.addEventListener('click', function () {
      Swal.fire({
        title: 'Request jurisdiction removal',
        text: 'An Administrator will review this request. The jurisdiction will not be removed until approved.',
        input: 'textarea',
        inputLabel: 'Reason (optional)',
        inputPlaceholder: 'Explain why this jurisdiction should be removed',
        inputAttributes: { maxlength: 5000 },
        showCancelButton: true,
        confirmButtonText: 'Send request'
      }).then(function (result) {
        if (result.isConfirmed) {
          submitRemoval(requestButton, {
            endpoint: 'ajax_request_removal.php',
            request: true,
            data: { reason: result.value || '' }
          });
        }
      });
    });
  }
})();
