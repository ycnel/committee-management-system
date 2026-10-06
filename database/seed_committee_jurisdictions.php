<?php
/**
 * Add the supplied scope records to their matching official committees.
 *
 * Run from the project root:
 *   php database/seed_committee_jurisdictions.php --dry-run
 *   php database/seed_committee_jurisdictions.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once __DIR__ . '/../config/database.php';

function normalizeCommitteeName(string $name): string
{
    return strtolower((string)preg_replace('/[^a-z0-9]/i', '', $name));
}

$dataPath = __DIR__ . '/committee_jurisdictions.json';
$jurisdictions = json_decode(
    (string)file_get_contents($dataPath),
    true,
    512,
    JSON_THROW_ON_ERROR
);

if (count($jurisdictions) !== 68) {
    throw new RuntimeException('Expected 68 jurisdiction records; refusing to continue.');
}

$pdo = db();
$committeeRows = $pdo->query('SELECT committee_id, committee_name FROM committees')->fetchAll();
$committeesByName = [];
foreach ($committeeRows as $committee) {
    $normalizedName = normalizeCommitteeName((string)$committee['committee_name']);
    $committeesByName[$normalizedName][] = $committee;
}

$recordCommittees = [];
$committeeIds = [];
foreach ($jurisdictions as $jurisdiction) {
    $normalizedCategory = normalizeCommitteeName((string)$jurisdiction['category']);
    $matches = $committeesByName[$normalizedCategory] ?? [];
    if (count($matches) !== 1) {
        throw new RuntimeException(
            'Expected exactly one committee for jurisdiction category "' . $jurisdiction['category'] . '".'
        );
    }
    $committee = $matches[0];
    $recordCommittees[] = $committee;
    $committeeIds[(int)$committee['committee_id']] = true;
}

if (count($committeeIds) !== 34) {
    throw new RuntimeException('Expected matching jurisdiction data for 34 committees; refusing to continue.');
}

$findJurisdiction = $pdo->prepare(
    'SELECT jurisdiction_id, category
     FROM jurisdictions
     WHERE jurisdiction_name = :name
     LIMIT 1'
);
$insertJurisdiction = $pdo->prepare(
    'INSERT INTO jurisdictions
        (jurisdiction_name, category, description, scope_definition, covered_areas,
         primary_responsibilities, typical_legislative_matters, outside_scope, notes, status)
     VALUES
        (:name, :category, :description, :scope_definition, :covered_areas,
         :primary_responsibilities, :typical_legislative_matters, :outside_scope, :notes, :status)'
);
$updateJurisdiction = $pdo->prepare(
    'UPDATE jurisdictions
     SET category = :category,
         description = :description,
         scope_definition = :scope_definition,
         covered_areas = :covered_areas,
         primary_responsibilities = :primary_responsibilities,
         typical_legislative_matters = :typical_legislative_matters,
         outside_scope = :outside_scope,
         notes = :notes,
         status = :status
     WHERE jurisdiction_id = :id'
);

$plannedInserts = 0;
$plannedUpdates = 0;
$resolved = [];
foreach ($jurisdictions as $index => $jurisdiction) {
    $committee = $recordCommittees[$index];
    $jurisdiction['category'] = (string)$committee['committee_name'];
    $findJurisdiction->execute([':name' => $jurisdiction['jurisdiction_name']]);
    $existing = $findJurisdiction->fetch();
    if ($existing) {
        if (
            $existing['category'] === null
            || normalizeCommitteeName((string)$existing['category']) !== normalizeCommitteeName($jurisdiction['category'])
        ) {
            throw new RuntimeException(
                'Jurisdiction name "' . $jurisdiction['jurisdiction_name'] . '" already exists under another category.'
            );
        }
        $jurisdiction['id'] = (int)$existing['jurisdiction_id'];
        $plannedUpdates++;
    } else {
        $plannedInserts++;
    }
    $resolved[] = $jurisdiction;
}

if (in_array('--dry-run', $argv, true)) {
    echo 'Validation passed: ' . count($resolved) . ' jurisdictions across ' . count($committeeIds) . " committees.\n";
    echo 'Would insert ' . $plannedInserts . ' and update ' . $plannedUpdates . " existing matching records.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    foreach ($resolved as $jurisdiction) {
        $values = [
            ':name' => $jurisdiction['jurisdiction_name'],
            ':category' => $jurisdiction['category'],
            ':description' => $jurisdiction['description'],
            ':scope_definition' => $jurisdiction['scope_definition'],
            ':covered_areas' => $jurisdiction['covered_areas'],
            ':primary_responsibilities' => $jurisdiction['primary_responsibilities'],
            ':typical_legislative_matters' => $jurisdiction['typical_legislative_matters'],
            ':outside_scope' => $jurisdiction['outside_scope'],
            ':notes' => $jurisdiction['notes'],
            ':status' => $jurisdiction['status'],
        ];

        if (isset($jurisdiction['id'])) {
            $values[':id'] = $jurisdiction['id'];
            $updateJurisdiction->execute($values);
        } else {
            $insertJurisdiction->execute($values);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo 'Jurisdiction data applied: inserted ' . $plannedInserts
    . ', updated ' . $plannedUpdates
    . ' across ' . count($committeeIds) . " committees.\n";
