<?php
/**
 * includes/availability.php
 * ------------------------------------------------------------------
 * Shared helpers for Availability / Pause Mode (role-hierarchy
 * revision §11). See database/migration_member_availability.sql for
 * the append-only history design and the "no row = Available" default.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/** Statuses that keep a member eligible for new AI-assisted recommendations. */
const AVAILABILITY_ELIGIBLE_STATUSES = ['Available'];

/** Human labels + Bootstrap color for each status, reused by every availability badge in the UI. */
function availabilityStatusMeta(string $status): array
{
    $map = [
        'Available'   => ['label' => 'Available',   'color' => 'success'],
        'Unavailable' => ['label' => 'Unavailable',  'color' => 'danger'],
        'Idle'        => ['label' => 'Idle / Paused','color' => 'warning'],
        'Emergency'   => ['label' => 'Emergency',    'color' => 'dark'],
    ];
    return $map[$status] ?? ['label' => $status, 'color' => 'secondary'];
}

/**
 * The CURRENT, effective availability for one committee_member_id: the
 * most recent history row, with an expired end_date (already in the
 * past) treated as having lapsed back to Available. The underlying row
 * is never mutated by this — expiry is computed at read time only, so
 * the history stays an accurate record of what was actually set and
 * when, per §11's "availability history is preserved".
 *
 * Always returns a full shape, even with no history at all:
 *   ['status', 'reason', 'start_date', 'end_date', 'updated_by',
 *    'updated_by_name', 'created_at', 'is_default', 'is_expired']
 */
function currentAvailability(PDO $pdo, int $committeeMemberId): array
{
    $stmt = $pdo->prepare(
        'SELECT a.*, u.full_name AS updated_by_name
         FROM member_availability a
         LEFT JOIN users u ON u.id = a.updated_by
         WHERE a.committee_member_id = :id
         ORDER BY a.availability_id DESC
         LIMIT 1'
    );
    $stmt->execute([':id' => $committeeMemberId]);
    $row = $stmt->fetch();

    if (!$row) {
        return [
            'status' => 'Available', 'reason' => null, 'start_date' => null, 'end_date' => null,
            'updated_by' => null, 'updated_by_name' => null, 'created_at' => null,
            'is_default' => true, 'is_expired' => false,
        ];
    }

    $isExpired = $row['end_date'] !== null && $row['end_date'] < date('Y-m-d') && $row['status'] !== 'Available';
    return [
        'status' => $isExpired ? 'Available' : $row['status'],
        'reason' => $row['reason'],
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
        'updated_by' => $row['updated_by'] !== null ? (int)$row['updated_by'] : null,
        'updated_by_name' => $row['updated_by_name'],
        'created_at' => $row['created_at'],
        'is_default' => false,
        'is_expired' => $isExpired,
    ];
}

/** True only when the member's current, effective status is Available. */
function isAvailableForAssignment(PDO $pdo, int $committeeMemberId): bool
{
    return in_array(currentAvailability($pdo, $committeeMemberId)['status'], AVAILABILITY_ELIGIBLE_STATUSES, true);
}

/**
 * Current availability for every Active member of one committee, keyed
 * by committee_member_id — one query instead of N, for member-list and
 * AI-candidate-pool rendering.
 */
function committeeAvailabilityMap(PDO $pdo, int $committeeId): array
{
    // Latest history row per committee_member_id in this committee.
    $stmt = $pdo->prepare(
        'SELECT a.*, u.full_name AS updated_by_name
         FROM member_availability a
         INNER JOIN (
             SELECT committee_member_id, MAX(availability_id) AS max_id
             FROM member_availability
             WHERE committee_member_id IN (
                 SELECT committee_member_id FROM committee_members WHERE committee_id = :cid
             )
             GROUP BY committee_member_id
         ) latest ON latest.committee_member_id = a.committee_member_id AND latest.max_id = a.availability_id
         LEFT JOIN users u ON u.id = a.updated_by'
    );
    $stmt->execute([':cid' => $committeeId]);

    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $isExpired = $row['end_date'] !== null && $row['end_date'] < date('Y-m-d') && $row['status'] !== 'Available';
        $map[(int)$row['committee_member_id']] = [
            'status' => $isExpired ? 'Available' : $row['status'],
            'reason' => $row['reason'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'updated_by_name' => $row['updated_by_name'],
            'created_at' => $row['created_at'],
            'is_expired' => $isExpired,
        ];
    }
    return $map; // committee_member_id not present here => default Available
}

/**
 * Record a new availability status (always inserts a new history row —
 * never updates one in place). Returns the new availability_id.
 */
function setAvailability(
    PDO $pdo,
    int $committeeMemberId,
    string $status,
    ?string $reason,
    ?string $startDate,
    ?string $endDate,
    int $updatedBy
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO member_availability (committee_member_id, status, reason, start_date, end_date, updated_by, created_at)
         VALUES (:cmid, :status, :reason, :start, :end, :by, NOW())'
    );
    $stmt->execute([
        ':cmid' => $committeeMemberId, ':status' => $status, ':reason' => $reason,
        ':start' => $startDate, ':end' => $endDate, ':by' => $updatedBy,
    ]);
    return (int)$pdo->lastInsertId();
}

/** Full history for one committee_member_id, newest first. */
function availabilityHistory(PDO $pdo, int $committeeMemberId, int $limit = 20): array
{
    $limit = max(1, min($limit, 100));
    $stmt = $pdo->prepare(
        "SELECT a.*, u.full_name AS updated_by_name
         FROM member_availability a
         LEFT JOIN users u ON u.id = a.updated_by
         WHERE a.committee_member_id = :id
         ORDER BY a.availability_id DESC
         LIMIT $limit"
    );
    $stmt->execute([':id' => $committeeMemberId]);
    return $stmt->fetchAll();
}