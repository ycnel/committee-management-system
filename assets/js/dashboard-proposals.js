/**
 * assets/js/dashboard-proposals.js
 * ------------------------------------------------------------------
 * Populates the "Awaiting Your Response" widget on dashboard_member.php
 * (Committee Member) via modules/workload/ajax_proposals.php.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.APP_URL + '/modules/workload';

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    function priorityColor(p) {
      return p === 'High' || p === 'Urgent' ? 'danger' : (p === 'Medium' ? 'warning' : 'success');
    }

    function stateColor(s) {
      const map = {
        'Awaiting Response': 'warning', 'Accepted': 'info', 'Declined': 'danger',
        'Approved': 'success', 'Reassigned': 'secondary', 'Stopped': 'dark'
      };
      return map[s] || 'secondary';
    }

    function row(p, showAssignee) {
      return '<a href="' + window.APP_URL + '/modules/workload/proposal.php?id=' + p.proposal_id + '" '
        + 'class="d-flex justify-content-between align-items-center px-3 py-2 text-decoration-none border-bottom proposal-row">'
        + '<span>'
        + '<span class="fw-semibold small d-block">' + escapeHtml(p.task_title) + '</span>'
        + '<span class="text-muted small">' + escapeHtml(p.committee_name)
          + (showAssignee ? ' &middot; ' + escapeHtml(p.assignee_name) : '')
          + ' &middot; ' + escapeHtml(p.proposed_at_human) + '</span>'
        + '</span>'
        + '<span class="d-flex gap-1">'
        + '<span class="badge bg-' + priorityColor(p.priority) + '-subtle text-' + priorityColor(p.priority) + '">' + escapeHtml(p.priority) + '</span>'
        + '<span class="badge bg-' + stateColor(p.state) + '">' + escapeHtml(p.state) + '</span>'
        + '</span></a>';
    }

    // ---- Committee Member widget (dashboard_member.php) ----
    const memberCard = document.getElementById('awaitingResponseCard');
    const memberBody = document.getElementById('awaitingResponseBody');
    if (memberCard && memberBody) {
      appGet(baseUrl + '/ajax_proposals.php?state=Awaiting%20Response').then(function (data) {
        const proposals = (data && data.success) ? (data.proposals || []) : [];
        if (!proposals.length) return; // keep the card hidden
        memberCard.style.display = '';
        memberBody.innerHTML = proposals.map(function (p) { return row(p, false); }).join('');
      });
    }
  });
})();
