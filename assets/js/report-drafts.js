/**
 * assets/js/report-drafts.js
 * ------------------------------------------------------------------
 * modules/committee_reports/drafts.php — list + filter + create.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.APP_URL + '/modules/committee_reports';
    const csrfToken = window.APP_CSRF_TOKEN || '';
    const listBody = document.getElementById('draftsListBody');
    const printModalEl = document.getElementById('reportPrintModal');
    const printFrame = document.getElementById('reportPrintFrame');
    const printButton = document.getElementById('btnPrintReportPreview');
    const printModal = printModalEl && window.bootstrap ? new bootstrap.Modal(printModalEl) : null;

    if (listBody) listBody.addEventListener('click', function (event) {
      const button = event.target.closest('[data-report-print-url]');
      if (!button || !printFrame || !printModal) return;
      document.getElementById('reportPrintModalTitle').textContent =
        (button.getAttribute('data-report-title') || 'Committee Report') + ' — Print Preview';
      printFrame.src = button.getAttribute('data-report-print-url');
      printModal.show();
    });
    if (printButton) printButton.addEventListener('click', function () {
      if (printFrame && printFrame.contentWindow) printFrame.contentWindow.print();
    });
    if (printModalEl) printModalEl.addEventListener('hidden.bs.modal', function () {
      if (printFrame) printFrame.src = 'about:blank';
    });

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    function loadDrafts() {
      if (!listBody) return;
      listBody.innerHTML = '<p class="text-muted small p-3 mb-0">Loading…</p>';
      const committeeId = document.getElementById('filterCommittee').value;
      const status = document.getElementById('filterStatus').value;
      appGet(baseUrl + '/ajax_draft_list.php?committee_id=' + committeeId + '&status=' + encodeURIComponent(status))
        .then(function (data) {
          if (!data || !data.success) {
            listBody.innerHTML = '<p class="text-danger small p-3 mb-0">Could not load drafts.</p>';
            return;
          }
          const drafts = data.drafts || [];
          if (!drafts.length) {
            listBody.innerHTML = '<p class="text-muted small p-3 mb-0">No drafts match this filter.</p>';
            return;
          }
          let html = '<div class="committee-report-card-list">';
          drafts.forEach(function (d) {
            const editUrl = baseUrl + '/draft_edit.php?id=' + d.draft_id;
            const measureReference = d.reference_measure_no || d.legislative_matter_ref;
            html += '<article class="committee-report-card">'
              + '<div class="committee-report-card-top">'
              + '<span class="committee-report-number"><i class="bi bi-file-earmark-text"></i>' + escapeHtml(d.report_number || ('#' + d.draft_id)) + '</span>'
              + '<span class="badge bg-' + escapeHtml(d.status_color) + '">' + escapeHtml(d.status) + '</span>'
              + '</div>'
              + '<div class="committee-report-card-content">'
              + '<div class="committee-report-card-heading">'
              + '<div><a class="committee-report-subject" href="' + editUrl + '">' + escapeHtml(d.subject_title || d.report_title) + '</a>'
              + (d.ai_generated ? '<span class="committee-report-ai"><i class="bi bi-stars"></i> AI-assisted</span>' : '')
              + '<div class="committee-report-committee"><i class="bi bi-people"></i>' + escapeHtml(d.committee_name || '—') + '</div>'
              + (d.jurisdiction_name ? '<div class="committee-report-jurisdiction"><i class="bi bi-geo-alt"></i>' + escapeHtml(d.jurisdiction_name) + '</div>' : '')
              + '</div>'
              + '<span class="committee-report-updated"><i class="bi bi-clock"></i> Updated ' + escapeHtml(d.updated_at_human || d.created_at_human) + '</span>'
              + '</div>'
              + '<div class="committee-report-meta">'
              + '<div><span>Measure / Ordinance</span><strong>' + escapeHtml(d.proposed_ordinance_title || '—') + '</strong>'
              + (measureReference ? '<small>Official reference: ' + escapeHtml(measureReference) + '</small>' : '')
              + '<small>CMAS reference: ' + escapeHtml(d.internal_reference_no || '—') + '</small></div>'
              + '<div><span>Date referred</span><strong>' + escapeHtml(d.date_referred || '—') + '</strong></div>'
              + '<div><span>Prepared by</span><strong>' + escapeHtml(d.created_by_name || 'Former user') + '</strong></div>'
              + '</div>'
              + '<div class="committee-report-card-footer">'
              + '<div class="committee-report-actions"><a class="btn btn-outline-secondary btn-sm" href="' + editUrl + '"><i class="bi bi-pencil"></i> View / Edit</a>'
              + (d.status === 'For Review' || d.status === 'Under Review' ? ' <a class="btn btn-outline-primary btn-sm" href="' + editUrl + '"><i class="bi bi-check2-square"></i> Review</a>' : '')
              + ' <button type="button" class="btn btn-outline-secondary btn-sm" data-report-print-url="' + baseUrl + '/print.php?draft_id=' + encodeURIComponent(d.draft_id) + '" data-report-title="' + escapeHtml(d.report_number || ('#' + d.draft_id)) + '"><i class="bi bi-printer"></i> Print / PDF</button>'
              + ' <a class="btn btn-link btn-sm" href="' + editUrl + '#reportHistory"><i class="bi bi-clock-history"></i> History</a>'
              + '</div></div></div></article>';
          });
          html += '</div>';
          listBody.innerHTML = html;
        });
    }

    const applyBtn = document.getElementById('btnApplyFilter');
    if (applyBtn) applyBtn.addEventListener('click', loadDrafts);
    loadDrafts();

    // ---- New Draft / Generate AI Draft modal ----
    const modalEl = document.getElementById('newDraftModal');
    const dateRangeWrap = document.getElementById('ndDateRangeWrap');
    const committeeSelect = document.getElementById('ndCommittee');
    const committeeContext = document.getElementById('ndCommitteeContext');
    const jurisdictionInput = document.getElementById('ndJurisdiction');
    const legislativeFields = document.getElementById('ndLegislativeFields');
    const reportTypeSelect = document.getElementById('ndType');
    let isAiMode = false;

    function updateCommitteeContext() {
      const option = committeeSelect.options[committeeSelect.selectedIndex];
      jurisdictionInput.replaceChildren();
      if (!option || option.value === '0') {
        committeeContext.textContent = 'Select a committee to load its chairperson, members, and jurisdiction.';
        jurisdictionInput.add(new Option('Select a committee first', ''));
        jurisdictionInput.disabled = true;
        jurisdictionInput.required = false;
        return;
      }
      let jurisdictions = [];
      try {
        jurisdictions = JSON.parse(option.dataset.jurisdictions || '[]');
      } catch (error) {
        appToast('error', 'Could not load this committee’s jurisdictions. Refresh the page and try again.');
      }
      jurisdictionInput.add(new Option(
        jurisdictions.length ? 'Select one jurisdiction' : 'No jurisdiction assigned',
        ''
      ));
      jurisdictions.forEach(function (jurisdiction) {
        jurisdictionInput.add(new Option(jurisdiction.name, String(jurisdiction.id)));
      });
      jurisdictionInput.disabled = jurisdictions.length === 0;
      jurisdictionInput.required = jurisdictions.length > 0;
      if (jurisdictions.length === 1) {
        jurisdictionInput.value = String(jurisdictions[0].id);
      }
      committeeContext.textContent = [
        option.textContent,
        option.dataset.jurisdiction ? 'Available jurisdictions: ' + option.dataset.jurisdiction : 'No jurisdiction assigned',
        option.dataset.chair ? 'Chairperson: ' + option.dataset.chair : 'Chairperson information unavailable',
        option.dataset.members ? 'Members: ' + option.dataset.members : 'No active committee-member records',
      ].join(' · ');
    }
    function updateReportTypeFields() {
      const isCommitteeReport = reportTypeSelect.value === 'Committee';
      legislativeFields.style.display = isCommitteeReport ? '' : 'none';
      committeeSelect.required = isCommitteeReport;
    }
    committeeSelect.addEventListener('change', updateCommitteeContext);
    modalEl.addEventListener('shown.bs.modal', updateCommitteeContext);
    reportTypeSelect.addEventListener('change', updateReportTypeFields);
    updateCommitteeContext();
    updateReportTypeFields();

    const newBtn = document.getElementById('btnNewDraft');
    const aiBtn = document.getElementById('btnGenerateAiDraft');
    if (newBtn) newBtn.addEventListener('click', function () {
      isAiMode = false;
      document.getElementById('newDraftModalTitle').textContent = 'New Committee Report';
      dateRangeWrap.style.display = 'none';
      updateCommitteeContext();
      if (window.bootstrap) new bootstrap.Modal(modalEl).show();
    });
    if (aiBtn) aiBtn.addEventListener('click', function () {
      isAiMode = true;
      document.getElementById('newDraftModalTitle').textContent = 'Generate AI Draft';
      dateRangeWrap.style.display = 'flex';
      if (window.bootstrap) new bootstrap.Modal(modalEl).show();
    });

    const submitBtn = document.getElementById('btnSubmitNewDraft');
    if (submitBtn) submitBtn.addEventListener('click', function () {
      const title = document.getElementById('ndTitle').value.trim();
      if (!title) { appToast('error', 'Report title is required.'); return; }

      const committeeId = document.getElementById('ndCommittee').value;
      const reportType = document.getElementById('ndType').value;
      if (isAiMode && reportType === 'Committee') {
        appToast('error', 'AI generation is disabled for formal Committee Reports. Create a draft and use Generate from CMAS Data instead.');
        return;
      }
      if (reportType === 'Committee' && committeeId === '0') {
        appToast('error', 'Select a committee for the Committee Report.');
        return;
      }
      if (jurisdictionInput.required && !jurisdictionInput.value) {
        appToast('error', 'Select one jurisdiction for this committee.');
        jurisdictionInput.focus();
        return;
      }

      const payload = { csrf_token: csrfToken, committee_id: committeeId, report_title: title };
      payload.jurisdiction_id = jurisdictionInput.value;
      payload.proposed_ordinance_title = document.getElementById('ndMeasureTitle').value.trim();
      payload.reference_measure_no = document.getElementById('ndMeasureNo').value.trim();
      payload.date_referred = document.getElementById('ndDateReferred').value;
      payload.referred_by = document.getElementById('ndReferredBy').value.trim();
      payload.subject_title = document.getElementById('ndSubject').value.trim() || title;
      let url;
      if (isAiMode) {
        url = baseUrl + '/ajax_draft_generate_ai.php';
        payload.type = reportType.toLowerCase();
        payload.date_from = document.getElementById('ndDateFrom').value;
        payload.date_to = document.getElementById('ndDateTo').value;
      } else {
        url = baseUrl + '/ajax_draft_save.php';
        payload.id = '0';
        payload.report_type = reportType;
      }

      submitBtn.disabled = true;
      const originalLabel = submitBtn.textContent;
      submitBtn.textContent = 'Creating…';
      appPost(url, payload).then(function (data) {
        if (data && data.success) {
          window.location.href = baseUrl + '/draft_edit.php?id=' + data.draft_id;
        } else {
          appToast('error', (data && data.message) || 'Could not create draft.');
        }
      }).catch(function () {
        appToast('error', 'The draft could not be created because the request failed. Check your connection and try again.');
      }).finally(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = originalLabel;
      });
    });
  });
})();
