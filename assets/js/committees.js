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
  if (!wrap || !filterForm) return;

  const AJAX_URL = window.APP_URL + '/modules/committees/ajax_search.php';
  let currentPage = 1;

  function buildParams(extra) {
    const data = new FormData(filterForm);
    const params = new URLSearchParams();
    for (const [key, val] of data.entries()) { if (val !== '') params.append(key, val); }
    params.set('page', extra && extra.page ? extra.page : currentPage);
    return params;
  }

  function updateFilterChip() {
    const chip = document.getElementById('activeFilterCount');
    if (!chip) return;
    const ignored = new Set(['sort', 'dir']);
    let n = 0;
    for (const [key, val] of new FormData(filterForm).entries()) {
      if (!ignored.has(key) && val !== '') n++;
    }
    chip.textContent = n + ' filter' + (n === 1 ? '' : 's') + ' active';
    chip.classList.toggle('d-none', n === 0);
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

  function openCommitteeView(id, href) {
    if (!viewModal) { window.location.href = href; return; }
    appGet(window.APP_URL + '/modules/committees/ajax_get.php?id=' + encodeURIComponent(id)).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message || 'Unable to load committee.'); return; }
      const c = data.committee || {};
      const status = c.status || '';
      const statusEl = viewModalEl.querySelector('#cvmStatus');
      statusEl.textContent = status;
      statusEl.className = 'badge ms-1 bg-' + (STATUS_COLORS[status] || 'secondary');
      viewModalEl.querySelector('#cvmName').textContent = c.committee_name || 'Committee';
      viewModalEl.querySelector('#cvmJurisdiction').textContent = c.jurisdiction_name || 'Unassigned';
      viewModalEl.querySelector('#cvmCategory').textContent = c.jurisdiction_category || '—';
      viewModalEl.querySelector('#cvmCreated').textContent = data.created_label || '—';
      viewModalEl.querySelector('#cvmDescription').textContent = c.description || 'No description provided.';
      const members = data.members || [];
      viewModalEl.querySelector('#cvmMemberCount').textContent = members.length;
      const tbody = viewModalEl.querySelector('#cvmMembersBody');
      tbody.innerHTML = '';
      if (!members.length) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No members assigned yet.</td></tr>';
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
          const dateTd = document.createElement('td'); dateTd.className = 'text-end small text-muted'; dateTd.textContent = m.assigned_label || '—';
          tr.appendChild(nameTd); tr.appendChild(roleTd); tr.appendChild(cRoleTd); tr.appendChild(dateTd);
          tbody.appendChild(tr);
        });
      }
      viewModal.show();
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
  filterForm.addEventListener('input', markPending);
  filterForm.addEventListener('change', markPending);
  filterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    currentPage = 1;
    loadTable();
  });

  const filterReset = document.getElementById('filterReset');
  if (filterReset) {
    filterReset.addEventListener('click', function () {
      filterForm.reset();
      currentPage = 1;
      loadTable();
    });
  }

  bindRowEvents();

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
    committeeSave.style.display = committeeStep === 3 ? 'inline-flex' : 'none';
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
      goToCommitteeStep(1);
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
