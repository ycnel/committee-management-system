/**
 * Synchronizes the committee and jurisdiction fields in the workload task modal.
 */
(function () {
  function getFields(root) {
    const scope = root && root.querySelector ? root : document;
    const committee = scope.querySelector('#wl_committee');
    const jurisdiction = scope.querySelector('#wl_jurisdiction');
    if (!committee || !jurisdiction) return null;
    return { committee, jurisdiction };
  }

  function committeeJurisdictions(option) {
    return option
      ? (option.dataset.jurisdictionId || '').split(',').filter(Boolean)
      : [];
  }

  function updateValidity(fields) {
    const selectedCommittee = fields.committee.options[fields.committee.selectedIndex];
    const jurisdictionIds = committeeJurisdictions(selectedCommittee);
    const jurisdictionId = fields.jurisdiction.value;
    const mismatch = !!fields.committee.value
      && !jurisdictionIds.includes(jurisdictionId);

    fields.jurisdiction.setCustomValidity(
      mismatch ? 'Select a jurisdiction assigned to this committee.' : ''
    );
    return mismatch;
  }

  function filterJurisdictions(fields) {
    const previousJurisdictionId = fields.jurisdiction.value;
    const selectedCommittee = fields.committee.options[fields.committee.selectedIndex];
    const jurisdictionIds = committeeJurisdictions(selectedCommittee);
    const hasCommittee = !!fields.committee.value;

    Array.from(fields.jurisdiction.options).forEach(function (option) {
      if (!option.value) {
        option.hidden = false;
        option.disabled = false;
        return;
      }
      const belongsToCommittee = jurisdictionIds.includes(option.value);
      option.hidden = hasCommittee && !belongsToCommittee;
      option.disabled = hasCommittee && !belongsToCommittee;
    });

    if (hasCommittee && !jurisdictionIds.includes(fields.jurisdiction.value)) {
      fields.jurisdiction.value = jurisdictionIds[0] || '';
    }
    if (!hasCommittee) fields.jurisdiction.value = '';
    fields.jurisdiction.setCustomValidity('');
    if (fields.jurisdiction.value !== previousJurisdictionId) {
      fields.jurisdiction.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  document.addEventListener('change', function (event) {
    const form = event.target.closest('form');
    const fields = getFields(form);
    if (!fields) return;

    if (event.target === fields.committee) {
      filterJurisdictions(fields);
      updateValidity(fields);
      return;
    }

    if (event.target !== fields.jurisdiction) return;

    updateValidity(fields);
  });

  document.addEventListener('submit', function (event) {
    if (!event.target || event.target.id !== 'taskForm') return;

    const fields = getFields(event.target);
    if (!fields || !updateValidity(fields)) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    fields.jurisdiction.reportValidity();
  }, true);

  function scheduleModalSync(event) {
    if (!event.target || event.target.id !== 'taskModal') return;
    window.setTimeout(function () {
      const fields = getFields(event.target);
      if (!fields) return;
      filterJurisdictions(fields);
      updateValidity(fields);
    }, 50);
  }

  document.addEventListener('show.bs.modal', scheduleModalSync);
  document.addEventListener('shown.bs.modal', scheduleModalSync);
})();
