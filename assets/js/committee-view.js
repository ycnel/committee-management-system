/**
 * assets/js/committee-view.js
 * ------------------------------------------------------------------
 * Powers modules/committees/view.php: the Assign Member modal, the
 * inline committee-role dropdown, and refreshing the roster table
 * after any change (Member Assignment module).
 * ------------------------------------------------------------------
 */

(function () {
  const wrap = document.getElementById('membersTableWrap');
  if (!wrap) return;

  const assignBtn = document.getElementById('btnAssignMember');
  const committeeId = assignBtn ? assignBtn.getAttribute('data-committee-id')
                                 : new URLSearchParams(window.location.search).get('id');

  function reloadRoster() {
    appGet(window.APP_URL + '/modules/committees/ajax_member_search.php?committee_id=' + committeeId)
      .then(data => {
        if (data.success) { wrap.innerHTML = data.html; bindRoleSelects(); }
        else if (!data.session_expired) { appToast('error', data.message || 'Unable to refresh the roster.'); }
      });
  }

  function bindRoleSelects() {
    wrap.querySelectorAll('.member-role-select').forEach(sel => {
      sel.addEventListener('change', function () {
        const memberId = sel.getAttribute('data-member-id');
        const csrfToken = window.APP_CSRF_TOKEN || '';
        appPost(window.APP_URL + '/modules/committees/ajax_member_role.php', {
          id: memberId,
          member_role: sel.value,
          csrf_token: csrfToken
        }).then(data => {
          if (data.success) { appToast('success', data.message); reloadRoster(); }
          else if (!data.session_expired) { Swal.fire('Error', data.message, 'error'); reloadRoster(); }
        });
      });
    });
  }

  if (window.registerDeleteHandler) window.registerDeleteHandler(reloadRoster);

  bindRoleSelects();

  /* ================= Assign Member Modal ================= */
  const modalEl = document.getElementById('assignMemberModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('assignMemberForm');

  if (assignBtn) {
    assignBtn.addEventListener('click', function () {
      form.reset();
      modal.show();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/modules/committees/ajax_member_save.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) {
          modal.hide();
          appToast('success', data.message);
          reloadRoster();
        } else if (!data.session_expired) {
          Swal.fire('Error', data.message, 'error');
        }
      });
  });
})();
