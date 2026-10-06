<?php
/**
 * Replace sample committee records with the official 13th City Council roster.
 * Source: City Council Officers and Standing Committees Chairmanship,
 * as of January 16, 2026.
 *
 * Run from the project root after taking a full database backup:
 *   php database/replace_sample_committee_data.php
 *
 * Council-wide officer titles and jurisdiction descriptions are not stored
 * because the current request deferred those fields and the document gives
 * no jurisdiction mapping. Committee chair/vice-chair/member roles are stored
 * in the existing CMAS committee_members relationship.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$people = [
    'timothy' => 'HON. TIMOTHY OLIVER I. ZARCAL',
    'raymundo' => 'HON. RAYMUNDO R. YUPANGCO',
    'jaybee' => 'HON. JAYBEE S. HIZON',
    'louisa' => 'HON. LOUISA MARIE “LADY” J. QUINTOS-TAN',
    'joaquin' => 'HON. JOAQUIN ANDRE D. DOMAGOSO',
    'don_juan' => 'HON. DON JUAN “DJ” A. BAGATSING',
    'jesus' => 'HON. JESUS “TAGA” E. FAJARDO, JR.',
    'rafael' => 'HON. RAFAEL P. BORROMEO',
    'elmer' => 'HON. ELMER M. PAR',
    'darwin' => 'HON. DARWIN B. SIA',
    'enriqueta' => 'HON. ENRIQUETA “ERIKA” O. PLATON',
    'luciano' => 'HON. LUCIANO M. VELOSO',
    'mark_ryan' => 'HON. MARK RYAN B. PONCE',
    'voltaire' => 'HON. VOLTAIRE CARLO D. CASTAÑEDA',
    'christian' => 'HON. CHRISTIAN PAUL L. UY',
    'roberto' => 'HON. ROBERTO S. ESPIRITU II',
    'francis' => 'HON. FRANCIS MICHAEL U. ALMIRON',
    'irma' => 'HON. IRMA C. ALFONSO-JUSON',
    'edward' => 'HON. EDWARD M. TAN',
    'ernesto' => 'HON. ERNESTO “JONG” C. ISIP, JR.',
    'erick' => 'HON. ERICK IAN O. NIEVA',
    'eunice' => 'HON. EUNICE ANN DENICE G. CASTRO',
    'mark_anthony' => 'HON. MARK ANTHONY A. IGNACIO',
    'karen' => 'HON. KAREN C. ALIBARBAR',
    'arlene' => 'HON. ARLENE MAILE I. ATIENZA',
    'rosalino' => 'HON. ROSALINO “JHUN” P. IBAY, JR.',
    'pamela' => 'HON. PAMELA “FA” G. FUGOSO-PASCUAL',
    'fernando' => 'HON. FERNANDO S. MERCADO',
    'juliana' => 'HON. JULIANA RAE M. IBAY',
    'jefferson' => 'HON. JEFFERSON “JEFF” C. LAU',
    'john' => 'HON. JOHN CHRISTOPHER L. SY',
];

$committees = [
    ['name' => 'ACCOUNTABILITY OF PUBLIC OFFICERS AND INVESTIGATION (BLUE RIBBON)', 'chair' => 'jesus', 'vice' => 'timothy', 'members' => ['raymundo', 'louisa', 'jaybee', 'john', 'don_juan', 'elmer', 'rafael']],
    ['name' => 'AMUSEMENT, GAMES AND ENTERTAINMENT', 'chair' => 'darwin', 'vice' => 'mark_anthony', 'members' => ['raymundo', 'arlene', 'rosalino', 'roberto', 'don_juan', 'rafael', 'jesus']],
    ['name' => 'APPROPRIATIONS', 'chair' => 'timothy', 'vice' => 'jaybee', 'members' => ['raymundo', 'roberto', 'louisa', 'darwin', 'don_juan', 'elmer', 'rafael']],
    ['name' => 'BARANGAY AFFAIRS', 'chair' => 'enriqueta', 'vice' => 'juliana', 'members' => ['raymundo', 'voltaire', 'fernando', 'john', 'don_juan', 'elmer', 'jesus']],
    ['name' => 'CHILDREN’S AFFAIRS', 'chair' => 'luciano', 'vice' => 'juliana', 'members' => ['raymundo', 'francis', 'roberto', 'joaquin', 'don_juan', 'eunice', 'karen']],
    ['name' => 'CIVIL SERVICE, APPOINTMENTS AND REORGANIZATION', 'chair' => 'timothy', 'vice' => 'jaybee', 'members' => ['raymundo', 'john', 'mark_anthony', 'rosalino', 'don_juan', 'erick', 'fernando']],
    ['name' => 'CONSUMER’S AFFAIRS & PRICE CONTROL', 'chair' => 'mark_ryan', 'vice' => 'rosalino', 'members' => ['raymundo', 'francis', 'john', 'christian', 'don_juan', 'fernando', 'ernesto']],
    ['name' => 'COOPERATIVES, NGO’S & PEOPLE’S ORGANIZATIONS', 'chair' => 'voltaire', 'vice' => 'elmer', 'members' => ['raymundo', 'rosalino', 'joaquin', 'edward', 'don_juan', 'fernando', 'eunice']],
    ['name' => 'ECONOMIC DEVELOPMENT', 'chair' => 'christian', 'vice' => 'voltaire', 'members' => ['raymundo', 'roberto', 'edward', 'mark_anthony', 'don_juan', 'mark_ryan', 'ernesto']],
    ['name' => 'EDUCATION', 'chair' => 'joaquin', 'vice' => 'louisa', 'members' => ['raymundo', 'mark_anthony', 'timothy', 'roberto', 'don_juan', 'juliana', 'eunice']],
    ['name' => 'ENGINEERING AND PUBLIC WORKS', 'chair' => 'rafael', 'vice' => 'darwin', 'members' => ['raymundo', 'roberto', 'voltaire', 'joaquin', 'don_juan', 'elmer', 'jesus']],
    ['name' => 'ENVIRONMENTAL PROTECTION AND ECOLOGICAL PRESERVATION AND SANITATION AND ANIMAL WELFARE', 'chair' => 'louisa', 'vice' => 'juliana', 'members' => ['raymundo', 'arlene', 'joaquin', 'darwin', 'don_juan', 'mark_ryan', 'rafael']],
    ['name' => 'FAMILY RELATIONS AND MORAL RECOVERY', 'chair' => 'francis', 'vice' => 'joaquin', 'members' => ['raymundo', 'darwin', 'irma', 'elmer', 'don_juan', 'juliana', 'erick']],
    ['name' => 'HEALTH', 'chair' => 'irma', 'vice' => 'francis', 'members' => ['raymundo', 'louisa', 'luciano', 'christian', 'don_juan', 'fernando', 'juliana']],
    ['name' => 'HOUSING, LAND, URBAN PLANNING DEVELOPMENT AND RESETTLEMENT', 'chair' => 'roberto', 'vice' => 'darwin', 'members' => ['raymundo', 'louisa', 'mark_anthony', 'christian', 'don_juan', 'rafael', 'elmer']],
    ['name' => 'INFORMATION AND COMMUNICATION TECHNOLOGY', 'chair' => 'edward', 'vice' => 'mark_ryan', 'members' => ['raymundo', 'christian', 'john', 'francis', 'don_juan', 'rafael', 'pamela']],
    ['name' => 'INTERNATIONAL RELATIONS', 'chair' => 'edward', 'vice' => 'christian', 'members' => ['raymundo', 'irma', 'ernesto', 'roberto', 'don_juan', 'eunice', 'fernando']],
    ['name' => 'JUSTICE AND HUMAN RIGHTS', 'chair' => 'ernesto', 'vice' => 'jaybee', 'members' => ['raymundo', 'rosalino', 'voltaire', 'roberto', 'don_juan', 'juliana', 'karen']],
    ['name' => 'LABOR, EMPLOYMENT AND HUMAN RESOURCES', 'chair' => 'erick', 'vice' => 'jesus', 'members' => ['raymundo', 'voltaire', 'arlene', 'francis', 'don_juan', 'eunice', 'juliana']],
    ['name' => 'LAWS', 'chair' => 'jaybee', 'vice' => 'louisa', 'members' => ['raymundo', 'timothy', 'roberto', 'elmer', 'don_juan', 'ernesto', 'juliana']],
    ['name' => 'LIVELIHOOD', 'chair' => 'eunice', 'vice' => 'timothy', 'members' => ['raymundo', 'louisa', 'francis', 'christian', 'don_juan', 'fernando', 'pamela']],
    ['name' => 'MARKETS, HAWKERS AND SLAUGHTERHOUSES', 'chair' => 'elmer', 'vice' => 'rafael', 'members' => ['raymundo', 'irma', 'voltaire', 'jaybee', 'don_juan', 'erick', 'jesus']],
    ['name' => 'MIGRANT WORKERS', 'chair' => 'karen', 'vice' => 'don_juan', 'members' => ['raymundo', 'edward', 'luciano', 'darwin', 'mark_ryan', 'elmer', 'ernesto']],
    ['name' => 'OVERSIGHT', 'chair' => 'raymundo', 'vice' => 'jaybee', 'members' => ['louisa', 'christian', 'timothy', 'roberto', 'don_juan', 'rafael', 'jesus']],
    ['name' => 'PATRIMONIAL PROPERTIES', 'chair' => 'arlene', 'vice' => 'timothy', 'members' => ['raymundo', 'ernesto', 'roberto', 'mark_anthony', 'don_juan', 'rafael', 'jesus']],
    ['name' => 'POLICE, PEACE AND ORDER, FIRE AND PUBLIC SAFETY', 'chair' => 'rosalino', 'vice' => 'edward', 'members' => ['raymundo', 'joaquin', 'darwin', 'john', 'don_juan', 'jesus', 'mark_ryan']],
    ['name' => 'RULES AND ETHICS', 'chair' => 'jaybee', 'vice' => 'louisa', 'members' => ['raymundo', 'timothy', 'edward', 'voltaire', 'don_juan', 'ernesto', 'fernando']],
    ['name' => 'SCIENCE AND TECHNOLOGY', 'chair' => 'pamela', 'vice' => 'don_juan', 'members' => ['raymundo', 'edward', 'mark_anthony', 'luciano', 'mark_ryan', 'juliana', 'jefferson']],
    ['name' => 'SENIOR CITIZENS AND DIFFERENTLY-ABLED PERSONS', 'chair' => 'erick', 'vice' => 'luciano', 'members' => ['raymundo', 'irma', 'john', 'mark_anthony', 'don_juan', 'rafael', 'pamela']],
    ['name' => 'SOCIAL WELFARE', 'chair' => 'joaquin', 'vice' => 'irma', 'members' => ['raymundo', 'john', 'mark_anthony', 'francis', 'don_juan', 'juliana', 'pamela']],
    ['name' => 'TOURISM, ARTS AND CULTURE', 'chair' => 'luciano', 'vice' => 'john', 'members' => ['raymundo', 'arlene', 'christian', 'edward', 'don_juan', 'eunice', 'karen']],
    ['name' => 'TRADE AND INDUSTRY', 'chair' => 'fernando', 'vice' => 'eunice', 'members' => ['raymundo', 'voltaire', 'francis', 'timothy', 'don_juan', 'elmer', 'juliana']],
    ['name' => 'TRANSPORTATION', 'chair' => 'mark_anthony', 'vice' => 'jesus', 'members' => ['raymundo', 'rosalino', 'darwin', 'arlene', 'don_juan', 'fernando', 'rafael']],
    ['name' => 'URBAN POOR', 'chair' => 'jefferson', 'vice' => 'darwin', 'members' => ['raymundo', 'luciano', 'voltaire', 'jaybee', 'don_juan', 'mark_ryan', 'karen']],
    ['name' => 'UTILITY AND FRANCHISE', 'chair' => 'john', 'vice' => 'rafael', 'members' => ['raymundo', 'mark_anthony', 'edward', 'arlene', 'don_juan', 'jesus', 'erick']],
    ['name' => 'WAYS AND MEANS', 'chair' => 'don_juan', 'vice' => 'luciano', 'members' => ['raymundo', 'elmer', 'jaybee', 'louisa', 'edward', 'rafael', 'jesus']],
    ['name' => 'WOMEN', 'chair' => 'irma', 'vice' => 'eunice', 'members' => ['raymundo', 'luciano', 'louisa', 'arlene', 'don_juan', 'juliana', 'mark_ryan']],
    ['name' => 'YOUTH VALUES FORMATION AND SPORTS DEVELOPMENT', 'chair' => 'juliana', 'vice' => 'eunice', 'members' => ['raymundo', 'timothy', 'louisa', 'jaybee', 'don_juan', 'elmer', 'joaquin']],
];

$committeePoliticalGroups = [
    'ACCOUNTABILITY OF PUBLIC OFFICERS AND INVESTIGATION (BLUE RIBBON)' => ['Majority' => ['raymundo', 'louisa', 'jaybee', 'john'], 'Minority' => ['don_juan', 'elmer', 'rafael']],
    'AMUSEMENT, GAMES AND ENTERTAINMENT' => ['Majority' => ['raymundo', 'arlene', 'rosalino', 'roberto'], 'Minority' => ['don_juan', 'rafael', 'jesus']],
    'APPROPRIATIONS' => ['Majority' => ['raymundo', 'roberto', 'louisa', 'darwin'], 'Minority' => ['don_juan', 'elmer', 'rafael']],
    'BARANGAY AFFAIRS' => ['Majority' => ['raymundo', 'voltaire', 'fernando', 'john'], 'Minority' => ['don_juan', 'elmer', 'jesus']],
    'CHILDREN’S AFFAIRS' => ['Majority' => ['raymundo', 'francis', 'roberto', 'joaquin'], 'Minority' => ['don_juan', 'eunice', 'karen']],
    'CIVIL SERVICE, APPOINTMENTS AND REORGANIZATION' => ['Majority' => ['raymundo', 'john', 'mark_anthony', 'rosalino'], 'Minority' => ['don_juan', 'erick', 'fernando']],
    'CONSUMER’S AFFAIRS & PRICE CONTROL' => ['Majority' => ['raymundo', 'francis', 'john', 'christian'], 'Minority' => ['don_juan', 'fernando', 'ernesto']],
    'COOPERATIVES, NGO’S & PEOPLE’S ORGANIZATIONS' => ['Majority' => ['raymundo', 'rosalino', 'joaquin', 'edward'], 'Minority' => ['don_juan', 'fernando', 'eunice']],
    'ECONOMIC DEVELOPMENT' => ['Majority' => ['raymundo', 'roberto', 'edward', 'mark_anthony'], 'Minority' => ['don_juan', 'mark_ryan', 'ernesto']],
    'EDUCATION' => ['Majority' => ['raymundo', 'mark_anthony', 'timothy', 'roberto'], 'Minority' => ['don_juan', 'juliana', 'eunice']],
    'ENGINEERING AND PUBLIC WORKS' => ['Majority' => ['raymundo', 'roberto', 'voltaire', 'joaquin'], 'Minority' => ['don_juan', 'elmer', 'jesus']],
    'ENVIRONMENTAL PROTECTION AND ECOLOGICAL PRESERVATION AND SANITATION AND ANIMAL WELFARE' => ['Majority' => ['raymundo', 'arlene', 'joaquin', 'darwin'], 'Minority' => ['don_juan', 'mark_ryan', 'rafael']],
    'FAMILY RELATIONS AND MORAL RECOVERY' => ['Majority' => ['raymundo', 'darwin', 'irma'], 'Minority' => ['don_juan', 'juliana', 'erick']],
    'HEALTH' => ['Majority' => ['raymundo', 'louisa', 'luciano', 'christian'], 'Minority' => ['don_juan', 'fernando', 'juliana']],
    'HOUSING, LAND, URBAN PLANNING DEVELOPMENT AND RESETTLEMENT' => ['Majority' => ['raymundo', 'louisa', 'mark_anthony', 'christian'], 'Minority' => ['don_juan', 'rafael', 'elmer']],
    'INFORMATION AND COMMUNICATION TECHNOLOGY' => ['Majority' => ['raymundo', 'christian', 'john', 'francis'], 'Minority' => ['don_juan', 'rafael', 'pamela']],
    'INTERNATIONAL RELATIONS' => ['Majority' => ['raymundo', 'irma', 'ernesto', 'roberto'], 'Minority' => ['don_juan', 'eunice', 'fernando']],
    'JUSTICE AND HUMAN RIGHTS' => ['Majority' => ['raymundo', 'rosalino', 'voltaire', 'roberto'], 'Minority' => ['don_juan', 'juliana', 'karen']],
    'LABOR, EMPLOYMENT AND HUMAN RESOURCES' => ['Majority' => ['raymundo', 'voltaire', 'arlene', 'francis'], 'Minority' => ['don_juan', 'eunice', 'juliana']],
    'LAWS' => ['Majority' => ['raymundo', 'timothy', 'roberto'], 'Minority' => ['don_juan', 'ernesto', 'juliana']],
    'LIVELIHOOD' => ['Majority' => ['raymundo', 'louisa', 'francis', 'christian'], 'Minority' => ['don_juan', 'fernando', 'pamela']],
    'MARKETS, HAWKERS AND SLAUGHTERHOUSES' => ['Majority' => ['raymundo', 'irma', 'voltaire', 'jaybee'], 'Minority' => ['don_juan', 'erick', 'jesus']],
    'MIGRANT WORKERS' => ['Majority' => ['raymundo', 'edward', 'luciano', 'darwin'], 'Minority' => ['mark_ryan', 'elmer', 'ernesto']],
    'OVERSIGHT' => ['Majority' => ['louisa', 'christian', 'timothy', 'roberto'], 'Minority' => ['don_juan', 'rafael', 'jesus']],
    'PATRIMONIAL PROPERTIES' => ['Majority' => ['raymundo', 'ernesto', 'roberto', 'mark_anthony'], 'Minority' => ['don_juan', 'rafael', 'jesus']],
    'POLICE, PEACE AND ORDER, FIRE AND PUBLIC SAFETY' => ['Majority' => ['raymundo', 'joaquin', 'darwin', 'john'], 'Minority' => ['don_juan', 'jesus', 'mark_ryan']],
    'RULES AND ETHICS' => ['Majority' => ['raymundo', 'timothy', 'edward', 'voltaire'], 'Minority' => ['don_juan', 'ernesto', 'fernando']],
    'SCIENCE AND TECHNOLOGY' => ['Majority' => ['raymundo', 'edward', 'mark_anthony', 'luciano'], 'Minority' => ['mark_ryan', 'juliana', 'jefferson']],
    'SENIOR CITIZENS AND DIFFERENTLY-ABLED PERSONS' => ['Majority' => ['raymundo', 'irma', 'john', 'mark_anthony'], 'Minority' => ['don_juan', 'rafael', 'pamela']],
    'SOCIAL WELFARE' => ['Majority' => ['raymundo', 'john', 'mark_anthony', 'francis'], 'Minority' => ['don_juan', 'juliana', 'pamela']],
    'TOURISM, ARTS AND CULTURE' => ['Majority' => ['raymundo', 'arlene', 'christian', 'edward'], 'Minority' => ['don_juan', 'eunice', 'karen']],
    'TRADE AND INDUSTRY' => ['Majority' => ['raymundo', 'voltaire', 'francis', 'timothy'], 'Minority' => ['don_juan', 'elmer', 'juliana']],
    'TRANSPORTATION' => ['Majority' => ['raymundo', 'rosalino', 'darwin', 'arlene'], 'Minority' => ['don_juan', 'fernando', 'rafael']],
    'URBAN POOR' => ['Majority' => ['raymundo', 'luciano', 'voltaire', 'jaybee'], 'Minority' => ['don_juan', 'mark_ryan', 'karen']],
    'UTILITY AND FRANCHISE' => ['Majority' => ['raymundo', 'mark_anthony', 'edward', 'arlene'], 'Minority' => ['don_juan', 'jesus', 'erick']],
    'WAYS AND MEANS' => ['Majority' => ['raymundo', 'jaybee', 'louisa', 'edward'], 'Minority' => ['elmer', 'rafael', 'jesus']],
    'WOMEN' => ['Majority' => ['raymundo', 'luciano', 'louisa', 'arlene'], 'Minority' => ['don_juan', 'juliana', 'mark_ryan']],
    'YOUTH VALUES FORMATION AND SPORTS DEVELOPMENT' => ['Majority' => ['raymundo', 'timothy', 'louisa', 'jaybee'], 'Minority' => ['don_juan', 'elmer']],
];

if (count($people) !== 31 || count($committees) !== 38) {
    throw new RuntimeException('Roster count validation failed.');
}

$knownCommitteeNames = [];
foreach ($committees as $committee) {
    if (isset($knownCommitteeNames[$committee['name']])) {
        throw new RuntimeException('Duplicate committee in import list: ' . $committee['name']);
    }
    $knownCommitteeNames[$committee['name']] = true;
    $assignedPeople = [$committee['chair'], $committee['vice'], ...$committee['members']];
    if (count($assignedPeople) !== count(array_unique($assignedPeople))) {
        throw new RuntimeException('Duplicate person assignment in committee: ' . $committee['name']);
    }
    foreach ($assignedPeople as $personKey) {
        if (!isset($people[$personKey])) {
            throw new RuntimeException('Unknown person key ' . $personKey . ' in ' . $committee['name']);
        }
    }
}

if (in_array('--dry-run', $argv, true)) {
    $membershipCount = array_sum(array_map(
        static fn(array $committee): int => 2 + count($committee['members']),
        $committees
    ));
    echo 'Roster validation passed: ' . count($committees) . ' committees, '
        . count($people) . ' people, ' . $membershipCount . " committee memberships.\n";
    exit(0);
}

$pdo = db();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS cmas_data_migrations (
        migration_key VARCHAR(191) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$migrationKey = 'official_city_council_roster_2026_01_16';
$migrationCheck = $pdo->prepare('SELECT migration_key FROM cmas_data_migrations WHERE migration_key = :key');
$migrationCheck->execute([':key' => $migrationKey]);
if ($migrationCheck->fetchColumn()) {
    throw new RuntimeException('This roster replacement has already been applied; refusing to delete data again.');
}

$committeeRoleQuery = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
$committeeRoleQuery->execute([':name' => ROLE_COMMITTEE]);
$committeeRoleId = $committeeRoleQuery->fetchColumn();
if (!$committeeRoleId) {
    throw new RuntimeException('The Committee Member role is missing.');
}

$pdo->beginTransaction();
try {
    $oldCommitteeCount = (int)$pdo->query('SELECT COUNT(*) FROM committees')->fetchColumn();
    $oldJurisdictionCount = (int)$pdo->query('SELECT COUNT(*) FROM jurisdictions')->fetchColumn();
    $oldAssignmentCount = (int)$pdo->query('SELECT COUNT(*) FROM workload_assignments')->fetchColumn();
    $oldProposalCount = (int)$pdo->query('SELECT COUNT(*) FROM workload_assignment_proposals')->fetchColumn();

    $personUserIds = [];
    $findUser = $pdo->prepare('SELECT id FROM users WHERE full_name = :name ORDER BY id LIMIT 2');
    $createUser = $pdo->prepare(
        'INSERT INTO users (full_name, email, password, role_id, status)
         VALUES (:name, :email, :password, :role_id, \'Inactive\')'
    );

    foreach ($people as $personKey => $fullName) {
        $findUser->execute([':name' => $fullName]);
        $matches = $findUser->fetchAll(PDO::FETCH_COLUMN);
        if (count($matches) > 1) {
            throw new RuntimeException('Multiple existing accounts match ' . $fullName . '.');
        }
        if ($matches) {
            $personUserIds[$personKey] = (int)$matches[0];
            continue;
        }

        $placeholderEmail = 'councilor.' . $personKey . '@cmas.invalid';
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $createUser->execute([
            ':name' => $fullName,
            ':email' => $placeholderEmail,
            ':password' => $passwordHash,
            ':role_id' => (int)$committeeRoleId,
        ]);
        $personUserIds[$personKey] = (int)$pdo->lastInsertId();
    }

    // Archive-linked reports explicitly; the committee FK uses SET NULL.
    // All other committee-dependent rows are removed by their existing
    // ON DELETE CASCADE relationships.
    $pdo->exec('DELETE FROM committee_reports WHERE committee_id IS NOT NULL');
    $pdo->exec('DELETE FROM committees');
    $pdo->exec('DELETE FROM jurisdictions');

    $insertCommittee = $pdo->prepare(
        'INSERT INTO committees (committee_name, description, jurisdiction_id, status, date_created, created_by)
         VALUES (:name, NULL, NULL, \'Active\', NULL, NULL)'
    );
    $insertMember = $pdo->prepare(
        'INSERT INTO committee_members (committee_id, user_id, member_role, political_group, assigned_date, status)
         VALUES (:committee_id, :user_id, :member_role, :political_group, NULL, \'Active\')'
    );

    foreach ($committees as $committee) {
        $insertCommittee->execute([':name' => $committee['name']]);
        $committeeId = (int)$pdo->lastInsertId();
        $memberships = [
            [$committee['chair'], 'Chairperson'],
            [$committee['vice'], 'Vice Chairperson'],
        ];
        foreach ($committee['members'] as $personKey) {
            $memberships[] = [$personKey, 'Member'];
        }
        foreach ($memberships as [$personKey, $memberRole]) {
            $politicalGroup = null;
            foreach (['Majority', 'Minority'] as $groupName) {
                if (isset($committeePoliticalGroups[$committee['name']][$groupName]) && in_array($personKey, $committeePoliticalGroups[$committee['name']][$groupName], true)) {
                    $politicalGroup = $groupName;
                    break;
                }
            }
            $insertMember->execute([
                ':committee_id' => $committeeId,
                ':user_id' => $personUserIds[$personKey],
                ':member_role' => $memberRole,
                ':political_group' => $politicalGroup,
            ]);
        }
    }

    $markApplied = $pdo->prepare('INSERT INTO cmas_data_migrations (migration_key) VALUES (:key)');
    $markApplied->execute([':key' => $migrationKey]);
    $pdo->commit();

    echo "Official roster imported successfully.\n";
    echo 'Committees: ' . count($committees) . "\n";
    echo 'Council members represented: ' . count($people) . "\n";
    echo 'Old committees removed: ' . $oldCommitteeCount . "\n";
    echo 'Old jurisdictions removed: ' . $oldJurisdictionCount . "\n";
    echo 'Old assignments removed by cascade: ' . $oldAssignmentCount . "\n";
    echo 'Old proposals removed by cascade: ' . $oldProposalCount . "\n";
    echo "Placeholder accounts are Inactive with non-routable .invalid emails.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
