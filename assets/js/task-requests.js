(function () {
  const detailModalElement = document.getElementById('taskRequestDetailModal');
  const detailModal = detailModalElement && window.bootstrap
    ? new bootstrap.Modal(detailModalElement)
    : null;

  document.addEventListener('click', function (event) {
    const viewButton = event.target.closest('.task-request-view');
    if (viewButton && detailModal) {
      const data = viewButton.dataset;
      const setText = function (id, value) {
        document.getElementById(id).textContent = value || 'Not provided.';
      };
      setText('taskRequestDetailTitle', data.title);
      setText('taskRequestDetailRequester', data.requester);
      setText('taskRequestDetailRole', data.role);
      setText('taskRequestDetailCommittee', data.committee);
      setText('taskRequestDetailJurisdiction', data.jurisdiction);
      document.getElementById('taskRequestDetailTemplate').textContent = data.template
        ? 'Standard task: ' + data.template
        : '';
      setText('taskRequestDetailAssignee', data.assignee);
      setText('taskRequestDetailPriority', data.priority);
      setText('taskRequestDetailDue', data.due);
      setText('taskRequestDetailSubmitted', data.submitted);
      setText('taskRequestDetailDescription', data.description || 'No description provided.');
      const status = document.getElementById('taskRequestDetailStatus');
      status.textContent = data.status;
      const statusClass = data.status === 'Pending' ? 'pending'
        : (data.status === 'Approved' ? 'approved' : 'rejected');
      status.className = 'badge task-request-pill task-request-status-' + statusClass;
      const responseWrap = document.getElementById('taskRequestDetailResponseWrap');
      responseWrap.classList.toggle('d-none', !data.response);
      setText('taskRequestDetailResponse', data.response);
      detailModal.show();
      return;
    }

    const actionButton = event.target.closest('[data-review-task]');
    if (!actionButton) return;
    const decision = actionButton.dataset.reviewTask;
    const proposalId = actionButton.dataset.proposalId;

    const submitReview = function (response) {
      appFetchJson(window.APP_URL + '/modules/workload/ajax_process_task_request.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: window.APP_CSRF_TOKEN || '',
          proposal_id: proposalId,
          decision: decision,
          admin_response: response || ''
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
    };

    if (decision === 'Approved') {
      Swal.fire({
        title: 'Approve task proposal?',
        text: 'An assignment will be created and the requester will be notified.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approve and create task'
      }).then(function (result) {
        if (result.isConfirmed) submitReview('');
      });
      return;
    }

    Swal.fire({
      title: 'Reject task proposal?',
      input: 'textarea',
      inputLabel: 'Optional reason for rejection',
      inputPlaceholder: 'Add a response for the requester (optional)',
      inputAttributes: { maxlength: 5000 },
      showCancelButton: true,
      confirmButtonText: 'Reject proposal',
      confirmButtonColor: '#a4302a'
    }).then(function (result) {
      if (result.isConfirmed) submitReview(result.value || '');
    });
  });

  const requestId = new URLSearchParams(window.location.search).get('request_id');
  const row = requestId && document.getElementById('task-request-' + requestId);
  if (row) {
    row.classList.add('table-primary');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
})();
