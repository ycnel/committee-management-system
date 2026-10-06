document.addEventListener('DOMContentLoaded', function () {
  const detailsElement = document.getElementById('approvalRequestDetailsModal');
  if (!detailsElement) return;
  const detailsModal = new bootstrap.Modal(detailsElement);
  const proofElement = document.getElementById('completionProofPreviewModal');
  const proofModal = proofElement ? new bootstrap.Modal(proofElement) : null;
  if (proofElement) {
    proofElement.addEventListener('hidden.bs.modal', function () {
      document.getElementById('completionProofImage').removeAttribute('src');
      document.getElementById('completionProofDocument').removeAttribute('src');
    });
  }

  const setText = function (id, value) {
    document.getElementById(id).textContent = value || 'Not provided.';
  };

  document.addEventListener('click', function (event) {
    const proofButton = event.target.closest('[data-proof-preview]');
    if (proofButton && proofModal) {
      const proofUrl = proofButton.dataset.proofUrl;
      const proofType = proofButton.dataset.proofType;
      const proofName = proofButton.dataset.proofName || 'Completion proof';
      const imageWrap = document.getElementById('completionProofImageWrap');
      const documentWrap = document.getElementById('completionProofDocumentWrap');
      const downloadOnly = document.getElementById('completionProofDownloadOnly');
      const image = document.getElementById('completionProofImage');
      const iframe = document.getElementById('completionProofDocument');
      const downloadLink = document.getElementById('completionProofDownload');

      imageWrap.classList.add('d-none');
      documentWrap.classList.add('d-none');
      downloadOnly.classList.add('d-none');
      image.removeAttribute('src');
      iframe.removeAttribute('src');
      document.getElementById('completionProofFileName').textContent = proofName;
      downloadLink.href = proofUrl + '&download=1';

      if (proofType === 'image/png' || proofType === 'image/jpeg' || proofType === 'image/jpg') {
        image.src = proofUrl;
        imageWrap.classList.remove('d-none');
      } else if (proofType === 'application/pdf') {
        iframe.src = proofUrl;
        documentWrap.classList.remove('d-none');
      } else {
        downloadOnly.classList.remove('d-none');
      }

      proofModal.show();
      return;
    }

    const detailsButton = event.target.closest('[data-approval-details]');
    if (detailsButton) {
      const data = detailsButton.dataset;
      setText('approvalDetailType', data.requestType);
      setText('approvalDetailTask', data.task);
      setText('approvalDetailRequester', data.requester);
      setText('approvalDetailCommittee', data.committee);
      setText('approvalDetailJurisdiction', data.jurisdiction);
      setText('approvalDetailSubmitted', data.submitted);
      setText('approvalDetailStatus', data.status);
      document.getElementById('approvalDetailNotesLabel').textContent =
        data.requestType === 'Task Completion Request' ? 'Completion Notes / Remarks' : 'Removal Reason';
      setText('approvalDetailNotes', data.notes);

      const remarksWrap = document.getElementById('approvalDetailRemarksWrap');
      remarksWrap.classList.toggle('d-none', !data.reviewerRemarks);
      setText('approvalDetailRemarks', data.reviewerRemarks);

      const proofWrap = document.getElementById('approvalDetailProofWrap');
      const proofLink = document.getElementById('approvalDetailProof');
      proofWrap.classList.toggle('d-none', !data.proofUrl);
      proofLink.href = data.proofUrl || '';
      proofLink.textContent = data.proofName || 'View Proof';
      detailsModal.show();
      return;
    }

    const completionButton = event.target.closest('[data-review-completion]');
    const removalButton = event.target.closest('[data-review-removal]');
    const reviewButton = completionButton || removalButton;
    if (!reviewButton) return;

    const isCompletion = Boolean(completionButton);
    const decision = reviewButton.dataset[isCompletion ? 'reviewCompletion' : 'reviewRemoval'];
    const requestId = reviewButton.dataset.requestId;
    const requestName = isCompletion ? 'completion' : 'task removal';
    const submitReview = function (remarks) {
      const endpoint = isCompletion
        ? '/modules/workload/ajax_review_task_completion.php'
        : '/modules/workload/ajax_review_task_removal.php';
      appPost(window.APP_URL + endpoint, {
        csrf_token: window.APP_CSRF_TOKEN || '',
        request_id: requestId,
        decision: decision,
        reviewer_remarks: remarks || '',
      }).then(function (data) {
        if (!data.success) {
          if (!data.session_expired) Swal.fire('Unable to review request', data.message || 'Please try again.', 'error');
          return;
        }
        Swal.fire('Request processed', data.message, 'success').then(function () {
          window.location.reload();
        });
      });
    };

    if (decision === 'Approved') {
      const message = isCompletion
        ? 'The task will be marked complete and the member will be notified.'
        : 'The assigned task will be removed and the member will be notified.';
      Swal.fire({
        title: 'Approve ' + requestName + '?',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: isCompletion ? 'Approve completion' : 'Approve removal',
      }).then(function (result) {
        if (result.isConfirmed) submitReview('');
      });
      return;
    }

    Swal.fire({
      title: 'Reject ' + requestName + '?',
      input: 'textarea',
      inputLabel: 'Reviewer Remarks (required)',
      inputPlaceholder: 'Explain what the member should address.',
      inputAttributes: { maxlength: 10000 },
      inputValidator: function (value) {
        return value && value.trim() ? undefined : 'Reviewer remarks are required.';
      },
      showCancelButton: true,
      confirmButtonText: 'Reject request',
      confirmButtonColor: '#a13b3b',
    }).then(function (result) {
      if (result.isConfirmed) submitReview(result.value.trim());
    });
  });

  const requestId = new URLSearchParams(window.location.search).get('request_id');
  const completionRow = requestId && document.getElementById('completion-request-' + requestId);
  const removalRow = requestId && document.getElementById('removal-request-' + requestId);
  const targetRow = completionRow || removalRow;
  if (targetRow) {
    targetRow.classList.add('table-primary');
    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  const section = new URLSearchParams(window.location.search).get('section');
  if (!targetRow && section) {
    const targetSection = document.getElementById(section === 'removal' ? 'taskRemovalRequests' : 'taskCompletionRequests');
    if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});
