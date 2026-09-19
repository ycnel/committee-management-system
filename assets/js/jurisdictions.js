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
  let currentSort = 'jurisdiction_name';
  let currentDir = 'asc';
  let currentPage = 1;
  let searchTimer = null;

  function buildParams(extra) {
    const data = new FormData(filterForm);
    const params = new URLSearchParams();
    for (const [key, val] of data.entries()) { if (val !== '') params.append(key, val); }
    params.set('sort', currentSort);
    params.set('dir', currentDir);
    params.set('page', extra && extra.page ? extra.page : currentPage);
    return params;
  }

  function loadTable(extra) {
    appGet(AJAX_URL + '?' + buildParams(extra || {}).toString()).then(data => {
      if (data.success) { wrap.innerHTML = data.html; bindRowEvents(); }
      else if (!data.session_expired) { appToast('error', data.message || 'Unable to load jurisdictions right now.'); }
    });
  }

  function bindRowEvents() {
    wrap.querySelectorAll('.sort-link').forEach(el => {
      el.style.cursor = 'pointer';
      el.addEventListener('click', function (e) {
        e.preventDefault();
        const col = el.getAttribute('data-sort');
        currentDir = (currentSort === col && currentDir === 'asc') ? 'desc' : 'asc';
        currentSort = col;
        currentPage = 1;
        loadTable();
      });
    });
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

  if (window.registerDeleteHandler) window.registerDeleteHandler(loadTable);

  filterForm.addEventListener('input', function (e) {
    if (e.target.id === 'searchInput') {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => { currentPage = 1; loadTable(); }, 400);
    }
  });
  filterForm.addEventListener('change', function (e) {
    if (e.target.id !== 'searchInput') { currentPage = 1; loadTable(); }
  });

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
