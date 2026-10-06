<?php if (isCommitteeMember()): ?>
<div class="modal fade" id="taskCompletionRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="taskCompletionRequestForm" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="workload_id" id="completionWorkloadId">
        <div class="modal-header">
          <div>
            <h5 class="modal-title">Submit Completion Request</h5>
            <p class="small text-muted mb-0">A Chairperson or Administrator must review your proof before the task is marked complete.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6"><span class="small text-muted d-block">Task Name</span><strong id="completionTaskTitle"></strong></div>
            <div class="col-md-6"><span class="small text-muted d-block">Committee</span><strong id="completionCommitteeName"></strong></div>
            <div class="col-md-6"><span class="small text-muted d-block">Jurisdiction</span><strong id="completionJurisdictionName"></strong></div>
            <div class="col-md-6"><span class="small text-muted d-block">Assigned Date</span><strong id="completionAssignedDate"></strong></div>
          </div>
          <div class="alert alert-light border d-none" id="completionProofGuidanceWrap">
            <strong class="small d-block mb-1">Proof Requirement</strong>
            <span id="completionProofGuidance"></span>
          </div>
          <div class="mb-3">
            <label class="form-label" for="completionNotes">Completion Notes / Remarks <span class="text-danger">*</span></label>
            <textarea class="form-control" id="completionNotes" name="completion_notes" rows="4" maxlength="10000" required
                      placeholder="Explain the work you completed and what the attached evidence demonstrates."></textarea>
          </div>
          <div>
            <label class="form-label" for="completionProofFile">Proof / Supporting File <span class="text-danger">*</span></label>
            <input class="form-control" type="file" id="completionProofFile" name="proof_file"
                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" required>
            <div class="form-text">JPG, JPEG, PNG, PDF, DOC, DOCX, XLS, or XLSX; maximum 10 MB.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="submitCompletionRequest">
            <i class="bi bi-send-check"></i> Submit for Approval
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="taskRemovalRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="taskRemovalRequestForm">
        <?= csrfField() ?>
        <input type="hidden" name="workload_id" id="removalWorkloadId">
        <div class="modal-header">
          <div>
            <h5 class="modal-title">Request Task Removal</h5>
            <p class="small text-muted mb-0">The assignment remains active unless a Chairperson or Administrator approves removal.</p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6"><span class="small text-muted d-block">Task Name</span><strong id="removalTaskTitle"></strong></div>
            <div class="col-md-6"><span class="small text-muted d-block">Committee</span><strong id="removalCommitteeName"></strong></div>
            <div class="col-12"><span class="small text-muted d-block">Jurisdiction</span><strong id="removalJurisdictionName"></strong></div>
          </div>
          <div>
            <label class="form-label" for="taskRemovalReason">Reason for removal <span class="text-danger">*</span></label>
            <textarea class="form-control" id="taskRemovalReason" name="reason" rows="4" maxlength="10000" required
                      placeholder="Explain why this assignment should be removed."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm" id="submitTaskRemovalRequest">
            <i class="bi bi-send"></i> Submit Removal Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
