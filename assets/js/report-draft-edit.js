/**
 * assets/js/report-draft-edit.js
 * ------------------------------------------------------------------
 * Legislative report editing, AI assistance, review, and finalization.
 * ------------------------------------------------------------------
 */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = window.APP_URL + '/modules/committee_reports';
    const csrfToken = window.APP_CSRF_TOKEN || '';
    const idEl = document.getElementById('dfId');
    if (!idEl) return;
    const draftId = idEl.value;
    const printModalEl = document.getElementById('reportPrintModal');
    const printFrame = document.getElementById('reportPrintFrame');
    const printModal = printModalEl && window.bootstrap ? new bootstrap.Modal(printModalEl) : null;
    const openPrintPreviewBtn = document.getElementById('btnOpenReportPrintPreview');
    const printButton = document.getElementById('btnPrintReportPreview');

    if (openPrintPreviewBtn && printFrame && printModal) openPrintPreviewBtn.addEventListener('click', function () {
      printFrame.src = baseUrl + '/print.php?draft_id=' + encodeURIComponent(draftId);
      printModal.show();
    });
    if (printButton) printButton.addEventListener('click', function () {
      if (printFrame && printFrame.contentWindow) printFrame.contentWindow.print();
    });
    if (printModalEl) printModalEl.addEventListener('hidden.bs.modal', function () {
      if (printFrame) printFrame.src = 'about:blank';
    });

    function post(url, data) {
      return appPost(url, Object.assign({ csrf_token: csrfToken }, data));
    }

    function handleResult(data, reload) {
      if (data && data.success) {
        appToast('success', data.message || 'Saved.');
        if (reload !== false) setTimeout(function () { window.location.reload(); }, 600);
      } else {
        appToast('error', (data && data.message) || 'Something went wrong.');
      }
    }

    function collectFields() {
      function fieldValue(id) {
        const field = document.getElementById(id);
        return field ? field.value : '';
      }
      return {
        id: draftId,
        committee_id: document.getElementById('dfCommitteeId').value,
        jurisdiction_id: document.getElementById('dfJurisdiction').value,
        report_type: document.getElementById('dfReportType').value,
        report_title: document.getElementById('dfTitle').value,
        executive_summary: fieldValue('df_executive_summary'),
        analysis: fieldValue('df_analysis'),
        observations: fieldValue('df_observations'),
        recommendations: fieldValue('df_recommendations'),
        conclusion: fieldValue('df_conclusion'),
        proposed_ordinance_title: document.getElementById('dfMeasureTitle').value,
        reference_measure_no: document.getElementById('dfMeasureNo').value,
        date_referred: document.getElementById('dfDateReferred').value,
        referred_by: document.getElementById('dfReferredBy').value,
        subject_title: document.getElementById('dfSubject').value,
        matter_referred: fieldValue('df_matter_referred'),
        committee_proceedings: fieldValue('df_committee_proceedings'),
        findings: fieldValue('df_findings'),
        discussion_analysis: fieldValue('df_discussion_analysis'),
        recommendation_type: fieldValue('df_recommendation_type'),
        legislative_history: fieldValue('df_legislative_history'),
        committee_amendments: fieldValue('df_committee_amendments'),
        individual_views: fieldValue('df_individual_views'),
        committee_action: fieldValue('df_committee_action'),
        committee_action_date: fieldValue('df_committee_action_date'),
        signature_details: fieldValue('df_signature_details'),
        appendices: fieldValue('df_appendices'),
        legislative_matter_ref: fieldValue('dfMatterRef'),
        meeting_ref: fieldValue('dfMeetingRef'),
        hearing_ref: fieldValue('dfHearingRef'),
        research_ref: fieldValue('dfResearchRef'),
        document_references: fieldValue('dfDocumentRefs'),
      };
    }

    const saveBtn = document.getElementById('btnSaveDraft');
    if (saveBtn) saveBtn.addEventListener('click', function () {
      post(baseUrl + '/ajax_draft_save.php', collectFields()).then(function (d) { handleResult(d); });
    });

    const reviewBtn = document.getElementById('btnMarkReview');
    if (reviewBtn) reviewBtn.addEventListener('click', function () {
      post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'review' }).then(function (d) { handleResult(d); });
    });

    const submitBtn = document.getElementById('btnSubmitForReview');
    if (submitBtn) submitBtn.addEventListener('click', function () {
      post(baseUrl + '/ajax_draft_save.php', collectFields()).then(function (saved) {
        if (!saved || !saved.success) {
          appToast('error', (saved && saved.message) || 'Save the report before submitting.');
          return;
        }
        post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'submit' })
          .then(function (d) { handleResult(d); });
      });
    });

    function requestAIDraft(section) {
      post(baseUrl + '/ajax_draft_generate_ai.php', {
        draft_id: draftId,
        section: section || 'all',
        source_content: JSON.stringify(collectFields())
      }).then(function (data) {
        if (!data || !data.success) {
          appToast('error', (data && data.message) || 'AI assistance is unavailable.');
          return;
        }
        const apply = function () {
          Object.keys(data.sections || {}).forEach(function (key) {
            const field = document.getElementById('df_' + key);
            if (field && data.sections[key]) field.value = data.sections[key];
          });
          if (data.ai_sources) {
            const sourcePanel = document.getElementById('aiSourcesPanel');
            const sourceText = document.getElementById('aiSourcesText');
            if (sourceText) sourceText.textContent = data.ai_sources;
            if (sourcePanel) sourcePanel.open = true;
            appToast('success', 'AI suggestions loaded. Review and edit the draft before saving.');
          }
        };
        if (window.Swal) {
          Swal.fire({
            title: 'AI-generated draft suggestion',
            text: 'The generated text is based only on saved report information. It is not verified, must be reviewed, and will not decide the Committee’s action.',
            icon: 'info', showCancelButton: true, confirmButtonText: 'Use suggestion'
          }).then(function (result) { if (result.isConfirmed) apply(); });
        } else if (window.confirm('Use this AI-generated suggestion? It must be reviewed and edited by an authorized human.')) {
          apply();
        }
      });
    }

    const generateBtn = document.getElementById('btnGenerateLegislativeReport');
    if (generateBtn) generateBtn.addEventListener('click', function () {
      requestAIDraft('all');
    });
    document.querySelectorAll('[data-ai-section]').forEach(function (button) {
      button.addEventListener('click', function () {
        requestAIDraft(button.getAttribute('data-ai-section'));
      });
    });

    const cmasGenerateBtn = document.getElementById('btnGenerateFromCmas');
    const cmasPreviewModal = document.getElementById('cmasPreviewModal');
    const cmasPreviewSections = document.getElementById('cmasPreviewSections');
    const cmasPreviewSources = document.getElementById('cmasPreviewSources');
    const cmasPreviewNotices = document.getElementById('cmasPreviewNotices');
    let pendingCmasSections = null;
    const sectionLabels = {
      matter_referred: 'I. Matter Referred',
      committee_proceedings: 'II. Committee Proceedings',
      findings: 'III. Findings',
      discussion_analysis: 'IV. Discussion / Analysis',
      recommendations: 'VI. Recommendation / Proposed Committee Action',
      conclusion: 'V. Conclusion',
      legislative_history: 'VII. Legislative History',
      committee_amendments: 'VIII. Committee Amendments',
      individual_views: 'IX. Individual / Minority / Supplemental Views',
      signature_details: 'Signatures and Concurrence',
      appendices: 'Appendices'
    };
    if (cmasGenerateBtn) cmasGenerateBtn.addEventListener('click', function () {
      cmasGenerateBtn.disabled = true;
      post(baseUrl + '/ajax_draft_save.php', collectFields()).then(function (saved) {
        if (!saved || !saved.success) {
          appToast('error', (saved && saved.message) || 'Save the report before generating a CMAS preview.');
          return;
        }
        return post(baseUrl + '/ajax_draft_generate_cmas.php', { id: draftId }).then(function (data) {
          if (!data || !data.success) {
            appToast('error', (data && data.message) || 'Could not generate a CMAS preview.');
            return;
          }
          pendingCmasSections = data.sections || {};
          cmasPreviewSections.replaceChildren();
          Object.keys(sectionLabels).forEach(function (key) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-3';
            const label = document.createElement('h6');
            label.textContent = sectionLabels[key];
            const content = document.createElement('pre');
            content.className = 'small bg-light border rounded p-2 text-wrap';
            content.textContent = pendingCmasSections[key] || '';
            wrapper.append(label, content);
            cmasPreviewSections.appendChild(wrapper);
          });
          cmasPreviewSources.textContent = (data.sources || []).join('; ');
          cmasPreviewNotices.replaceChildren();
          (data.notices || []).forEach(function (notice) {
            const item = document.createElement('div');
            item.textContent = notice;
            cmasPreviewNotices.appendChild(item);
          });
          if (window.bootstrap) new bootstrap.Modal(cmasPreviewModal).show();
        });
      }).catch(function () {
        appToast('error', 'The CMAS preview request failed. No generated report content was applied.');
      }).finally(function () {
        cmasGenerateBtn.disabled = false;
      });
    });

    const applyCmasPreviewBtn = document.getElementById('btnApplyCmasPreview');
    if (applyCmasPreviewBtn) applyCmasPreviewBtn.addEventListener('click', function () {
      if (!pendingCmasSections) return;
      Object.keys(pendingCmasSections).forEach(function (key) {
        const field = document.getElementById('df_' + key);
        if (field && !field.value.trim()) field.value = pendingCmasSections[key];
      });
      pendingCmasSections = null;
      if (window.bootstrap) bootstrap.Modal.getInstance(cmasPreviewModal).hide();
      appToast('success', 'Preview applied to empty sections. Save Changes to store it.');
    });

    const returnBtn = document.getElementById('btnReturnReport');
    if (returnBtn) returnBtn.addEventListener('click', function () {
      const askReason = function (reason) {
        if (!reason || !reason.trim()) {
          appToast('error', 'A reason is required to return the report.');
          return;
        }
        post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'return', comment: reason })
          .then(function (d) { handleResult(d); });
      };
      if (window.Swal) {
        Swal.fire({
          title: 'Return for revision',
          input: 'textarea',
          inputLabel: 'Explain what needs revision',
          inputValidator: function (value) { return value && value.trim() ? undefined : 'A reason is required.'; },
          showCancelButton: true,
          confirmButtonText: 'Return report'
        }).then(function (result) { if (result.isConfirmed) askReason(result.value); });
      } else {
        askReason(window.prompt('Reason for returning this report:') || '');
      }
    });

    const approveBtn = document.getElementById('btnApproveReport');
    if (approveBtn) approveBtn.addEventListener('click', function () {
      const confirmApproval = function () {
        post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'approve' })
          .then(function (d) { handleResult(d); });
      };
      if (window.Swal) {
        Swal.fire({
          title: 'Approve this Committee Report?',
          text: 'This records your human approval. Finalization remains a separate action.',
          icon: 'question', showCancelButton: true, confirmButtonText: 'Approve'
        }).then(function (result) { if (result.isConfirmed) confirmApproval(); });
      } else if (window.confirm('Approve this Committee Report?')) confirmApproval();
    });

    const finalizeBtn = document.getElementById('btnFinalize');
    if (finalizeBtn) finalizeBtn.addEventListener('click', function () {
      const go = function () {
        post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'finalize' }).then(function (d) { handleResult(d); });
      };
      if (window.Swal) {
        Swal.fire({
          title: 'Finalize this report?',
          text: 'Finalization requires a confirmed recommendation, the actual Committee action and date, and a separate signed status/date for each active Committee member.',
          icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, finalize'
        }).then(function (r) { if (r.isConfirmed) go(); });
      } else if (window.confirm('Finalization requires a confirmed recommendation, the actual Committee action and date, and a separate signed status/date for each active Committee member. Continue?')) {
        go();
      }
    });

    const archiveBtn = document.getElementById('btnArchive');
    if (archiveBtn) archiveBtn.addEventListener('click', function () {
      post(baseUrl + '/ajax_draft_transition.php', { id: draftId, action: 'archive' }).then(function (d) { handleResult(d); });
    });
  });
})();
