/**
 * assets/js/users.js
 * ------------------------------------------------------------------
 * Powers pages/users.php: live AJAX search/filter/sort/pagination,
 * and the Create/Edit User modal.
 * ------------------------------------------------------------------
 */

(function () {
  const wrap = document.getElementById('usersTableWrap');
  const filterForm = document.getElementById('filterForm');
  if (!wrap || !filterForm) return;

  const AJAX_URL = window.APP_URL + '/pages/ajax_user_search.php';
  let currentSort = 'full_name';
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
      else if (!data.session_expired) { appToast('error', data.message || 'Unable to load users right now.'); }
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
    wrap.querySelectorAll('.btn-edit-user').forEach(btn => {
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
  const modalEl = document.getElementById('userModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('userForm');
  const passwordInput = document.getElementById('us_password');
  initPasswordPolicyHint('us_password');

  const addBtn = document.getElementById('btnAddUser');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      form.reset();
      document.getElementById('us_id').value = 0;
      document.getElementById('us_status').value = 'Active';
      passwordInput.setAttribute('required', 'required');
      document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus"></i> New User';
      modal.show();
    });
  }

  function openEditModal(id) {
    appGet(window.APP_URL + '/pages/ajax_user_get.php?id=' + id).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message); return; }
      const u = data.user;
      form.reset();
      document.getElementById('us_id').value = u.id;
      document.getElementById('us_full_name').value = u.full_name || '';
      document.getElementById('us_email').value = u.email || '';
      document.getElementById('us_role').value = u.role_id || '';
      document.getElementById('us_status').value = u.status || 'Active';
      passwordInput.removeAttribute('required');
      document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit User';
      modal.show();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/pages/ajax_user_save.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) { modal.hide(); appToast('success', data.message); loadTable(); }
        else if (!data.session_expired) { Swal.fire('Error', data.message, 'error'); }
      });
  });
})();
