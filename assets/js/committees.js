/**
 * assets/js/committees.js
 * ------------------------------------------------------------------
 * Powers modules/committees/index.php: live AJAX search/filter/sort/
 * pagination, and the Create/Edit Committee modal.
 * ------------------------------------------------------------------
 */

(function () {
  const wrap = document.getElementById('committeesTableWrap');
  const filterForm = document.getElementById('filterForm');
  if (!wrap) return;

  const AJAX_URL = window.APP_URL + '/modules/committees/ajax_search.php';
  let currentPage = 1;

  function buildParams(extra) {
    const params = new URLSearchParams();
    if (filterForm) {
      const data = new FormData(filterForm);
      for (const [key, val] of data.entries()) { if (val !== '') params.append(key, val); }
    }
    params.set('page', extra && extra.page ? extra.page : currentPage);
    return params;
  }

  function updateFilterChip() {
    const chip = document.getElementById('activeFilterCount');
    if (!chip || !filterForm) return;
    const ignored = new Set(['sort', 'dir']);
    let n = 0;
    for (const [key, val] of new FormData(filterForm).entries()) {
      if (!ignored.has(key) && val !== '') n++;
    }
    chip.textContent = n + ' filter' + (n === 1 ? '' : 's') + ' active';
    chip.classList.toggle('d-none', n === 0);
  }

  function initDatePickers() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    const pad = value => String(value).padStart(2, '0');
    const formatValue = date => date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    const formatLabel = date => pad(date.getMonth() + 1) + '/' + pad(date.getDate()) + '/' + date.getFullYear();

    document.querySelectorAll('[data-date-picker]').forEach(function (picker) {
      const valueInput = picker.querySelector('[data-date-value]');
      const trigger = picker.querySelector('[data-date-trigger]');
      const label = picker.querySelector('[data-date-label]');
      const calendar = document.createElement('div');
      calendar.className = 'committee-calendar';
      calendar.hidden = true;
      picker.appendChild(calendar);
      let viewDate = new Date();
      let choosingYear = false;

      function close() {
        calendar.hidden = true;
        picker.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      }

      function render() {
        const selected = valueInput.value ? new Date(valueInput.value + 'T00:00:00') : null;
        if (choosingYear) {
          const startYear = viewDate.getFullYear() - 4;
          const years = Array.from({ length: 12 }, (_, index) => startYear + index);
          calendar.innerHTML = '<div class="committee-calendar-header"><button type="button" class="committee-calendar-title committee-calendar-title-button" data-calendar-month-view>' + monthNames[viewDate.getMonth()] + ' ' + viewDate.getFullYear() + '</button><span class="committee-calendar-nav"><button type="button" data-calendar-year-prev aria-label="Previous years">&#8249;</button><button type="button" data-calendar-year-next aria-label="Next years">&#8250;</button></span></div>'
            + '<div class="committee-calendar-years">' + years.map(year => '<button type="button" class="committee-calendar-year' + (year === viewDate.getFullYear() ? ' is-selected' : '') + '" data-calendar-year="' + year + '">' + year + '</button>').join('') + '</div>'
            + '<div class="committee-calendar-footer"><button type="button" data-calendar-clear>Clear</button><button type="button" data-calendar-today>Today</button></div>';
          return;
        }
        const first = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
        const start = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1 - first.getDay());
        let days = '';
        for (let i = 0; i < 42; i++) {
          const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
          const muted = day.getMonth() !== viewDate.getMonth() ? ' is-muted' : '';
          const chosen = selected && formatValue(day) === formatValue(selected) ? ' is-selected' : '';
          days += '<button type="button" class="committee-calendar-day' + muted + chosen + '" data-calendar-date="' + formatValue(day) + '">' + day.getDate() + '</button>';
        }
        calendar.innerHTML = '<div class="committee-calendar-header"><button type="button" class="committee-calendar-title committee-calendar-title-button" data-calendar-year-view>' + monthNames[viewDate.getMonth()] + ' ' + viewDate.getFullYear() + '</button><span class="committee-calendar-nav"><button type="button" data-calendar-prev aria-label="Previous month">&#8249;</button><button type="button" data-calendar-next aria-label="Next month">&#8250;</button></span></div>'
          + '<div class="committee-calendar-weekdays">' + weekdays.map(day => '<span>' + day + '</span>').join('') + '</div>'
          + '<div class="committee-calendar-days">' + days + '</div>'
          + '<div class="committee-calendar-footer"><button type="button" data-calendar-clear>Clear</button><button type="button" data-calendar-today>Today</button></div>';
      }

      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        if (calendar.hidden) {
          if (valueInput.value) viewDate = new Date(valueInput.value + 'T00:00:00');
          render(); calendar.hidden = false; picker.classList.add('is-open'); trigger.setAttribute('aria-expanded', 'true');
        } else close();
      });
      calendar.addEventListener('click', function (event) {
        event.stopPropagation();
        const day = event.target.closest('[data-calendar-date]');
        if (day) { valueInput.value = day.dataset.calendarDate; label.textContent = formatLabel(new Date(day.dataset.calendarDate + 'T00:00:00')); trigger.classList.add('has-value'); close(); return; }
        const previous = event.target.closest('[data-calendar-prev]');
        if (previous) {
          event.preventDefault();
          viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() - 1, 1);
          render();
          return;
        }
        const next = event.target.closest('[data-calendar-next]');
        if (next) {
          event.preventDefault();
          viewDate = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 1);
          render();
          return;
        }
        if (event.target.closest('[data-calendar-year-view]')) { choosingYear = true; render(); return; }
        if (event.target.closest('[data-calendar-month-view]')) { choosingYear = false; render(); return; }
        if (event.target.closest('[data-calendar-year-prev]')) { viewDate = new Date(viewDate.getFullYear() - 12, viewDate.getMonth(), 1); render(); return; }
        if (event.target.closest('[data-calendar-year-next]')) { viewDate = new Date(viewDate.getFullYear() + 12, viewDate.getMonth(), 1); render(); return; }
        const selectedYear = event.target.closest('[data-calendar-year]');
        if (selectedYear) { viewDate = new Date(Number(selectedYear.dataset.calendarYear), viewDate.getMonth(), 1); choosingYear = false; render(); return; }
        if (event.target.closest('[data-calendar-clear]')) { valueInput.value = ''; label.textContent = 'mm/dd/yyyy'; trigger.classList.remove('has-value'); close(); return; }
        if (event.target.closest('[data-calendar-today]')) { const today = new Date(); valueInput.value = formatValue(today); label.textContent = formatLabel(today); trigger.classList.add('has-value'); close(); }
      });
    });
    document.addEventListener('click', function (event) { document.querySelectorAll('[data-date-picker].is-open').forEach(picker => { if (!picker.contains(event.target)) picker.querySelector('[data-date-trigger]').click(); }); });
  }

  function loadTable(extra) {
    appGet(AJAX_URL + '?' + buildParams(extra || {}).toString()).then(data => {
      if (data.success) { wrap.innerHTML = data.html; bindRowEvents(); updateFilterChip(); if (applyDot) applyDot.classList.add('d-none'); }
      else if (!data.session_expired) { appToast('error', data.message || 'Unable to load committees right now.'); }
    });
  }

  /* --- Committee detail modal (card click) --- */
  const viewModalEl = document.getElementById('committeeViewModal');
  const viewModal = viewModalEl ? new bootstrap.Modal(viewModalEl) : null;
  const ROLE_COLORS = { 'Chairperson': 'primary', 'Vice Chairperson': 'info', 'Member': 'secondary' };
  const STATUS_COLORS = { 'Active': 'success', 'Inactive': 'secondary', 'Dissolved': 'danger' };
  const assignMemberModalEl = document.getElementById('committeeAssignMemberModal');
  const assignMemberModal = assignMemberModalEl ? new bootstrap.Modal(assignMemberModalEl) : null;
  const assignMemberForm = document.getElementById('committeeAssignMemberForm');
  let openCommitteeId = null;

  function populateAssignMemberForm(id, users) {
    const select = document.getElementById('cvmAssignUser');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select a user --</option>';
    (users || []).forEach(function (user) {
      const option = document.createElement('option');
      option.value = user.id;
      option.textContent = (user.full_name || '') + ' (' + (user.role_name || '') + ')';
      select.appendChild(option);
    });
    select.disabled = !(users && users.length);
    document.getElementById('cvmAssignEmpty').textContent = users && users.length
      ? 'Only active users not already assigned are shown.'
      : 'All active users are already assigned to this committee.';
    document.getElementById('cvmAssignCommitteeId').value = id;
  }

  function openCommitteeView(id, href) {
    if (!viewModal) { window.location.href = href; return; }
    appGet(window.APP_URL + '/modules/committees/ajax_get.php?id=' + encodeURIComponent(id)).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message || 'Unable to load committee.'); return; }
      const c = data.committee || {};
      openCommitteeId = id;
      const status = c.status || '';
      const statusEl = viewModalEl.querySelector('#cvmStatus');
      statusEl.textContent = status;
      statusEl.className = 'badge ms-1 bg-' + (STATUS_COLORS[status] || 'secondary');
      viewModalEl.querySelector('#cvmName').textContent = c.committee_name || 'Committee';
      viewModalEl.querySelector('#cvmCreated').textContent = data.created_label || '—';
      viewModalEl.querySelector('#cvmDescription').textContent = c.description || 'No description provided.';
      const jurisdictionSelect = viewModalEl.querySelector('#cvmJurisdictionSelect');
      const jurisdictions = data.jurisdictions || [];
      jurisdictionSelect.replaceChildren();
      jurisdictions.forEach(function (jurisdiction) {
        const option = document.createElement('option');
        option.value = jurisdiction.jurisdiction_id;
        option.textContent = jurisdiction.jurisdiction_name;
        jurisdictionSelect.appendChild(option);
      });
      viewModalEl.querySelector('#cvmJurisdictionCount').textContent = jurisdictions.length
        ? jurisdictions.length + (jurisdictions.length === 1 ? ' jurisdiction' : ' jurisdictions')
        : 'No jurisdictions assigned';
      const jurisdictionDetail = viewModalEl.querySelector('#cvmJurisdictionDetail');
      function showSelectedJurisdiction() {
        const selected = jurisdictions.find(item => String(item.jurisdiction_id) === jurisdictionSelect.value);
        jurisdictionSelect.classList.toggle('d-none', jurisdictions.length === 0);
        jurisdictionDetail.classList.toggle('d-none', jurisdictions.length > 0 && !selected);
        viewModalEl.querySelector('#cvmJurisdictionName').textContent = selected
          ? selected.jurisdiction_name
          : 'No jurisdiction assigned';
        viewModalEl.querySelector('#cvmJurisdictionDescription').textContent =
          selected && selected.description ? selected.description : (selected ? 'No description provided.' : '');
        const scope = selected && selected.scope_definition ? selected.scope_definition : '';
        viewModalEl.querySelector('#cvmJurisdictionScope').textContent = scope || 'No scope definition provided.';
        viewModalEl.querySelector('#cvmJurisdictionScopeWrap').classList.toggle('d-none', !scope);
      }
      jurisdictionSelect.disabled = jurisdictions.length < 2;
      jurisdictionSelect.onchange = showSelectedJurisdiction;
      showSelectedJurisdiction();
      const members = data.members || [];
      populateAssignMemberForm(id, data.available_users || []);
      viewModalEl.querySelector('#cvmMemberCount').textContent = members.length;
      const tbody = viewModalEl.querySelector('#cvmMembersBody');
      const canManageMembers = tbody.dataset.canManageMembers === '1';
      tbody.innerHTML = '';
      if (!members.length) {
        tbody.innerHTML = '<tr><td colspan="' + (canManageMembers ? '6' : '5') + '" class="text-center text-muted py-3">No members assigned yet.</td></tr>';
      } else {
        members.forEach(function (m) {
          const tr = document.createElement('tr');
          const nameTd = document.createElement('td');
          const nm = document.createElement('div'); nm.className = 'fw-semibold'; nm.textContent = m.full_name || '';
          const em = document.createElement('div'); em.className = 'small text-muted'; em.textContent = m.email || '';
          nameTd.appendChild(nm); nameTd.appendChild(em);
          const roleTd = document.createElement('td');
          const rB = document.createElement('span'); rB.className = 'badge bg-light text-dark border'; rB.textContent = m.role_name || '';
          roleTd.appendChild(rB);
          const cRoleTd = document.createElement('td');
          const cB = document.createElement('span'); cB.className = 'badge bg-' + (ROLE_COLORS[m.member_role] || 'secondary'); cB.textContent = m.member_role || '';
          cRoleTd.appendChild(cB);
          const groupTd = document.createElement('td');
          if (m.political_group) {
            const gB = document.createElement('span');
            const groupClass = m.political_group === 'Majority'
              ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
              : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            gB.className = 'badge rounded-pill ' + groupClass;
            gB.textContent = m.political_group;
            groupTd.appendChild(gB);
          } else {
            groupTd.innerHTML = '<span class="text-muted small">—</span>';
          }
          const dateTd = document.createElement('td'); dateTd.className = 'text-end small text-muted'; dateTd.textContent = m.assigned_label || '—';
          tr.appendChild(nameTd); tr.appendChild(roleTd); tr.appendChild(cRoleTd); tr.appendChild(groupTd); tr.appendChild(dateTd);
          if (canManageMembers) {
            const actionTd = document.createElement('td');
            actionTd.className = 'text-end';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.dataset.removeCommitteeMember = m.committee_member_id;
            removeBtn.dataset.memberName = m.full_name || 'This member';
            removeBtn.setAttribute('aria-label', 'Remove ' + (m.full_name || 'member') + ' from committee');
            removeBtn.title = 'Remove from committee';
            removeBtn.innerHTML = '<i class="bi bi-person-dash" aria-hidden="true"></i>';
            actionTd.appendChild(removeBtn);
            tr.appendChild(actionTd);
          }
          tbody.appendChild(tr);
        });
      }
      viewModal.show();
    });
  }

  const cvmMembersBody = viewModalEl && viewModalEl.querySelector('#cvmMembersBody');
  if (cvmMembersBody) {
    cvmMembersBody.addEventListener('click', function (event) {
      const removeBtn = event.target.closest('[data-remove-committee-member]');
      if (!removeBtn || !openCommitteeId) return;

      const memberId = removeBtn.dataset.removeCommitteeMember;
      const memberName = removeBtn.dataset.memberName;
      Swal.fire({
        title: 'Remove committee member?',
        text: memberName + ' will no longer be an active member of this committee. Existing workload history will be retained.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#a4302a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Remove member'
      }).then(function (result) {
        if (!result.isConfirmed) return;

        removeBtn.disabled = true;
        appPost(window.APP_URL + '/modules/committees/ajax_member_remove.php', {
          id: memberId,
          csrf_token: window.APP_CSRF_TOKEN || ''
        }).then(function (response) {
          if (response.success) {
            appToast('success', response.message || 'Member removed from committee.');
            openCommitteeView(openCommitteeId, window.location.href);
          } else {
            removeBtn.disabled = false;
            if (!response.session_expired) Swal.fire('Error', response.message || 'Unable to remove member.', 'error');
          }
        });
      });
    });
  }

  const assignMemberBtn = document.getElementById('btnCvmAssignMember');
  if (assignMemberBtn && assignMemberModal) {
    assignMemberBtn.addEventListener('click', function () {
      if (viewModal) viewModal.hide();
      assignMemberModal.show();
    });
  }

  if (assignMemberForm) {
    assignMemberForm.addEventListener('submit', function (e) {
      e.preventDefault();
      appPost(window.APP_URL + '/modules/committees/ajax_member_save.php', Object.fromEntries(new FormData(assignMemberForm)))
        .then(data => {
          if (data.success) {
            assignMemberModal.hide();
            appToast('success', data.message);
            if (openCommitteeId) openCommitteeView(openCommitteeId, window.location.href);
          } else if (!data.session_expired) {
            Swal.fire('Error', data.message, 'error');
          }
        });
    });
  }

  function bindRowEvents() {
    wrap.querySelectorAll('.committee-row').forEach(row => {
      row.style.cursor = 'pointer';
      const open = function () { openCommitteeView(row.getAttribute('data-id'), row.getAttribute('data-href')); };
      row.addEventListener('click', function (e) {
        if (e.target.closest('button, input, select, textarea, .committee-actions')) return;
        if (e.target.closest('a') && !e.target.closest('.committee-card-title')) return;
        e.preventDefault();
        open();
      });
      row.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target === row) {
          e.preventDefault();
          open();
        }
      });
    });
    wrap.querySelectorAll('.pagination a.page-link').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        currentPage = parseInt(new URL(a.href).searchParams.get('page') || '1', 10);
        loadTable({ page: currentPage });
      });
    });
    wrap.querySelectorAll('.btn-edit-committee').forEach(btn => {
      btn.addEventListener('click', () => openEditModal(btn.getAttribute('data-id')));
    });
  }

  if (window.registerDeleteHandler) window.registerDeleteHandler(loadTable);

  /* Filters are Apply-gated: any edit just marks the Apply button pending;
     the table reloads on submit (Apply click or Enter in any field). */
  const applyDot = document.getElementById('applyPendingDot');
  function markPending() { if (applyDot) applyDot.classList.remove('d-none'); }
  const filterReset = document.getElementById('filterReset');
  if (filterForm) {
    filterForm.addEventListener('input', markPending);
    filterForm.addEventListener('change', markPending);
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      currentPage = 1;
      loadTable();
    });
  }
  if (filterReset && filterForm) {
    filterReset.addEventListener('click', function () {
      filterForm.reset();
      currentPage = 1;
      loadTable();
    });
  }

  bindRowEvents();
  initDatePickers();

  /* ================= Create / Edit Modal ================= */
  const modalEl = document.getElementById('committeeModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('committeeForm');
  const committeeStepPanels = Array.from(document.querySelectorAll('[data-committee-step-panel]'));
  const committeeStepIndicators = Array.from(document.querySelectorAll('[data-committee-step-indicator]'));
  const committeePrevious = document.getElementById('committeeStepPrevious');
  const committeeNext = document.getElementById('committeeStepNext');
  const committeeSave = document.getElementById('committeeStepSave');
  let committeeStep = 1;

  function goToCommitteeStep(step) {
    committeeStep = Math.max(1, Math.min(3, step));
    committeeStepPanels.forEach(panel => panel.classList.toggle('is-active', Number(panel.dataset.committeeStepPanel) === committeeStep));
    committeeStepIndicators.forEach(indicator => {
      const indicatorStep = Number(indicator.dataset.committeeStepIndicator);
      indicator.classList.toggle('is-active', indicatorStep === committeeStep);
      indicator.classList.toggle('is-completed', indicatorStep < committeeStep);
    });
    committeePrevious.disabled = committeeStep === 1;
    committeeNext.style.display = committeeStep === 3 ? 'none' : 'inline-flex';
    const isEditing = Number(document.getElementById('cm_id').value) > 0;
    committeeSave.style.display = committeeStep === 3 || (isEditing && committeeStep === 2) ? 'inline-flex' : 'none';
    committeeSave.innerHTML = isEditing
      ? '<i class="bi bi-check-circle"></i> Save Changes'
      : '<i class="bi bi-check-circle"></i> Save Committee';
  }

  function validateCommitteeStep() {
    const panel = committeeStepPanels.find(item => Number(item.dataset.committeeStepPanel) === committeeStep);
    for (const field of (panel ? Array.from(panel.querySelectorAll('[required]')) : [])) {
      if (!field.checkValidity()) { field.reportValidity(); return false; }
    }
    return true;
  }

  committeePrevious.addEventListener('click', () => goToCommitteeStep(committeeStep - 1));
  committeeNext.addEventListener('click', () => {
    if (validateCommitteeStep()) goToCommitteeStep(committeeStep + 1);
  });

  const addBtn = document.getElementById('btnAddCommittee');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      form.reset();
      document.getElementById('cm_id').value = 0;
      document.getElementById('cm_status').value = 'Active';
      goToCommitteeStep(1);
      document.getElementById('committeeModalTitle').innerHTML = '<i class="bi bi-diagram-3"></i> New Committee';
      modal.show();
    });
  }

  function openEditModal(id) {
    appGet(window.APP_URL + '/modules/committees/ajax_get.php?id=' + id).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message); return; }
      const c = data.committee;
      form.reset();
      document.getElementById('cm_id').value = c.committee_id;
      document.getElementById('cm_name').value = c.committee_name || '';
      document.getElementById('cm_description').value = c.description || '';
      document.getElementById('cm_jurisdiction').value = c.jurisdiction_id || '';
      document.getElementById('cm_date_created').value = c.date_created || '';
      document.getElementById('cm_status').value = c.status || 'Active';
      goToCommitteeStep(2);
      document.getElementById('committeeModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Committee';
      modal.show();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/modules/committees/ajax_save.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) { modal.hide(); appToast('success', data.message); loadTable(); }
        else if (!data.session_expired) { Swal.fire('Error', data.message, 'error'); }
      });
  });
})();
