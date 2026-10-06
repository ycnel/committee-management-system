/**
 * Loads the standard task templates available for the selected jurisdiction.
 */
(function () {
  const requestVersions = new WeakMap();

  function getFields(root) {
    const scope = root && root.querySelector ? root : document;
    const jurisdiction = scope.querySelector('#wl_jurisdiction');
    const template = scope.querySelector('#wl_template');
    const title = scope.querySelector('#wl_title');
    const form = scope.matches && scope.matches('form') ? scope : scope.querySelector('#taskForm');
    if (!jurisdiction || !template || !title || !form) return null;
    return { form, jurisdiction, template, title };
  }

  function showError(message) {
    if (window.appToast) window.appToast('error', message);
    else window.alert(message);
  }

  function setPlaceholder(select, text, disabled) {
    select.replaceChildren();
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = text;
    select.appendChild(placeholder);
    select.disabled = disabled;
  }

  function renderTemplates(select, templates) {
    setPlaceholder(select, '-- Select a Standard Task --', false);
    if (!templates.length) {
      setPlaceholder(select, '-- No Standard Tasks Available --', false);
      return;
    }

    const core = document.createElement('optgroup');
    core.label = 'Core / Global Tasks';
    const jurisdictionSpecific = document.createElement('optgroup');
    jurisdictionSpecific.label = 'Jurisdiction-Specific Tasks';

    templates.forEach(function (template) {
      const option = document.createElement('option');
      option.value = String(template.id);
      option.textContent = template.task_name;
      option.dataset.taskTitle = template.task_name;
      (template.jurisdiction_id == null ? core : jurisdictionSpecific).appendChild(option);
    });

    if (core.children.length) select.appendChild(core);
    if (jurisdictionSpecific.children.length) select.appendChild(jurisdictionSpecific);
  }

  async function loadTemplates(fields) {
    const version = (requestVersions.get(fields.template) || 0) + 1;
    requestVersions.set(fields.template, version);
    const jurisdictionId = fields.jurisdiction.value;
    if (!jurisdictionId) {
      setPlaceholder(fields.template, '-- Select Jurisdiction First --', true);
      return;
    }

    setPlaceholder(fields.template, '-- Loading Standard Tasks --', true);
    const endpoint = window.APP_URL + '/modules/workload/ajax_task_templates.php?jurisdiction_id='
      + encodeURIComponent(jurisdictionId);

    try {
      const response = await fetch(endpoint, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const data = await response.json();
      if (version !== requestVersions.get(fields.template)) return;
      if (!response.ok || !data.success) {
        setPlaceholder(fields.template, '-- Unable to Load Standard Tasks --', false);
        showError(data.message || 'Unable to load standard task templates.');
        return;
      }
      renderTemplates(fields.template, data.templates || []);
    } catch (error) {
      if (version !== requestVersions.get(fields.template)) return;
      setPlaceholder(fields.template, '-- Unable to Load Standard Tasks --', false);
      showError('Unable to load standard task templates. Please try again.');
    }
  }

  document.addEventListener('change', function (event) {
    const form = event.target.closest('form');
    const fields = getFields(form);
    if (!fields) return;

    if (event.target === fields.jurisdiction) {
      fields.template.value = '';
      loadTemplates(fields);
      return;
    }

    if (event.target === fields.template) {
      const selected = fields.template.options[fields.template.selectedIndex];
      if (selected && selected.dataset.taskTitle) fields.title.value = selected.dataset.taskTitle;
    }
  });

  function scheduleLoad(event) {
    if (!event.target || event.target.id !== 'taskModal') return;
    const fields = getFields(event.target);
    const taskId = fields && fields.form.querySelector('#wl_id');
    if (fields) fields.template.required = !taskId || Number(taskId.value) === 0;
    window.setTimeout(function () {
      const currentFields = getFields(event.target);
      if (currentFields && currentFields.jurisdiction.value) loadTemplates(currentFields);
    }, 60);
  }

  document.addEventListener('show.bs.modal', scheduleLoad);
  document.addEventListener('shown.bs.modal', scheduleLoad);

  document.addEventListener('DOMContentLoaded', function () {
    const fields = getFields();
    if (fields && fields.jurisdiction.value) loadTemplates(fields);
  });
})();
