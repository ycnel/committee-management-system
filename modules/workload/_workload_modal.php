<!-- Committee workload modal: loads committee.php?embed=1 markup, then binds workload.js via initWorkloadPage() -->
<div class="modal fade" id="workloadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:1100px;">
    <div class="modal-content">
      <div class="modal-header py-2 border-0">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" id="wlmBody">
        <div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm"></span> Loading workload…</div>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('workloadModal');
  const bodyEl = document.getElementById('wlmBody');
  if (!modalEl || !window.bootstrap) return;
  const modal = new bootstrap.Modal(modalEl);

  document.addEventListener('click', function (e) {
    const card = e.target.closest('[data-committee-workload]');
    if (!card) return;
    e.preventDefault();
    bodyEl.innerHTML = '<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm"></span> Loading workload…</div>';
    modal.show();

    fetch('committee.php?committee_id=' + encodeURIComponent(card.getAttribute('data-committee-workload')) + '&embed=1', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store'
    })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(function (html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        bodyEl.innerHTML = '';
        Array.from(doc.body.childNodes).forEach(function (n) { bodyEl.appendChild(document.importNode(n, true)); });
        if (window.initWorkloadPage) window.initWorkloadPage();
      })
      .catch(function () {
        bodyEl.innerHTML = '<div class="text-center text-muted py-5">Unable to load this committee workload right now.</div>';
      });
  });
  modalEl.addEventListener('hidden.bs.modal', function () { bodyEl.innerHTML = ''; });
});
</script>
