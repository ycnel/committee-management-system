(function () {
  const endpoint = window.APP_URL + '/modules/jurisdictions/ajax_process_removal.php';

  function processRequest(requestId, decision, adminResponse) {
    appFetchJson(endpoint, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: window.APP_CSRF_TOKEN || '',
        request_id: requestId,
        decision: decision,
        admin_response: adminResponse || ''
      }).toString()
    }).then(function (data) {
      if (data.success) {
        Swal.fire('Request processed', data.message, 'success').then(function () {
          window.location.reload();
        });
      } else if (!data.session_expired) {
        Swal.fire('Unable to process request', data.message || 'Please try again.', 'error');
      }
    });
  }

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-process-removal]');
    if (!button) return;

    const requestId = button.dataset.requestId;
    const decision = button.dataset.processRemoval;
    if (decision === 'Approved') {
      Swal.fire({
        title: 'Approve and remove?',
        text: 'The jurisdiction will be permanently removed if it is not assigned to a committee.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Approve and remove',
        confirmButtonColor: '#a4302a'
      }).then(function (result) {
        if (result.isConfirmed) processRequest(requestId, decision, '');
      });
      return;
    }

    Swal.fire({
      title: 'Reject removal request',
      input: 'textarea',
      inputLabel: 'Optional response to the Chairperson',
      inputPlaceholder: 'Add a reason or response (optional)',
      inputAttributes: { maxlength: 5000 },
      showCancelButton: true,
      confirmButtonText: 'Reject request'
    }).then(function (result) {
      if (result.isConfirmed) processRequest(requestId, decision, result.value || '');
    });
  });

  const requestId = new URLSearchParams(window.location.search).get('request_id');
  const requestedCard = requestId && document.getElementById('request-' + requestId);
  if (requestedCard) {
    requestedCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    requestedCard.classList.add('border-primary');
  }
})();
