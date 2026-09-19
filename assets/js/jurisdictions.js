/**
 * assets/js/jurisdictions.js
 * ------------------------------------------------------------------
 * Powers modules/jurisdictions/index.php: live AJAX search/filter/
 * sort/pagination, and the Create/Edit Jurisdiction modal.
 * ------------------------------------------------------------------
 */

(function () {
  const wrap = document.getElementById('jurisdictionsTableWrap');
  const filterForm = document.getElementById('filterForm');
  if (!wrap || !filterForm) return;

  const AJAX_URL = window.APP_URL + '/modules/jurisdictions/ajax_search.php';
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
      else if (!data.session_expired) { appToast('error', data.message || 'Unable to load jurisdictions right now.'); }
    });
  }

  function bindRowEvents() {
    wrap.querySelectorAll('.pagination a.page-link').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        currentPage = parseInt(new URL(a.href).searchParams.get('page') || '1', 10);
        loadTable({ page: currentPage });
      });
    });
    wrap.querySelectorAll('.btn-edit-jurisdiction').forEach(btn => {
      btn.addEventListener('click', () => openEditModal(btn.getAttribute('data-id')));
    });
  }

  /* ---- Read-only details modal (delegated: survives AJAX reloads) ---- */
  const viewModalEl = document.getElementById('jurisdictionViewModal');
  if (viewModalEl && window.bootstrap) {
    const viewModal = new bootstrap.Modal(viewModalEl);
    const text = function (id, value) {
      const el = document.getElementById(id);
      el.textContent = (value && String(value).trim() !== '') ? value : 'Not provided.';
      el.classList.toggle('text-muted', !(value && String(value).trim() !== ''));
    };

    wrap.addEventListener('click', function (e) {
      const link = e.target.closest('.btn-view-jurisdiction');
      if (!link) return;
      e.preventDefault();

      fetch(window.APP_URL + '/modules/jurisdictions/ajax_get.php?id=' + encodeURIComponent(link.getAttribute('data-id')), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store'
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.success) { appToast('error', data.message || 'Unable to load jurisdiction.'); return; }
          const j = data.jurisdiction;
          const statusBadge = function (s) { return 'bg-' + (s === 'Active' ? 'success' : (s === 'Dissolved' ? 'danger' : 'secondary')); };

          document.getElementById('jvName').textContent = j.jurisdiction_name;
          document.getElementById('jvNameDl').textContent = j.jurisdiction_name;
          const status = document.getElementById('jvStatus');
          status.textContent = j.status;
          status.className = 'badge ms-1 ' + statusBadge(j.status);
          const statusDl = document.getElementById('jvStatusDl');
          statusDl.innerHTML = '';
          const dlBadge = document.createElement('span');
          dlBadge.className = 'badge ' + statusBadge(j.status);
          dlBadge.textContent = j.status;
          statusDl.appendChild(dlBadge);
          document.getElementById('jvCount').textContent = data.committees.length;
          document.getElementById('jvCountBadge').textContent = data.committees.length;
          text('jvCategory', j.category);
          text('jvDescription', j.description);
          text('jvScope', j.scope_definition);
          text('jvCovered', j.covered_areas);
          text('jvResp', j.primary_responsibilities);
          text('jvMatters', j.typical_legislative_matters);
          text('jvOutside', j.outside_scope);

          const notesWrap = document.getElementById('jvNotesWrap');
          if (j.notes && j.notes.trim() !== '') {
            notesWrap.classList.remove('d-none');
            text('jvNotes', j.notes);
          } else {
            notesWrap.classList.add('d-none');
          }

          const tbody = document.getElementById('jvCommitteesBody');
          const empty = document.getElementById('jvCommitteesEmpty');
          const tableWrap = document.getElementById('jvCommitteesTableWrap');
          tbody.innerHTML = '';
          if (!data.committees.length) {
            empty.classList.remove('d-none');
            tableWrap.classList.add('d-none');
          } else {
            empty.classList.add('d-none');
            tableWrap.classList.remove('d-none');
            data.committees.forEach(function (c) {
              const tr = document.createElement('tr');
              const tdName = document.createElement('td');
              tdName.textContent = c.committee_name;
              const tdStatus = document.createElement('td');
              const badge = document.createElement('span');
              badge.className = 'badge ' + statusBadge(c.status);
              badge.textContent = c.status;
              tdStatus.appendChild(badge);
              const tdActions = document.createElement('td');
              tdActions.className = 'text-end';
              const view = document.createElement('a');
              view.className = 'btn btn-outline-secondary btn-sm';
              view.href = window.APP_URL + '/modules/committees/view.php?id=' + encodeURIComponent(c.committee_id);
              view.title = 'View committee';
              const icon = document.createElement('i');
              icon.className = 'bi bi-eye';
              view.appendChild(icon);
              tdActions.appendChild(view);
              tr.appendChild(tdName);
              tr.appendChild(tdStatus);
              tr.appendChild(tdActions);
              tbody.appendChild(tr);
            });
          }
          viewModal.show();
        })
        .catch(function () { appToast('error', 'Unable to load jurisdiction right now.'); });
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

  const resetBtn = document.getElementById('filterReset');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      filterForm.reset();
      currentPage = 1;
      loadTable();
    });
  }

  bindRowEvents();

  /* ================= Create / Edit Modal ================= */
  const modalEl = document.getElementById('jurisdictionModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('jurisdictionForm');
  const jurisdictionStepPanels = Array.from(document.querySelectorAll('[data-jurisdiction-step-panel]'));
  const jurisdictionStepIndicators = Array.from(document.querySelectorAll('[data-jurisdiction-step-indicator]'));
  const jurisdictionPrevious = document.getElementById('jurisdictionStepPrevious');
  const jurisdictionNext = document.getElementById('jurisdictionStepNext');
  const jurisdictionSave = document.getElementById('jurisdictionStepSave');
  let jurisdictionStep = 1;

  function goToJurisdictionStep(step) {
    jurisdictionStep = Math.max(1, Math.min(3, step));
    jurisdictionStepPanels.forEach(panel => panel.classList.toggle('is-active', Number(panel.dataset.jurisdictionStepPanel) === jurisdictionStep));
    jurisdictionStepIndicators.forEach(indicator => {
      const indicatorStep = Number(indicator.dataset.jurisdictionStepIndicator);
      indicator.classList.toggle('is-active', indicatorStep === jurisdictionStep);
      indicator.classList.toggle('is-completed', indicatorStep < jurisdictionStep);
    });
    jurisdictionPrevious.disabled = jurisdictionStep === 1;
    jurisdictionNext.style.display = jurisdictionStep === 3 ? 'none' : 'inline-flex';
    jurisdictionSave.style.display = jurisdictionStep === 3 ? 'inline-flex' : 'none';
  }

  function validateJurisdictionStep() {
    const panel = jurisdictionStepPanels.find(item => Number(item.dataset.jurisdictionStepPanel) === jurisdictionStep);
    for (const field of (panel ? Array.from(panel.querySelectorAll('[required]')) : [])) {
      if (!field.checkValidity()) { field.reportValidity(); return false; }
    }
    return true;
  }

  jurisdictionPrevious.addEventListener('click', () => goToJurisdictionStep(jurisdictionStep - 1));
  jurisdictionNext.addEventListener('click', () => {
    if (validateJurisdictionStep()) goToJurisdictionStep(jurisdictionStep + 1);
  });

  const addBtn = document.getElementById('btnAddJurisdiction');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      form.reset();
      document.getElementById('jd_id').value = 0;
      document.getElementById('jd_status').value = 'Active';
      goToJurisdictionStep(1);
      document.getElementById('jurisdictionModalTitle').innerHTML = '<i class="bi bi-geo-alt"></i> New Jurisdiction';
      modal.show();
    });
  }

  function openEditModal(id) {
    appGet(window.APP_URL + '/modules/jurisdictions/ajax_get.php?id=' + id).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message); return; }
      const j = data.jurisdiction;
      form.reset();
      document.getElementById('jd_id').value = j.jurisdiction_id;
      document.getElementById('jd_name').value = j.jurisdiction_name || '';
      document.getElementById('jd_category').value = j.category || '';
      document.getElementById('jd_description').value = j.description || '';
      document.getElementById('jd_scope_definition').value = j.scope_definition || '';
      document.getElementById('jd_covered_areas').value = j.covered_areas || '';
      document.getElementById('jd_primary_responsibilities').value = j.primary_responsibilities || '';
      document.getElementById('jd_typical_legislative_matters').value = j.typical_legislative_matters || '';
      document.getElementById('jd_outside_scope').value = j.outside_scope || '';
      document.getElementById('jd_notes').value = j.notes || '';
      document.getElementById('jd_status').value = j.status || 'Active';
      goToJurisdictionStep(1);
      document.getElementById('jurisdictionModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Jurisdiction';
      modal.show();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/modules/jurisdictions/ajax_save.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) { modal.hide(); appToast('success', data.message); loadTable(); }
        else if (!data.session_expired) { Swal.fire('Error', data.message, 'error'); }
      });
  });

  const editId = new URLSearchParams(window.location.search).get('edit');
  if (editId) openEditModal(editId);
})();
