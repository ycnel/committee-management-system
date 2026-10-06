/**
 * assets/js/workload.js
 * ------------------------------------------------------------------
 * Powers modules/workload/index.php: live AJAX search/filter/sort/
 * pagination, the Assign Task modal — including the "Generate with
 * AI" flow (Standard Task -> Google Gemini -> auto-filled, editable
 * Description/Assign To/Priority/Due Date) —
 * and assignment recommendations.
 * ------------------------------------------------------------------
 */

/**
 * initWorkloadPage — binds the workload UI (table filters/sort/pagination,
 * Assign Task modal, AI generate). Called on DOMContentLoaded for the
 * standalone committee.php page, and again after the committee workload
 * modal injects committee.php?embed=1 markup on the index page.
 */
function initWorkloadPage() {
  const wrap = document.getElementById('workloadTableWrap');
  const filterForm = document.getElementById('filterForm');
  if (!wrap || !filterForm) return;

  const AJAX_URL = window.APP_URL + '/modules/workload/ajax_search.php';
  const filterCommitteeSelect = filterForm.querySelector('[name="committee_id"]');
  const taskStateInput = filterForm.querySelector('[name="task_state"]');
  const applyDot = document.getElementById('applyPendingDot');
  const liveSearch = filterForm.dataset.liveSearch === 'true';
  let currentPage = 1;
  let searchDebounceTimer = null;

  function buildParams(extra) {
    const data = new FormData(filterForm);
    const params = new URLSearchParams();
    for (const [key, val] of data.entries()) { if (val !== '') params.append(key, val); }
    params.set('page', extra && extra.page ? extra.page : currentPage);
    return params;
  }

  function loadTable(extra) {
    appGet(AJAX_URL + '?' + buildParams(extra || {}).toString()).then(data => {
      if (data.success) { wrap.innerHTML = data.html; bindRowEvents(); if (applyDot) applyDot.classList.add('d-none'); }
      else if (!data.session_expired) { appToast('error', data.message || 'Unable to load tasks right now.'); }
    });
  }

    wrap.addEventListener('click', function (event) {
      const button = event.target.closest('.workload-empty-assign');
      if (button) openCreateModal(button.getAttribute('data-committee-id'));
    });
    document.querySelectorAll('.workload-state-filter').forEach(button => {
      button.addEventListener('click', function () {
        if (!taskStateInput) return;
        taskStateInput.value = button.dataset.taskState;
        document.querySelectorAll('.workload-state-filter').forEach(filterButton => {
          const isActive = filterButton === button;
          filterButton.classList.toggle('is-active', isActive);
          filterButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        currentPage = 1;
        loadTable();
      });
    });
  function bindRowEvents() {
    wrap.querySelectorAll('.pagination a.page-link').forEach(a => {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        currentPage = parseInt(new URL(a.href).searchParams.get('page') || '1', 10);
        loadTable({ page: currentPage });
      });
    });
    wrap.querySelectorAll('.btn-edit-task').forEach(btn => {
      btn.addEventListener('click', () => openEditModal(btn.getAttribute('data-id')));
    });
  }

  function bindCommitteeCards() {
    const clearButton = document.getElementById('clearCommitteeFilter');
    if (clearButton) {
      clearButton.addEventListener('click', function () {
        window.location.href = 'index.php';
      });
    }
  }

  if (window.registerDeleteHandler) window.registerDeleteHandler(loadTable);

  /* Apply-gated filters: edits stage locally (gold pending dot) and only
     reload the task list on Apply / Enter; Reset clears and applies. */
  filterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    currentPage = 1;
    loadTable();
  });
  filterForm.addEventListener('input', function (e) {
    if (liveSearch && e.target.name === 'search') {
      if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(function () {
        currentPage = 1;
        loadTable();
      }, 250);
      return;
    }
    if (applyDot) applyDot.classList.remove('d-none');
  });
  filterForm.addEventListener('change', function () { if (applyDot) applyDot.classList.remove('d-none'); });
  const resetBtn = document.getElementById('filterReset');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      filterForm.reset();
      currentPage = 1;
      loadTable();
    });
  }

  bindRowEvents();
  bindCommitteeCards();

  /* ================= Create / Edit Modal ================= */
  const modalEl = document.getElementById('taskModal');
  if (!modalEl) return;

  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('taskForm');
  const committeeSelect = document.getElementById('wl_committee');
  const titleInput = document.getElementById('wl_title');
  const memberSelect = document.getElementById('wl_member');
  const descriptionInput = document.getElementById('wl_description');
  const prioritySelect = document.getElementById('wl_priority');
  const dueInput = document.getElementById('wl_due');
  const aiRecIdInput = document.getElementById('wl_ai_recommendation_id');

  const btnGenerate = document.getElementById('btnGenerateAI');
  const aiLoading = document.getElementById('wl_ai_loading');
  const aiLoadingText = document.getElementById('wl_ai_loading_text');
  const aiPanelWrap = document.getElementById('wl_ai_panel_wrap');
  const aiErrorBadge = document.getElementById('wl_ai_error_badge');
  const aiSummary = document.getElementById('wl_ai_summary');
  const aiReasoning = document.getElementById('wl_ai_reasoning');
  const aiDescBadge = document.getElementById('wl_ai_desc_badge');
  const stepPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
  const stepIndicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
  const previousStepButton = document.getElementById('taskStepPrevious');
  const nextStepButton = document.getElementById('taskStepNext');
  const saveStepButton = document.getElementById('taskStepSave');
  let currentStep = 1;

  function goToStep(step) {
    currentStep = Math.max(1, Math.min(3, step));
    stepPanels.forEach(panel => panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === currentStep));
    stepIndicators.forEach(indicator => {
      const indicatorStep = Number(indicator.dataset.stepIndicator);
      indicator.classList.toggle('is-active', indicatorStep === currentStep);
      indicator.classList.toggle('is-completed', indicatorStep < currentStep);
    });
    previousStepButton.disabled = currentStep === 1;
    nextStepButton.style.display = currentStep === 3 ? 'none' : 'inline-flex';
    saveStepButton.style.display = currentStep === 3 ? 'inline-flex' : 'none';
  }

  function validateCurrentStep() {
    const panel = stepPanels.find(item => Number(item.dataset.stepPanel) === currentStep);
    if (!panel) return true;
    const requiredFields = Array.from(panel.querySelectorAll('[required]'));
    for (const field of requiredFields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  }

  previousStepButton.addEventListener('click', () => goToStep(currentStep - 1));
  nextStepButton.addEventListener('click', () => {
    if (validateCurrentStep()) goToStep(currentStep + 1);
  });

  const LOADING_MESSAGES = [
    'AI is analyzing the task...',
    'Analyzing committee members and current workloads...',
    'Generating task description and workload recommendation...',
  ];
  let loadingMsgTimer = null;
  let aiRequestInFlight = false; // duplicate-request guard

  function startLoadingMessages() {
    let i = 0;
    aiLoadingText.textContent = LOADING_MESSAGES[0];
    loadingMsgTimer = setInterval(() => {
      i = (i + 1) % LOADING_MESSAGES.length;
      aiLoadingText.textContent = LOADING_MESSAGES[i];
    }, 1800);
  }
  function stopLoadingMessages() {
    if (loadingMsgTimer) { clearInterval(loadingMsgTimer); loadingMsgTimer = null; }
  }

  function setGenerateButtonBusy(busy) {
    aiRequestInFlight = busy;
    btnGenerate.disabled = busy;
    btnGenerate.innerHTML = busy
      ? '<span class="spinner-border spinner-border-sm"></span> Generating...'
      : '<i class="bi bi-stars"></i> Generate with AI';
  }

  function populateMemberOptions(members, selectedId, recommendedId = null) {
    let opts = '<option value="">-- Select Member --</option>';
    members.forEach(m => {
      const memberValue = m.member_id ?? m.committee_member_id;
      const memberName = m.name ?? m.full_name;
      const memberRole = m.role ?? m.member_role;
      const isSelected = String(memberValue) === String(selectedId);
      const isRecommended = recommendedId !== null && String(memberValue) === String(recommendedId);
      const labelSuffix = isRecommended ? ' — Recommended' : '';
      const sel = isSelected ? 'selected' : '';
      opts += `<option value="${memberValue}" ${sel}>${memberName} (${memberRole})${labelSuffix}</option>`;
    });
    memberSelect.innerHTML = opts;
  }

  function findMember(members, id) {
    return members.find(m => String(m.member_id) === String(id));
  }

  function priorityBadgeClass(p) {
    if (p === 'Urgent') return 'bg-danger';
    if (p === 'High') return 'bg-warning text-dark';
    if (p === 'Low') return 'bg-secondary';
    return 'bg-info text-dark';
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function (character) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[character];
    });
  }

  function renderAIResult(result) {
    aiPanelWrap.style.display = 'block';

    if (!result || !result.ai_available) {
      aiErrorBadge.style.display = 'inline-block';
      aiErrorBadge.textContent = 'Unavailable';
      aiSummary.innerHTML = '<span class="text-muted"><i class="bi bi-exclamation-circle"></i> ' +
        ((result && result.warning) || 'AI task generation is currently unavailable. Please fill in the task details manually.') +
        '</span>';
      aiReasoning.textContent = '';
      aiDescBadge.style.display = 'none';
      return;
    }

    aiErrorBadge.style.display = result.warning ? 'inline-block' : 'none';
    if (result.warning) { aiErrorBadge.textContent = 'Adjusted'; aiErrorBadge.title = result.warning; }

    const member = findMember(result.members || [], result.recommended_member_id);
    const memberLabel = member ? (member.name + ' (' + member.role + ')') : ('Member #' + result.recommended_member_id);
    const background = member && member.background ? member.background : {};
    const expertise = [background.primary_expertise, background.secondary_expertise, background.committee_expertise]
      .filter(value => value).join(', ') || 'No background expertise recorded';
    const experience = [
      background.years_experience ? background.years_experience + ' year(s)' : '',
      background.current_profession,
      background.degree_course,
    ].filter(value => value).join(' | ') || 'No professional or educational background recorded';
    const workload = member
      ? (member.active_assignments + ' current assignment(s), ' + member.active_committees + ' active committee(s)')
      : 'Unavailable';

    aiSummary.innerHTML =
      '<div class="d-flex flex-wrap gap-2 align-items-center">' +
        '<span><i class="bi bi-person-check text-success"></i> <strong>' + escapeHtml(memberLabel) + '</strong></span>' +
        '<span class="badge ' + priorityBadgeClass(result.priority) + '">' + (result.priority || '—') + ' Priority</span>' +
        '<span class="badge bg-light text-dark border"><i class="bi bi-calendar-event"></i> Due ' + (result.due_date || '—') + '</span>' +
      '</div>' +
      '<div class="mt-2"><strong>Relevant expertise:</strong> ' + escapeHtml(expertise) + '</div>' +
      '<div><strong>Relevant education/experience:</strong> ' + escapeHtml(experience) + '</div>' +
      '<div><strong>Current workload:</strong> ' + escapeHtml(workload) + '</div>';
    aiReasoning.innerHTML = result.reasoning ? ('<i class="bi bi-info-circle"></i> ' + escapeHtml(result.reasoning)) : '';
    if (result.excluded_unavailable && result.excluded_unavailable.length) {
      const names = result.excluded_unavailable.map(function (item) {
        return escapeHtml(item.name) + ' (' + escapeHtml(item.status) + ')';
      }).join(', ');
      aiReasoning.innerHTML += '<div class="text-muted small mt-1"><i class="bi bi-person-dash"></i> Not considered (unavailable): ' + names + '</div>';
    }

    // ---- Populate the actual editable form fields ----
    if (member) {
      // members list is already loaded into the select by the time this resolves (see populateMemberOptions above).
      memberSelect.value = String(result.recommended_member_id);
    }
    if (result.priority) prioritySelect.value = result.priority;
    if (result.due_date) dueInput.value = result.due_date;
    if (result.description) {
      descriptionInput.value = result.description;
      aiDescBadge.style.display = 'inline-flex';
    }

    aiRecIdInput.value = result.recommendation_id || '';
  }

  // Once the admin edits the AI-written description themselves, the
  // generated-content badge no longer applies to what's there.
  descriptionInput.addEventListener('input', function () {
    aiDescBadge.style.display = 'none';
  });

  function loadMembers(committeeId, selectedMemberId) {
    return new Promise((resolve) => {
      memberSelect.innerHTML = '<option value="">Loading...</option>';
      if (!committeeId) { memberSelect.innerHTML = '<option value="">-- Select Committee First --</option>'; resolve(); return; }
      appGet(window.APP_URL + '/modules/workload/ajax_members_by_committee.php?committee_id=' + committeeId).then(data => {
        if (!data.success) { memberSelect.innerHTML = '<option value="">-- No members --</option>'; resolve(); return; }
        let opts = '<option value="">-- Select Member --</option>';
        data.members.forEach(m => {
          const sel = String(m.committee_member_id) === String(selectedMemberId) ? 'selected' : '';
          const availability = m.availability_status && m.availability_status !== 'Available' ? ' — ' + m.availability_label : '';
          opts += '<option value="' + m.committee_member_id + '" ' + sel + '>' + m.full_name + ' (' + m.member_role + ')' + availability + '</option>';
        });
        memberSelect.innerHTML = opts || '<option value="">-- No active members --</option>';
        resolve();
      });
    });
  }

  function runGenerateAI() {
    if (aiRequestInFlight) return; // duplicate-request guard

    const committeeId = committeeSelect.value;
    const taskTitle = titleInput.value.trim();

    if (!committeeId) { appToast('error', 'Please select a committee first.'); return; }
    if (!taskTitle) { appToast('error', 'Please select a standard task first.'); return; }

    setGenerateButtonBusy(true);
    aiPanelWrap.style.display = 'none';
    aiLoading.style.display = 'block';
    startLoadingMessages();

    const params = new URLSearchParams();
    params.set('committee_id', committeeId);
    params.set('task_title', taskTitle);
    params.set('task_description', descriptionInput.value.trim());
    params.set('priority', prioritySelect.value);

    appGet(window.APP_URL + '/modules/workload/ajax_ai_recommend.php?' + params.toString())
      .then(data => {
        stopLoadingMessages();
        aiLoading.style.display = 'none';
        setGenerateButtonBusy(false);

        if (!data.success) {
          aiPanelWrap.style.display = 'block';
          aiErrorBadge.style.display = 'inline-block';
          aiErrorBadge.textContent = 'Error';
          aiSummary.innerHTML = '<span class="text-danger">' + (data.message || 'Unable to generate a recommendation right now.') + '</span>';
          aiReasoning.textContent = '';
          return;
        }

        const result = data.result;
        if (!result) {
          aiPanelWrap.style.display = 'block';
          aiSummary.innerHTML = '<span class="text-muted">' + escapeHtml(data.message || 'This committee has no active members yet.') + '</span>';
          aiReasoning.innerHTML = '';
          if (data.excluded_unavailable && data.excluded_unavailable.length) {
            const names = data.excluded_unavailable.map(function (item) {
              return escapeHtml(item.name) + ' (' + escapeHtml(item.status) + ')';
            }).join(', ');
            aiReasoning.innerHTML = '<div class="text-muted small"><i class="bi bi-person-dash"></i> ' + names + '</div>';
          }
          return;
        }

        // Populate the member dropdown from the AI response's own member
        // list (already the real active members for this committee) so
        // the select always has an option matching the recommendation,
        // while still keeping the rest of the members available as overrides.
        if (result.members) populateMemberOptions(result.members, result.recommended_member_id, result.recommended_member_id);
        renderAIResult(result);
        goToStep(2);
      })
      .catch(() => {
        stopLoadingMessages();
        aiLoading.style.display = 'none';
        setGenerateButtonBusy(false);
        aiPanelWrap.style.display = 'block';
        aiErrorBadge.style.display = 'inline-block';
        aiErrorBadge.textContent = 'Error';
        aiSummary.innerHTML = '<span class="text-danger">Unable to reach the AI recommendation engine.</span>';
      });
  }

  if (btnGenerate) btnGenerate.addEventListener('click', runGenerateAI);

  committeeSelect.addEventListener('change', function () {
    loadMembers(this.value, null);
    // Changing the committee invalidates any AI recommendation already shown.
    aiPanelWrap.style.display = 'none';
    aiDescBadge.style.display = 'none';
    aiRecIdInput.value = '';
  });

  const addBtn = document.getElementById('btnAddTask');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      openCreateModal(addBtn.getAttribute('data-committee-id') || '');
    });
  }

  function openCreateModal(committeeId) {
    form.reset();
    document.getElementById('wl_id').value = 0;
    committeeSelect.value = '';
    memberSelect.innerHTML = '<option value="">-- Select Committee First --</option>';
    aiPanelWrap.style.display = 'none';
    aiLoading.style.display = 'none';
    aiDescBadge.style.display = 'none';
    aiRecIdInput.value = '';
    goToStep(1);
    document.getElementById('taskModalTitle').innerHTML = '<i class="bi bi-bar-chart-steps"></i> Propose Task';
    saveStepButton.innerHTML = '<i class="bi bi-send"></i> Submit for Review';
    if (committeeId) {
      committeeSelect.value = committeeId;
      committeeSelect.dispatchEvent(new Event('change'));
    }
    modal.show();
  }

  function openEditModal(id) {
    appGet(window.APP_URL + '/modules/workload/ajax_get.php?id=' + id).then(data => {
      if (!data.success) { if (!data.session_expired) appToast('error', data.message); return; }
      const t = data.task;
      form.reset();
      document.getElementById('wl_id').value = t.workload_id;
      titleInput.value = t.task_title || '';
      descriptionInput.value = t.task_description || '';
      prioritySelect.value = t.priority || 'Medium';
      dueInput.value = t.due_date || '';
      committeeSelect.value = t.committee_id;
      loadMembers(t.committee_id, t.committee_member_id);
      aiPanelWrap.style.display = 'none';
      aiLoading.style.display = 'none';
      aiDescBadge.style.display = 'none';
      aiRecIdInput.value = '';
      goToStep(1);
      document.getElementById('taskModalTitle').innerHTML = '<i class="bi bi-pencil-square"></i> Edit Task';
      saveStepButton.innerHTML = '<i class="bi bi-check-circle"></i> Save Task';
      modal.show();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    appPost(window.APP_URL + '/modules/workload/ajax_save.php', Object.fromEntries(new FormData(form)))
      .then(data => {
        if (data.success) { modal.hide(); appToast('success', data.message); loadTable(); }
        else if (!data.session_expired) { Swal.fire('Error', data.message, 'error'); }
      });
  });
}

document.addEventListener('DOMContentLoaded', initWorkloadPage);
window.initWorkloadPage = initWorkloadPage;
