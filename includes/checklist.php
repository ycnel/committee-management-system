<?php
/**
 * includes/checklist.php
 * ------------------------------------------------------------------
 * Procedural Checklist (role-hierarchy revision §19), attached to one
 * workload_assignment_proposals row — the natural "matter" unit this
 * codebase already has. Half the doc's example checklist is derived
 * live from the proposal's own workflow state (never stored, never
 * goes stale); the rest — things CMAS doesn't itself track — are
 * stored in proposal_checklist_items.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/**
 * The manually-tracked half of the checklist: things CMAS references
 * but doesn't own the authoritative record of (a linked legislative
 * matter, a meeting/hearing, research, the report itself, supporting
 * documents). Order matches role-hierarchy revision §19's example list.
 */
const MANUAL_CHECKLIST_ITEMS = [
    'legislative_matter_linked' => 'Legislative matter linked',
    'jurisdiction_verified'     => 'Jurisdiction verified',
    'meeting_checked'           => 'Related meeting checked',
    'hearing_checked'           => 'Related hearing checked if applicable',
    'research_linked'           => 'Research linked',
    'report_prepared'           => 'Committee report prepared',
    'report_reviewed'           => 'Report reviewed',
    'documents_linked'          => 'Supporting documents linked',
];

/**
 * The auto-derived half: real-time read-outs of this proposal's own
 * workflow state, never stored (so they can't go stale or be
 * second-guessed by a manual checkbox). Takes the array shape
 * getProposalWithContext() returns.
 */
function autoChecklistItems(array $proposal): array
{
    $responded = ($proposal['responded_at'] ?? null) !== null;
    return [
        ['label' => 'Committee identified',              'checked' => true],
        ['label' => 'AI evaluation completed',           'checked' => ($proposal['ai_recommendation_id'] ?? null) !== null],
        ['label' => 'Multiple recommendations generated', 'checked' => ($proposal['ai_recommendation_id'] ?? null) !== null],
        ['label' => 'Chairperson reviewed recommendation','checked' => ($proposal['proposed_by'] ?? null) !== null],
        ['label' => 'Member selected',                    'checked' => true],
        ['label' => 'Member accepted/declined',           'checked' => $responded],
        ['label' => 'Chairperson final approval',         'checked' => ($proposal['state'] ?? null) === 'Approved'],
    ];
}

/** Every manual item for one proposal, defaulting to unchecked when no row exists yet. */
function manualChecklistItems(PDO $pdo, int $proposalId): array
{
    static $tableExists = null;
    if ($tableExists === null) {
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'proposal_checklist_items'");
            $tableExists = (bool)($check && $check->fetch());
        } catch (Throwable $e) {
            $tableExists = false;
        }
    }

    if (!$tableExists) {
            return array_map(static fn($label, $key) => [
                'key' => $key,
                'label' => $label,
                'checked' => false,
                'checked_by_name' => null,
                'checked_at' => null,
                'notes' => null,
            ], MANUAL_CHECKLIST_ITEMS, array_keys(MANUAL_CHECKLIST_ITEMS));
    }

    $stmt = $pdo->prepare(
        'SELECT ci.*, u.full_name AS checked_by_name
         FROM proposal_checklist_items ci
         LEFT JOIN users u ON u.id = ci.checked_by
         WHERE ci.proposal_id = :id'
    );
    $stmt->execute([':id' => $proposalId]);
    $saved = [];
    foreach ($stmt->fetchAll() as $row) {
        $saved[$row['item_key']] = $row;
    }

    $items = [];
    foreach (MANUAL_CHECKLIST_ITEMS as $key => $label) {
        $row = $saved[$key] ?? null;
        $items[] = [
            'key' => $key,
            'label' => $label,
            'checked' => $row ? (bool)$row['is_checked'] : false,
            'checked_by_name' => $row['checked_by_name'] ?? null,
            'checked_at' => $row['checked_at'] ?? null,
            'notes' => $row['notes'] ?? null,
        ];
    }
    return $items;
}

/** ['checked' => int, 'total' => int] across both halves, for rollup/progress display. */
function checklistProgress(PDO $pdo, array $proposal): array
{
    $auto = autoChecklistItems($proposal);
    $manual = manualChecklistItems($pdo, (int)$proposal['proposal_id']);
    $checked = count(array_filter($auto, static fn($i) => $i['checked']))
        + count(array_filter($manual, static fn($i) => $i['checked']));
    return ['checked' => $checked, 'total' => count($auto) + count($manual)];
}

/** Insert-or-update one manual checklist item. */
function setChecklistItem(PDO $pdo, int $proposalId, string $itemKey, bool $checked, ?string $notes, int $updatedBy): void
{
    if (!array_key_exists($itemKey, MANUAL_CHECKLIST_ITEMS)) {
        throw new InvalidArgumentException('Unknown checklist item.');
    }
    $stmt = $pdo->prepare(
        'INSERT INTO proposal_checklist_items (proposal_id, item_key, is_checked, checked_by, checked_at, notes)
         VALUES (:pid, :key, :checked, :by, :checked_at, :notes)
         ON DUPLICATE KEY UPDATE
            is_checked = VALUES(is_checked), checked_by = VALUES(checked_by),
            checked_at = VALUES(checked_at), notes = VALUES(notes)'
    );
    $stmt->execute([
        ':pid' => $proposalId, ':key' => $itemKey, ':checked' => $checked ? 1 : 0,
        ':by' => $updatedBy, ':checked_at' => $checked ? date('Y-m-d H:i:s') : null,
        ':notes' => $notes,
    ]);
}
