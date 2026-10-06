<?php
/**
 * Committee-first jurisdiction list, shared by initial and AJAX renders.
 */

$pdo = db();
$search = clean($_GET['search'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');
$committeeFilter = clean($_GET['committees'] ?? '');
$sortColumns = ['committee_name', 'jurisdiction_name', 'status'];
$sortBy = in_array($_GET['sort'] ?? '', $sortColumns, true) ? $_GET['sort'] : 'committee_name';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$canEditJurisdictions = canManage();
$canDeleteJurisdictions = isAdmin();

function cmasJurisdictionCommitteeIcon(string $committeeName): string
{
    $name = strtolower($committeeName);
    $iconMap = [
        '/\b(law|laws|legal|ordinance)\b/' => 'bi-bank',
        '/\b(health|medical|hospital)\b/' => 'bi-heart-pulse',
        '/\b(finance|budget|tax|appropriation)\b/' => 'bi-cash-coin',
        '/\b(education|school|library)\b/' => 'bi-book',
        '/\b(safety|police|peace|security|disaster)\b/' => 'bi-shield-check',
        '/\b(environment|ecology|natural resources|agriculture)\b/' => 'bi-tree',
        '/\b(works|infrastructure|transport|road|traffic)\b/' => 'bi-buildings',
        '/\b(social welfare|social services|family|women|children)\b/' => 'bi-people',
    ];
    foreach ($iconMap as $pattern => $icon) {
        if (preg_match($pattern, $name)) {
            return $icon;
        }
    }
    return 'bi-people';
}

$conditions = ["c.status = 'Active'"];
$params = [];
if ($search !== '') {
    $conditions[] = "(c.committee_name LIKE :committee_search OR EXISTS (
        SELECT 1 FROM jurisdictions js
        WHERE js.category = c.committee_name
          AND (js.jurisdiction_name LIKE :jurisdiction_search
            OR js.description LIKE :description_search
            OR js.scope_definition LIKE :scope_search
            OR js.covered_areas LIKE :areas_search)
    ))";
    $term = '%' . $search . '%';
    $params[':committee_search'] = $term;
    $params[':jurisdiction_search'] = $term;
    $params[':description_search'] = $term;
    $params[':scope_search'] = $term;
    $params[':areas_search'] = $term;
}
if ($committeeFilter === 'some') {
    $conditions[] = 'EXISTS (SELECT 1 FROM jurisdictions je WHERE je.category = c.committee_name)';
} elseif ($committeeFilter === 'none') {
    $conditions[] = 'NOT EXISTS (SELECT 1 FROM jurisdictions je WHERE je.category = c.committee_name)';
}
if (in_array($statusFilter, ['Active', 'Inactive'], true)) {
    $conditions[] = 'EXISTS (
        SELECT 1 FROM jurisdictions js_status
        WHERE js_status.category = c.committee_name AND js_status.status = :jurisdiction_status
    )';
    $params[':jurisdiction_status'] = $statusFilter;
}
$whereSql = implode(' AND ', $conditions);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM committees c WHERE $whereSql");
$countStmt->execute($params);
$pageInfo = paginate((int)$countStmt->fetchColumn());

$sortExpression = match ($sortBy) {
    'jurisdiction_name' => '(SELECT MIN(js.jurisdiction_name) FROM jurisdictions js WHERE js.category = c.committee_name)',
    'status' => 'c.status',
    default => 'c.committee_name',
};
$committeeStmt = $pdo->prepare(
    "SELECT c.committee_id, c.committee_name, c.status
     FROM committees c
     WHERE $whereSql
     ORDER BY $sortExpression $sortDir, c.committee_name ASC
     LIMIT {$pageInfo['perPage']} OFFSET {$pageInfo['offset']}"
);
$committeeStmt->execute($params);
$committees = $committeeStmt->fetchAll();

$jurisdictionsByCommittee = [];
if ($committees) {
    $committeeNames = array_column($committees, 'committee_name');
    $placeholders = [];
    $jurisdictionParams = [];
    foreach ($committeeNames as $i => $name) {
        $placeholder = ':committee_' . $i;
        $placeholders[] = $placeholder;
        $jurisdictionParams[$placeholder] = $name;
    }
    $childWhere = 'category IN (' . implode(', ', $placeholders) . ')';
    if (in_array($statusFilter, ['Active', 'Inactive'], true)) {
        $childWhere .= ' AND status = :child_status';
        $jurisdictionParams[':child_status'] = $statusFilter;
    }
    $childOrder = $sortBy === 'status' ? "status $sortDir, jurisdiction_name ASC" : "jurisdiction_name $sortDir";
    $jurisdictionStmt = $pdo->prepare(
        "SELECT jurisdiction_id, jurisdiction_name, category, description, scope_definition, status
         FROM jurisdictions
         WHERE $childWhere
         ORDER BY $childOrder"
    );
    $jurisdictionStmt->execute($jurisdictionParams);
    foreach ($jurisdictionStmt->fetchAll() as $jurisdiction) {
        $jurisdictionsByCommittee[$jurisdiction['category']][] = $jurisdiction;
    }
}
?>
<?php if (empty($committees)): ?>
  <div class="jurisdiction-empty-state text-center text-muted py-5">No committees match these filters.</div>
<?php else: ?>
  <div class="committee-jurisdiction-list-heading">
    <h6 class="mb-1">Committees</h6>
    <p class="small text-muted mb-0">Jurisdictions are grouped under their respective committees.</p>
  </div>
  <div class="committee-jurisdiction-list">
    <?php foreach ($committees as $committee): ?>
      <?php $committeeJurisdictions = $jurisdictionsByCommittee[$committee['committee_name']] ?? []; ?>
      <?php $jurisdictionPanelId = 'committee-jurisdictions-' . (int)$committee['committee_id']; ?>
      <section class="committee-jurisdiction-card" aria-label="<?= e($committee['committee_name']) ?>">
        <header class="committee-jurisdiction-header">
          <div class="committee-jurisdiction-identity">
            <span class="committee-jurisdiction-icon" aria-hidden="true">
              <i class="bi <?= e(cmasJurisdictionCommitteeIcon($committee['committee_name'])) ?>"></i>
            </span>
            <span class="committee-jurisdiction-title">
              <h6 class="mb-0"><?= e($committee['committee_name']) ?></h6>
              <small>
                <?= count($committeeJurisdictions) ?>
                <?= count($committeeJurisdictions) === 1 ? 'jurisdiction' : 'jurisdictions' ?>
              </small>
            </span>
          </div>
          <div class="committee-jurisdiction-header-actions">
          <?php if ($canEditJurisdictions): ?>
            <button type="button" class="jurisdiction-action-button btn-add-jurisdiction"
                    data-committee-name="<?= e($committee['committee_name']) ?>"
                    title="Add jurisdiction to <?= e($committee['committee_name']) ?>"
                    aria-label="Add jurisdiction to <?= e($committee['committee_name']) ?>">
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
            </button>
          <?php endif; ?>
            <button type="button" class="jurisdiction-action-button btn-toggle-committee-jurisdictions"
                    aria-expanded="false" aria-controls="<?= e($jurisdictionPanelId) ?>"
                    title="Show jurisdictions for <?= e($committee['committee_name']) ?>"
                    aria-label="Show jurisdictions for <?= e($committee['committee_name']) ?>">
              <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
          </div>
        </header>
        <div class="committee-jurisdiction-panel" id="<?= e($jurisdictionPanelId) ?>" hidden>
          <?php if (!$committeeJurisdictions): ?>
            <p class="committee-jurisdiction-empty mb-0">No jurisdictions listed for this committee yet.</p>
          <?php else: ?>
            <ul class="committee-jurisdiction-rows">
              <?php foreach ($committeeJurisdictions as $jurisdiction): ?>
                <li class="committee-jurisdiction-row">
                  <button type="button" class="committee-jurisdiction-name btn-view-jurisdiction"
                          data-id="<?= (int)$jurisdiction['jurisdiction_id'] ?>"
                          aria-label="<?= e('View jurisdiction ' . $jurisdiction['jurisdiction_name']) ?>">
                    <span><?= e($jurisdiction['jurisdiction_name']) ?></span>
                    <small><?= e(truncate($jurisdiction['description'] ?: ($jurisdiction['scope_definition'] ?? ''), 120) ?: 'No description provided.') ?></small>
                  </button>
                  <span class="jurisdiction-card-status status-<?= e(strtolower($jurisdiction['status'])) ?>"><?= e($jurisdiction['status']) ?></span>
                  <div class="jurisdiction-actions">
                    <button type="button" class="jurisdiction-action-button btn-view-jurisdiction" data-id="<?= (int)$jurisdiction['jurisdiction_id'] ?>" title="View jurisdiction details" aria-label="View jurisdiction details">
                      <i class="bi bi-eye"></i>
                    </button>
                    <?php if ($canEditJurisdictions): ?>
                      <button type="button" class="jurisdiction-action-button btn-edit-jurisdiction" data-id="<?= (int)$jurisdiction['jurisdiction_id'] ?>" title="Edit jurisdiction" aria-label="Edit jurisdiction">
                        <i class="bi bi-pencil-square"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($canDeleteJurisdictions): ?>
                      <button type="button" class="jurisdiction-action-button"
                              data-confirm-delete="jurisdiction &quot;<?= e($jurisdiction['jurisdiction_name']) ?>&quot;"
                              data-delete-url="<?= e(APP_URL) ?>/modules/jurisdictions/ajax_delete.php?id=<?= (int)$jurisdiction['jurisdiction_id'] ?>"
                              title="Delete jurisdiction" aria-label="Delete jurisdiction">
                        <i class="bi bi-trash"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<div class="card-footer bg-white py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Showing <?= count($committees) ?> of <?= $pageInfo['total'] ?> committee(s)</small>
    <?= renderPagination($pageInfo, 'index.php') ?>
  </div>
</div>
