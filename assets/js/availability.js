/**
 * assets/js/availability.js
 * ------------------------------------------------------------------
 * Shared "Availability" modal (role-hierarchy revision §11), wired up
 * to any `.btn-availability` button on the page — used by
 * modules/committees/member_table.php (Chairperson/Admin view of a
 * committee roster) and dashboard_member.php ("My Availability" widget).
 * Builds the modal on demand rather than requiring each page to embed
 * its own copy.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.APP_URL + '/modules/availability';
    const csrfToken = window.APP_CSRF_TOKEN || '';
    let modalEl = null;

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    function statusColor(s) {
      const map = { Available: 'success', Unavailable: 'danger', Idle: 'warning', Emergency: 'dark' };
      return map[s] || 'secondary';
    }

    function ensureModal() {
      if (modalEl) return modalEl;
      modalEl = document.createElement('div');
      modalEl.className = 'modal fade';
      modalEl.tabIndex = -1;
      modalEl.innerHTML =
        '<div class="modal-dialog">' +
          '<div class="modal-content">' +
            '<div class="modal-header">' +
              '<h5 class="modal-title" id="availModalTitle">Availability</h5>' +
              '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
            '</div>' +
            '<div class="modal-body" id="availModalBody"><p class="text-muted small">Loading…</p></div>' +
          '</div>' +
        '</div>';
      document.body.appendChild(modalEl);
      return modalEl;
    }

    function renderBody(data, committeeMemberId, canEdit, memberName) {
      const body = document.getElementById('availModalBody');
      const current = data.current;
      let html = '<div class="mb-3">';
      html += '<span class="badge bg-' + statusColor(current.status) + ' fs-6">' + escapeHtml(current.status_label) + '</span>';
      if (current.is_expired) {
        html += ' <span class="badge bg-light text-dark border">previous period expired</span>';
      }
      if (current.reason) html += '<div class="small text-muted mt-1">' + (current.is_expired ? '<em>Last set:</em> ' : '') + escapeHtml(current.reason) + '</div>';
      if (current.start_date || current.end_date) {
        html += '<div class="small text-muted">' + (current.is_expired ? 'Was ' : '') + (current.start_date ? 'from ' + escapeHtml(current.start_date) : '') +
          (current.end_date ? ' until ' + escapeHtml(current.end_date) : '') + '</div>';
      }
      if (!current.is_default) {
        html += '<div class="small text-muted">Set by ' + escapeHtml(current.updated_by_name || 'a former user') + '</div>';
      }
      html += '</div>';

      if (canEdit) {
        html +=
          '<form id="availForm" class="border-top pt-3">' +
            '<div class="mb-2">' +
              '<label class="form-label small">Status</label>' +
              '<select class="form-select form-select-sm" id="availStatus" required>' +
                '<option value="Available">Available</option>' +
                '<option value="Unavailable">Unavailable</option>' +
                '<option value="Idle">Idle / Paused</option>' +
                '<option value="Emergency">Emergency</option>' +
              '</select>' +
            '</div>' +
            '<div class="mb-2">' +
              '<label class="form-label small">Reason (optional)</label>' +
              '<textarea class="form-control form-control-sm" id="availReason" rows="2" maxlength="500"></textarea>' +
            '</div>' +
            '<div class="row g-2 mb-3">' +
              '<div class="col"><label class="form-label small">From</label><input type="date" class="form-control form-control-sm" id="availStart"></div>' +
              '<div class="col"><label class="form-label small">Until</label><input type="date" class="form-control form-control-sm" id="availEnd"></div>' +
            '</div>' +
            '<button type="submit" class="btn btn-dark btn-sm">Update Availability</button>' +
          '</form>';
      }

      if (data.history && data.history.length > 1) {
        html += '<div class="border-top pt-3 mt-3"><div class="small fw-semibold mb-1">History</div>';
        data.history.slice(1).forEach(function (h) {
          html += '<div class="small text-muted mb-1">' +
            '<span class="badge bg-' + statusColor(h.status) + '">' + escapeHtml(h.status_label) + '</span> ' +
            (h.reason ? escapeHtml(h.reason) + ' — ' : '') +
            escapeHtml(h.created_at_human) + (h.updated_by_name ? ' by ' + escapeHtml(h.updated_by_name) : '') +
            '</div>';
        });
        html += '</div>';
      }

      body.innerHTML = html;
      document.getElementById('availModalTitle').textContent = 'Availability — ' + memberName;

      if (canEdit) {
        document.getElementById('availForm').addEventListener('submit', function (event) {
          event.preventDefault();
          appPost(baseUrl + '/ajax_save.php', {
            csrf_token: csrfToken,
            committee_member_id: committeeMemberId,
            status: document.getElementById('availStatus').value,
            reason: document.getElementById('availReason').value,
            start_date: document.getElementById('availStart').value,
            end_date: document.getElementById('availEnd').value,
          }).then(function (resp) {
            if (resp && resp.success) {
              appToast('success', resp.message || 'Availability updated.');
              if (window.bootstrap) bootstrap.Modal.getInstance(modalEl).hide();
              setTimeout(function () { window.location.reload(); }, 600);
            } else {
              appToast('error', (resp && resp.message) || 'Could not update availability.');
            }
          });
        });
      }
    }

    document.addEventListener('click', function (event) {
      const btn = event.target.closest('.btn-availability');
      if (!btn) return;

      const committeeMemberId = btn.getAttribute('data-member-id');
      const memberName = btn.getAttribute('data-member-name') || 'Member';
      const canEdit = btn.getAttribute('data-can-edit') === '1';

      const el = ensureModal();
      document.getElementById('availModalBody').innerHTML = '<p class="text-muted small">Loading…</p>';
      if (window.bootstrap) new bootstrap.Modal(el).show();

      appGet(baseUrl + '/ajax_history.php?committee_member_id=' + encodeURIComponent(committeeMemberId))
        .then(function (data) {
          if (!data || !data.success) {
            document.getElementById('availModalBody').innerHTML = '<p class="text-danger small">Could not load availability.</p>';
            return;
          }
          renderBody(data, committeeMemberId, canEdit, memberName);
        });
    });
  });
})();