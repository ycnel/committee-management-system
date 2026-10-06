/**
 * Standard task template management and Chairperson request workflow.
 */
document.addEventListener('DOMContentLoaded', function () {
  const tableBody = document.getElementById('taskTemplateRows');
  const filter = document.getElementById('taskTemplateFilter');
  const requestRows = document.getElementById('taskTemplateRequestRows');
  if (!tableBody || !filter || !requestRows) return;

  const endpoint = window.APP_URL + '/modules/jurisdictions/ajax_task_templates.php';
  const requestEndpoint = window.APP_URL + '/modules/workload/ajax_task_template_requests.php';
  const isAdmin = requestRows.dataset.admin === 'true';
  const templateForm = document.getElementById('taskTemplateForm');
  const templateModalElement = document.getElementById('taskTemplateModal');
  const requestForm = document.getElementById('taskTemplateRequestForm');
  const requestModalElement = document.getElementById('taskTemplateRequestModal');
  const templateModal = templateModalElement ? new bootstrap.Modal(templateModalElement) : null;
  const requestModal = requestModalElement ? new bootstrap.Modal(requestModalElement) : null;

  const formFields = templateForm ? {
    id: document.getElementById('taskTemplateId'),
    name: document.getElementById('taskTemplateName'),
    description: document.getElementById('taskTemplateDescription'),
    proofRequirement: document.getElementById('taskTemplateProofRequirement'),
    jurisdiction: document.getElementById('taskTemplateJurisdiction'),
    order: document.getElementById('taskTemplateOrder'),
    required: document.getElementById('taskTemplateRequired'),
    active: document.getElementById('taskTemplateActive'),
  } : null;

  function showMessage(message, type) {
    if (window.appToast) window.appToast(type || 'error', message);
    else window.alert(message);
  }

  function addCell(row, text) {
    const cell = document.createElement('td');
    cell.textContent = text;
    row.appendChild(cell);
    return cell;
  }

  function renderTemplates(templates) {
    tableBody.replaceChildren();
    if (!templates.length) {
      const row = document.createElement('tr');
      const cell = addCell(row, 'No task templates found for this filter.');
      cell.colSpan = 6;
      cell.className = 'text-center text-muted py-4';
      tableBody.appendChild(row);
      return;
    }

    templates.forEach(function (template) {
      const row = document.createElement('tr');
      const taskCell = document.createElement('td');
      const name = document.createElement('strong');
      name.textContent = template.task_name;
      taskCell.appendChild(name);
      if (template.description) {
        const description = document.createElement('small');
        description.className = 'd-block text-muted';
        description.textContent = template.description;
        taskCell.appendChild(description);
      }
      if (template.proof_requirement) {
        const proofRequirement = document.createElement('small');
        proofRequirement.className = 'd-block text-muted mt-1';
        proofRequirement.textContent = 'Completion proof: ' + template.proof_requirement;
        taskCell.appendChild(proofRequirement);
      }
      row.appendChild(taskCell);
      addCell(row, template.jurisdiction_id
        ? (template.jurisdiction_name || 'Jurisdiction-specific')
        : 'Core / Global');
      addCell(row, String(template.sequence_order));
      addCell(row, Number(template.is_required) === 1 ? 'Yes' : 'No');
      addCell(row, Number(template.is_active) === 1 ? 'Active' : 'Inactive');

      const actions = document.createElement('td');
      actions.className = 'text-end text-nowrap';
      if (isAdmin) {
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.className = 'btn btn-outline-secondary btn-sm me-1';
        edit.dataset.templateAction = 'edit';
        edit.textContent = 'Edit';
        actions.appendChild(edit);

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = Number(template.is_active) === 1
          ? 'btn btn-outline-secondary btn-sm'
          : 'btn btn-outline-success btn-sm';
        toggle.dataset.templateAction = 'toggle';
        toggle.textContent = Number(template.is_active) === 1 ? 'Deactivate' : 'Activate';
        actions.appendChild(toggle);
      } else {
        actions.textContent = 'Managed by Administrator';
      }
      row.appendChild(actions);
      row.templateData = template;
      tableBody.appendChild(row);
    });
  }

  function loadTemplates() {
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading templates...</td></tr>';
    appGet(endpoint + '?jurisdiction_id=' + encodeURIComponent(filter.value))
      .then(function (data) {
        if (!data.success) {
          tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4"></td></tr>';
          tableBody.querySelector('td').textContent = data.message || 'Unable to load task templates.';
          return;
        }
        renderTemplates(data.templates || []);
      });
  }

  function renderRequests(requests) {
    requestRows.replaceChildren();
    if (!requests.length) {
      const row = document.createElement('tr');
      const cell = addCell(row, isAdmin ? 'No template requests found.' : 'You have not submitted any template requests.');
      cell.colSpan = isAdmin ? 6 : 4;
      cell.className = 'text-center text-muted py-4';
      requestRows.appendChild(row);
      return;
    }

    requests.forEach(function (request) {
      const row = document.createElement('tr');
      if (isAdmin) addCell(row, request.requester_name || 'Account unavailable');

      const taskCell = document.createElement('td');
      const taskName = document.createElement('strong');
      taskName.textContent = request.task_name;
      taskCell.appendChild(taskName);
      if (request.description) {
        const description = document.createElement('small');
        description.className = 'd-block text-muted';
        description.textContent = request.description;
        taskCell.appendChild(description);
      }
      if (request.admin_response) {
        const response = document.createElement('small');
        response.className = 'd-block text-muted mt-1';
        response.textContent = 'Admin response: ' + request.admin_response;
        taskCell.appendChild(response);
      }
      row.appendChild(taskCell);
      addCell(row, request.jurisdiction_id
        ? (request.jurisdiction_name || 'Jurisdiction unavailable')
        : 'Core / Global');
      addCell(row, new Date(request.created_at.replace(' ', 'T')).toLocaleDateString());

      const statusCell = addCell(row, request.status);
      statusCell.innerHTML = '';
      const status = document.createElement('span');
      status.className = 'badge ' + (request.status === 'Pending'
        ? 'text-bg-warning'
        : (request.status === 'Approved' ? 'text-bg-success' : 'text-bg-secondary'));
      status.textContent = request.status;
      statusCell.appendChild(status);

      if (isAdmin) {
        const actions = document.createElement('td');
        actions.className = 'text-end text-nowrap';
        if (request.status === 'Pending') {
          ['approve', 'reject'].forEach(function (action) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = action === 'approve'
              ? 'btn btn-outline-success btn-sm me-1'
              : 'btn btn-outline-danger btn-sm';
            button.dataset.requestAction = action;
            button.textContent = action === 'approve' ? 'Approve' : 'Reject';
            actions.appendChild(button);
          });
        } else {
          actions.textContent = request.reviewer_name
            ? 'Reviewed by ' + request.reviewer_name
            : 'Reviewed';
        }
        row.appendChild(actions);
        row.dataset.requestId = request.id;
      }
      requestRows.appendChild(row);
    });
  }

  function loadRequests() {
    const requestFilter = document.getElementById('taskTemplateRequestFilter');
    const status = isAdmin && requestFilter ? requestFilter.value : 'all';
    requestRows.innerHTML = '<tr><td colspan="' + (isAdmin ? 6 : 4)
      + '" class="text-center text-muted py-4">Loading requests...</td></tr>';
    appGet(requestEndpoint + '?status=' + encodeURIComponent(status))
      .then(function (data) {
        if (!data.success) {
          requestRows.innerHTML = '<tr><td colspan="' + (isAdmin ? 6 : 4)
            + '" class="text-center text-danger py-4"></td></tr>';
          requestRows.querySelector('td').textContent = data.message || 'Unable to load template requests.';
          return;
        }
        renderRequests(data.requests || []);
      });
  }

  function resetTemplateForm() {
    templateForm.reset();
    formFields.id.value = '0';
    formFields.order.value = '0';
    formFields.active.checked = true;
    document.getElementById('taskTemplateModalTitle').textContent = 'Add Standard Task Template';
  }

  const addTemplateButton = document.getElementById('btnAddTaskTemplate');
  if (addTemplateButton && templateForm && templateModal) {
    addTemplateButton.addEventListener('click', function () {
      resetTemplateForm();
      templateModal.show();
    });

    templateForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const values = Object.fromEntries(new FormData(templateForm));
      values.action = 'save';
      values.csrf_token = window.APP_CSRF_TOKEN;
      values.is_required = formFields.required.checked ? '1' : '0';
      values.is_active = formFields.active.checked ? '1' : '0';

      appPost(endpoint, values).then(function (data) {
        if (!data.success) {
          showMessage(data.message || 'Unable to save task template.');
          return;
        }
        templateModal.hide();
        loadTemplates();
        showMessage(data.message || 'Task template saved.', 'success');
      });
    });
  }

  const requestButton = document.getElementById('btnRequestTaskTemplate');
  if (requestButton && requestForm && requestModal) {
    requestButton.addEventListener('click', function () {
      requestForm.reset();
      document.getElementById('taskTemplateRequestOrder').value = '0';
      requestModal.show();
    });

    requestForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const values = Object.fromEntries(new FormData(requestForm));
      values.action = 'create';
      values.csrf_token = window.APP_CSRF_TOKEN;
      values.is_required = document.getElementById('taskTemplateRequestRequired').checked ? '1' : '0';

      appPost(requestEndpoint, values).then(function (data) {
        if (!data.success) {
          showMessage(data.message || 'Unable to submit template request.');
          return;
        }
        requestModal.hide();
        loadRequests();
        showMessage(data.message || 'Template request submitted.', 'success');
      });
    });
  }

  filter.addEventListener('change', loadTemplates);
  const requestFilter = document.getElementById('taskTemplateRequestFilter');
  if (requestFilter) requestFilter.addEventListener('change', loadRequests);

  tableBody.addEventListener('click', function (event) {
    const button = event.target.closest('[data-template-action]');
    if (!button || !isAdmin) return;
    const row = button.closest('tr');
    const template = row && row.templateData;
    if (!template) return;

    if (button.dataset.templateAction === 'edit') {
      resetTemplateForm();
      formFields.id.value = template.id;
      formFields.name.value = template.task_name;
      formFields.description.value = template.description || '';
      formFields.proofRequirement.value = template.proof_requirement || '';
      formFields.jurisdiction.value = template.jurisdiction_id || '';
      formFields.order.value = template.sequence_order;
      formFields.required.checked = Number(template.is_required) === 1;
      formFields.active.checked = Number(template.is_active) === 1;
      document.getElementById('taskTemplateModalTitle').textContent = 'Edit Standard Task Template';
      templateModal.show();
      return;
    }

    appPost(endpoint, {
      csrf_token: window.APP_CSRF_TOKEN,
      action: 'toggle',
      id: template.id,
      is_active: Number(template.is_active) === 1 ? 0 : 1,
    }).then(function (data) {
      if (!data.success) {
        showMessage(data.message || 'Unable to update task template.');
        return;
      }
      loadTemplates();
    });
  });

  requestRows.addEventListener('click', function (event) {
    const button = event.target.closest('[data-request-action]');
    if (!button || !isAdmin) return;
    const action = button.dataset.requestAction;
    const requestId = button.closest('tr').dataset.requestId;
    const confirmAction = Swal.fire({
        title: action === 'approve' ? 'Approve template request?' : 'Reject template request?',
        text: action === 'approve'
          ? 'This will add the requested task to the available templates.'
          : '',
        input: 'textarea',
        inputLabel: action === 'approve'
          ? 'Optional response for the Chairperson'
          : 'Optional response for the Chairperson',
        inputAttributes: { maxlength: 1000 },
        showCancelButton: true,
        confirmButtonText: action === 'approve' ? 'Approve and add template' : 'Reject request',
        confirmButtonColor: action === 'approve' ? undefined : '#a13b3b',
      });

    confirmAction.then(function (result) {
      if (!result.isConfirmed) return;
      appPost(requestEndpoint, {
        csrf_token: window.APP_CSRF_TOKEN,
        action: action,
        id: requestId,
        admin_response: result.value || '',
      }).then(function (data) {
        if (!data.success) {
          showMessage(data.message || 'Unable to review template request.');
          return;
        }
        loadRequests();
        if (action === 'approve') loadTemplates();
        showMessage(data.message || 'Template request reviewed.', 'success');
      });
    });
  });

  loadTemplates();
  loadRequests();
});
