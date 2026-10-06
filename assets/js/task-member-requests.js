document.addEventListener('DOMContentLoaded', function () {
  const completionForm = document.getElementById('taskCompletionRequestForm');
  const removalForm = document.getElementById('taskRemovalRequestForm');
  if (!completionForm || !removalForm) return;

  const completionModal = new bootstrap.Modal(document.getElementById('taskCompletionRequestModal'));
  const removalModal = new bootstrap.Modal(document.getElementById('taskRemovalRequestModal'));
  const setText = function (id, value) {
    document.getElementById(id).textContent = value || 'Not recorded';
  };
  const showMessage = function (message, type) {
    if (window.appToast) window.appToast(type || 'error', message);
    else window.alert(message);
  };

  document.addEventListener('click', function (event) {
    const completionButton = event.target.closest('[data-submit-completion]');
    if (completionButton) {
      completionForm.reset();
      document.getElementById('completionWorkloadId').value = completionButton.dataset.workloadId;
      setText('completionTaskTitle', completionButton.dataset.taskTitle);
      setText('completionCommitteeName', completionButton.dataset.committeeName);
      setText('completionJurisdictionName', completionButton.dataset.jurisdictionName);
      setText('completionAssignedDate', completionButton.dataset.assignedDate);
      const guidanceWrap = document.getElementById('completionProofGuidanceWrap');
      const guidance = completionButton.dataset.proofRequirement || '';
      guidanceWrap.classList.toggle('d-none', !guidance);
      setText('completionProofGuidance', guidance);
      completionModal.show();
      return;
    }

    const removalButton = event.target.closest('[data-request-removal]');
    if (!removalButton) return;
    removalForm.reset();
    document.getElementById('removalWorkloadId').value = removalButton.dataset.workloadId;
    setText('removalTaskTitle', removalButton.dataset.taskTitle);
    setText('removalCommitteeName', removalButton.dataset.committeeName);
    setText('removalJurisdictionName', removalButton.dataset.jurisdictionName);
    removalModal.show();
  });

  completionForm.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!completionForm.reportValidity()) return;
    const button = document.getElementById('submitCompletionRequest');
    button.disabled = true;
    appPostForm(window.APP_URL + '/modules/workload/ajax_submit_task_completion.php', completionForm)
      .then(function (data) {
        if (!data.success) {
          showMessage(data.message || 'Unable to submit the completion request.');
          return;
        }
        completionModal.hide();
        showMessage(data.message, 'success');
        window.location.reload();
      })
      .finally(function () {
        button.disabled = false;
      });
  });

  removalForm.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!removalForm.reportValidity()) return;
    const button = document.getElementById('submitTaskRemovalRequest');
    button.disabled = true;
    appPostForm(window.APP_URL + '/modules/workload/ajax_submit_task_removal.php', removalForm)
      .then(function (data) {
        if (!data.success) {
          showMessage(data.message || 'Unable to submit the task removal request.');
          return;
        }
        removalModal.hide();
        showMessage(data.message, 'success');
        window.location.reload();
      })
      .finally(function () {
        button.disabled = false;
      });
  });
});
