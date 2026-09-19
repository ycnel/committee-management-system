<?php
/**
 * modules/workload/ai_settings.php
 * ------------------------------------------------------------------
 * Admin-only controls for the Smart AI Workload Distribution engine:
 *   1. Rule-based factor weights (unchanged from before).
 *   2. Google Gemini AI settings — enable/disable, model,
 *      model, timeout, Test Connection, live status.
 *   3. Recent recommendations log, now showing both the rule-based
 *      pick and the AI's pick side-by-side with their agreement.
 * ------------------------------------------------------------------
 */


require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/GeminiAI.php';
requireRole([ROLE_ADMIN]);

$pageTitle  = 'Smart AI Workload Settings';
$activeMenu = 'workload';
$pdo = db();

$factors = [];

$gemini = new GeminiAI($pdo);
$geminiConfig = $gemini->getConfig();

$recent = $pdo->query(
    "SELECT ar.*, c.committee_name, u.full_name AS generated_by_name,
            rm.committee_member_id AS rec_cm_id, ru.full_name AS recommended_name,
            fm.committee_member_id AS final_cm_id, fu.full_name AS final_name,
            aim.committee_member_id AS ai_cm_id, aiu.full_name AS ai_recommended_name
     FROM ai_recommendations ar
     INNER JOIN committees c ON c.committee_id = ar.committee_id
     LEFT JOIN users u ON u.id = ar.generated_by
     LEFT JOIN committee_members rm ON rm.committee_member_id = ar.recommended_member_id
     LEFT JOIN users ru ON ru.id = rm.user_id
     LEFT JOIN committee_members fm ON fm.committee_member_id = ar.final_member_id
     LEFT JOIN users fu ON fu.id = fm.user_id
     LEFT JOIN committee_members aim ON aim.committee_member_id = ar.ai_recommended_member_id
     LEFT JOIN users aiu ON aiu.id = aim.user_id
     ORDER BY ar.generated_at DESC LIMIT 15"
)->fetchAll();

include __DIR__ . '/../../layouts/header.php';
?>
<div class="app-wrapper">
  <?php include __DIR__ . '/../../layouts/sidebar.php'; ?>

  <div class="main-content">
  <?php include __DIR__ . '/../../layouts/content-topbar.php'; ?>
  <div class="breadcrumb-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
    
    <div>
      <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb mb-0 small">
          <li class="breadcrumb-item"><a href="index.php">Workload Distribution</a></li>
          <li class="breadcrumb-item active">Smart AI Settings</li>
        </ol>
      </nav>
      <h5 class="mb-0"><i class="bi bi-stars text-primary"></i> Smart AI Workload Distribution — Settings</h5> 

      <small class="text-muted">Adjust how the recommendation engine weighs each factor, and configure the optional local AI decision-support layer.</small>
    </div>
  </div>

  <!-- Scoring Factors 
  <div class="card mb-3">
    <div class="card-header">Scoring Factors <span class="text-muted small fw-normal">(Rule-Based Engine — always on)</span></div>
    <div class="card-body">
      <form id="weightsForm">
        <?= csrfField() ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:40px;">On</th>
                <th>Factor</th>
                <th>Description</th>
                <th style="width:220px;">Weight (0–100)</th>
                <th style="width:140px;">Direction</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($factors as $f): ?>
                <tr>
                  <td>
                    <div class="form-check form-switch">
                      <input class="form-check-input factor-enabled" type="checkbox" data-key="<?= e($f['factor_key']) ?>" <?= $f['is_enabled'] ? 'checked' : '' ?>>
                    </div>
                  </td>
                  <td class="fw-semibold"><?= e($f['factor_label']) ?></td>
                  <td class="small text-muted"><?= e($f['description']) ?></td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <input type="range" class="form-range factor-weight" min="0" max="100" value="<?= (int)$f['weight'] ?>" data-key="<?= e($f['factor_key']) ?>">
                      <span class="badge bg-light text-dark border weight-value" style="min-width:38px;"><?= (int)$f['weight'] ?></span>
                    </div>
                  </td>
                  <td><span class="badge bg-<?= $f['direction'] === 'lower_is_better' ? 'info' : 'success' ?>"><?= $f['direction'] === 'lower_is_better' ? 'Lower is better' : 'Higher is better' ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Weights</button>
        <span class="text-muted small ms-2">Changes apply to every recommendation generated after saving — past recommendations in the log below are unaffected.</span>
      </form>
    </div>
  </div>
  -->

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-cloud"></i> Google Gemini AI — Contextual Decision Support</span>
      <span id="aiStatusBadge" class="badge bg-secondary">Checking…</span>
    </div>
    <div class="card-body">
      <p class="small text-muted">
        This uses the Google Gemini API through a server-side API key. It adds contextual reasoning alongside the rule-based score above;
        it never replaces it, and administrators always make the final call.
      </p>
      <form id="aiSettingsForm">
        <?= csrfField() ?>
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Enable Gemini AI</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="gemini_enabled" name="gemini_enabled" value="1" <?= $geminiConfig['enabled'] ? 'checked' : '' ?>>
              <label class="form-check-label small" for="gemini_enabled">Enabled</label>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Gemini Model</label>
            <input type="text" class="form-control form-control-sm" id="gemini_model" name="gemini_model" value="<?= e($geminiConfig['model']) ?>" placeholder="gemini-3.6-flash">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Timeout (s)</label>
            <input type="number" class="form-control form-control-sm" id="gemini_timeout" name="gemini_timeout" value="<?= (int)$geminiConfig['timeout'] ?>" min="5" max="120">
          </div>
        </div>
        <div class="mt-3 d-flex align-items-center gap-2">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Save AI Settings</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTestAiConnection"><i class="bi bi-wifi"></i> Test AI Connection</button>
          <span id="aiTestResult" class="small ms-1"></span>
        </div>
      </form>

      <div class="row g-3 mt-1">
        <div class="col-md-4">
          <div class="border rounded p-2 small">
            <div class="text-muted">AI Status</div>
            <div class="fw-semibold" id="aiStatusDetail">Not checked yet</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 small">
            <div class="text-muted">Last Test Response Time</div>
            <div class="fw-semibold" id="aiResponseTime">—</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 small">
            <div class="text-muted">Last Successful AI Analysis</div>
            <div class="fw-semibold">
              <?php
                $lastSuccess = $pdo->query("SELECT generated_at FROM ai_recommendations WHERE ai_available = 1 ORDER BY generated_at DESC LIMIT 1")->fetchColumn();
                echo $lastSuccess ? e(formatDateTime($lastSuccess)) : '<span class="text-muted">None yet</span>';
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Recommendations Log 
  <div class="card">
    <div class="card-header">Recent Recommendations <span class="text-muted small fw-normal">(Rule-Based vs. AI)</span></div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Committee</th>
            <th>Rule-Based Pick</th>
            <th>Score</th>
            <th>AI Pick</th>
            <th>AI Confidence</th>
            <th>Risk</th>
            <th>Agreement</th>
            <th>Final Assignment</th>
            <th>Generated</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No recommendations generated yet.</td></tr>
          <?php else: foreach ($recent as $r): ?>
            <tr>
              <td><?= e($r['committee_name']) ?></td>
              <td><?= $r['recommended_name'] ? e($r['recommended_name']) : '<span class="text-muted">—</span>' ?></td>
              <td><?= $r['recommended_score'] !== null ? (float)$r['recommended_score'] . '%' : '—' ?></td>
              <td>
                <?php if (!isset($r['ai_available']) || $r['ai_available'] === null): ?>
                  <span class="text-muted small">N/A</span>
                <?php elseif ((int)$r['ai_available'] === 1): ?>
                  <?= $r['ai_recommended_name'] ? e($r['ai_recommended_name']) : '<span class="text-muted">—</span>' ?>
                <?php else: ?>
                  <span class="badge bg-light text-muted border">Unavailable</span>
                <?php endif; ?>
              </td>
              <td><?= $r['ai_confidence'] ? '<span class="badge bg-' . ($r['ai_confidence'] === 'High' ? 'success' : ($r['ai_confidence'] === 'Medium' ? 'warning' : 'secondary')) . '">' . e($r['ai_confidence']) . '</span>' : '<span class="text-muted small">—</span>' ?></td>
              <td><?= $r['ai_risk_assessment'] ? '<span class="badge bg-' . ($r['ai_risk_assessment'] === 'Low' ? 'success' : ($r['ai_risk_assessment'] === 'Medium' ? 'warning' : 'danger')) . '">' . e($r['ai_risk_assessment']) . '</span>' : '<span class="text-muted small">—</span>' ?></td>
              <td>
                <?php if (($r['agreement_status'] ?? 'N/A') === 'Yes'): ?>
                  <span class="badge bg-success">Yes</span>
                <?php elseif (($r['agreement_status'] ?? 'N/A') === 'No'): ?>
                  <span class="badge bg-warning text-dark">No</span>
                <?php else: ?>
                  <span class="text-muted small">N/A</span>
                <?php endif; ?>
              </td>
              <td><?= $r['final_name'] ? e($r['final_name']) : '<span class="text-muted">Not yet assigned</span>' ?></td>
              <td class="small text-muted"><?= formatDateTime($r['generated_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  -->

<?php
$extraJs = [APP_URL . '/assets/js/ai-settings.js', APP_URL . '/assets/js/ai-gemini-settings.js'];
include __DIR__ . '/../../layouts/footer.php';
?>
