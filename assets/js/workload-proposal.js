/**
 * assets/js/workload-proposal.js
 * ------------------------------------------------------------------
 * modules/workload/proposal.php — member accept/decline, and
 * Chairperson/Admin approve/reassign/stop distribution actions.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const proposalId = new URLSearchParams(window.location.search).get('id');
    const baseUrl = window.APP_URL + '/modules/workload';
    const csrfToken = window.APP_CSRF_TOKEN || '';

    function post(url, data) {
      return appPost(url, Object.assign({ csrf_token: csrfToken }, data));
    }

    function handleResult(data, successMessage) {
      if (data && data.success) {
        appToast('success', data.message || successMessage);
        setTimeout(function () { window.location.reload(); }, 700);
      } else if (data && !data.session_expired) {
        if (window.Swal) Swal.fire('Error', data.message || 'Something went wrong.', 'error');
        else appToast('error', data.message || 'Something went wrong.');
      }
    }

    // ---- Member: Accept / Decline ----
    const btnAccept = document.getElementById('btnAccept');
    if (btnAccept) {
      btnAccept.addEventListener('click', function () {
        if (window.Swal) {
          Swal.fire({
            title: 'Accept this task?',
            text: 'The Chairperson/Administrator still needs to give final approval afterward.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, accept'
          }).then(function (r) {
            if (r.isConfirmed) post(baseUrl + '/ajax_member_respond.php', { proposal_id: proposalId, action: 'accept' }).then(function (d) { handleResult(d, 'Accepted.'); });
          });
        } else if (window.confirm('Accept this task?')) {
          post(baseUrl + '/ajax_member_respond.php', { proposal_id: proposalId, action: 'accept' }).then(function (d) { handleResult(d, 'Accepted.'); });
        }
      });
    }

    const btnDecline = document.getElementById('btnDecline');
    const declineWrap = document.getElementById('declineReasonWrap');
    if (btnDecline && declineWrap) {
      btnDecline.addEventListener('click', function () {
        declineWrap.style.display = declineWrap.style.display === 'none' ? 'block' : 'none';
      });
      document.getElementById('btnConfirmDecline').addEventListener('click', function () {
        const note = document.getElementById('declineReason').value;
        post(baseUrl + '/ajax_member_respond.php', { proposal_id: proposalId, action: 'decline', note: note })
          .then(function (d) { handleResult(d, 'Declined.'); });
      });
    }

    // ---- Chairperson/Admin: Approve ----
    const btnApprove = document.getElementById('btnApprove');
    if (btnApprove) {
      btnApprove.addEventListener('click', function () {
        if (window.Swal) {
          Swal.fire({
            title: 'Give final approval?',
            text: 'This confirms the assignment and makes it a real, active task.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, approve'
          }).then(function (r) {
            if (r.isConfirmed) post(baseUrl + '/ajax_approve.php', { proposal_id: proposalId }).then(function (d) { handleResult(d, 'Approved.'); });
          });
        } else if (window.confirm('Give final approval?')) {
          post(baseUrl + '/ajax_approve.php', { proposal_id: proposalId }).then(function (d) { handleResult(d, 'Approved.'); });
        }
      });
    }

    // ---- Chairperson/Admin: Reassign ----
    const btnReassign = document.getElementById('btnReassign');
    const reassignWrap = document.getElementById('reassignWrap');
    const reassignSelect = document.getElementById('reassignMemberSelect');
    if (btnReassign && reassignWrap && reassignSelect) {
      btnReassign.addEventListener('click', function () {
        const isOpening = reassignWrap.style.display === 'none';
        reassignWrap.style.display = isOpening ? 'block' : 'none';
        if (isOpening && reassignSelect.options.length <= 1) {
          const committeeId = btnReassign.getAttribute('data-committee-id');
          appGet(window.APP_URL + '/modules/workload/ajax_members_by_committee.php?committee_id=' + committeeId)
            .then(function (data) {
              reassignSelect.innerHTML = '<option value="">Select a member…</option>';
              (data.members || []).forEach(function (m) {
                const opt = document.createElement('option');
                opt.value = m.committee_member_id;
                const availability = m.availability_status && m.availability_status !== 'Available'
                  ? ' — ' + m.availability_label : '';
                opt.textContent = m.full_name + ' (' + m.member_role + ')' + availability;
                reassignSelect.appendChild(opt);
              });
            });
        }
      });
      document.getElementById('btnConfirmReassign').addEventListener('click', function () {
        const newId = reassignSelect.value;
        if (!newId) { appToast('error', 'Please select a member.'); return; }
        post(baseUrl + '/ajax_reassign.php', { proposal_id: proposalId, committee_member_id: newId })
          .then(function (d) { handleResult(d, 'Reassignment submitted for Administrator review.'); });
      });
    }

    // ---- Chairperson/Admin: Stop Distribution ----
    const btnStop = document.getElementById('btnStop');
    const stopWrap = document.getElementById('stopWrap');
    if (btnStop && stopWrap) {
      btnStop.addEventListener('click', function () {
        stopWrap.style.display = stopWrap.style.display === 'none' ? 'block' : 'none';
      });
      document.getElementById('btnConfirmStop').addEventListener('click', function () {
        const note = document.getElementById('stopReason').value;
        post(baseUrl + '/ajax_stop_distribution.php', { proposal_id: proposalId, note: note })
          .then(function (d) { handleResult(d, 'Stopped.'); });
      });
    }

    // ---- Procedural checklist ----
    document.querySelectorAll('.checklist-toggle').forEach(function (toggle) {
      toggle.addEventListener('change', function () {
        if (toggle.disabled) return;

        const payload = {
          proposal_id: toggle.dataset.proposalId,
          item_key: toggle.dataset.itemKey,
          checked: toggle.checked ? 1 : 0,
          notes: ''
        };

        post(baseUrl + '/ajax_checklist_save.php', payload)
          .then(function (d) {
            if (d && d.success) {
              appToast('success', d.message || 'Checklist updated.');
              setTimeout(function () { window.location.reload(); }, 400);
            } else if (d && d.message) {
              appToast('error', d.message);
              toggle.checked = !toggle.checked;
            }
          })
          .catch(function () {
            toggle.checked = !toggle.checked;
            appToast('error', 'Unable to update checklist.');
          });
      });
    });
  });
})();
