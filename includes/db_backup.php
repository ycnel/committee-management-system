<?php
/**
 * includes/db_backup.php
 * ------------------------------------------------------------------
 * Full-database backup/restore for Super Admin's System module.
 * Implemented in pure PDO rather than shelling out to `mysqldump` —
 * exec()/shell_exec() are disabled on a lot of shared hosting, and a
 * missing/mismatched mysqldump binary path is a common deployment
 * footgun. This keeps backup/restore working anywhere the app itself
 * already runs, at the cost of being slower on very large databases.
 * ------------------------------------------------------------------
 */

/**
 * Stream a full logical backup of the current database as a single
 * downloadable .sql file: schema (CREATE TABLE ... exact DDL, via
 * SHOW CREATE TABLE so indexes/foreign keys/auto-increment come along)
 * followed by data (INSERT statements, batched for reasonably sized
 * files). Writes directly to output and exits — call this last.
 */
function streamDatabaseBackup(PDO $pdo): void
{
    $dbName = DB_NAME;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    $filename = 'cmas_backup_' . $dbName . '_' . date('Y-m-d_His') . '.sql';
    header('Content-Type: application/sql; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    echo "-- CMAS database backup\n";
    echo "-- Database: {$dbName}\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . " by " . (currentUser()['full_name'] ?? 'Super Admin') . "\n";
    echo "-- Generated with includes/db_backup.php (pure PHP/PDO, no mysqldump)\n\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';

        // ---- Schema ----
        $createRow = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_NUM);
        $createSql = $createRow[1] ?? '';
        echo "-- ----------------------------\n-- Table: {$table}\n-- ----------------------------\n";
        echo "DROP TABLE IF EXISTS {$quotedTable};\n";
        echo $createSql . ";\n\n";

        // ---- Data, streamed in chunks so large tables don't blow memory ----
        $countStmt = $pdo->query("SELECT COUNT(*) FROM {$quotedTable}");
        $total = (int)$countStmt->fetchColumn();
        if ($total === 0) {
            echo "\n";
            continue;
        }

        $chunkSize = 500;
        $colsStmt = $pdo->query("SELECT * FROM {$quotedTable} LIMIT 1");
        $columnCount = $colsStmt->columnCount();
        $columnNames = [];
        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $colsStmt->getColumnMeta($i);
            $columnNames[] = '`' . str_replace('`', '``', $meta['name']) . '`';
        }
        $columnList = implode(', ', $columnNames);

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $rowsStmt = $pdo->query("SELECT * FROM {$quotedTable} LIMIT {$chunkSize} OFFSET {$offset}");
            $valueGroups = [];
            while ($row = $rowsStmt->fetch(PDO::FETCH_NUM)) {
                $values = array_map(static function ($value) use ($pdo): string {
                    if ($value === null) return 'NULL';
                    return $pdo->quote((string)$value);
                }, $row);
                $valueGroups[] = '(' . implode(', ', $values) . ')';
            }
            if ($valueGroups) {
                echo "INSERT INTO {$quotedTable} ({$columnList}) VALUES\n"
                    . implode(",\n", $valueGroups) . ";\n";
            }
            flush();
        }
        echo "\n";
    }

    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit;
}

/**
 * Restore from an uploaded .sql file: split into individual statements
 * and execute each inside a transaction. Any single statement failing
 * rolls back the whole restore rather than leaving the database
 * half-migrated. Returns ['statements' => int, 'error' => ?string].
 *
 * This is intentionally a plain statement splitter (on unescaped `;`),
 * not a full SQL parser — it only needs to correctly replay files that
 * this same function (or the project's own database/*.sql dumps) wrote,
 * not arbitrary hostile SQL. Restore is Super-Admin-only and requires a
 * typed confirmation phrase in the UI for exactly this reason.
 */
function restoreDatabaseFromSql(PDO $pdo, string $sqlFilePath): array
{
    $sql = file_get_contents($sqlFilePath);
    if ($sql === false) {
        return ['statements' => 0, 'error' => 'Could not read the uploaded file.'];
    }

    // Strip full-line "-- comment" lines before splitting. Safe for dumps
    // this same file generates (streamDatabaseBackup() never emits data
    // that starts a physical line with "--" — string values are always
    // wrapped starting with a quote), and this is the format restore is
    // meant to consume. Without this, a comment line glued onto the front
    // of the next real statement (nothing separates them but a newline)
    // made the "skip pure-comment chunks" check below skip real SQL too
    // — e.g. a DROP TABLE right after a "-- Table: x" header line was
    // silently discarded, and the CREATE TABLE two lines later then
    // failed with "table already exists".
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    $statements = splitSqlStatements($sql);
    if (!$statements) {
        return ['statements' => 0, 'error' => 'No SQL statements found in the uploaded file.'];
    }

    // NOTE on atomicity: DROP TABLE / CREATE TABLE (and other DDL) cause
    // an implicit COMMIT in MySQL/MariaDB, so beginTransaction()/rollBack()
    // below cannot fully undo a restore that fails partway through — any
    // table already recreated by that point stays recreated. This is a
    // MySQL server limitation, not something the application layer can
    // paper over. The transaction still rolls back a partially-inserted
    // batch of rows within one table's data section, which is the common
    // failure mode (e.g. a truncated/corrupted upload).
    $executed = 0;
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->beginTransaction();
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') continue;
            $pdo->exec($statement);
            $executed++;
        }
        // Only commit if a transaction is actually still open — the DDL
        // above (DROP/CREATE TABLE) almost always closed it early via an
        // implicit commit, and calling commit() with nothing to commit
        // throws "There is no active transaction", which would otherwise
        // report a fully-successful restore as a failure.
        if ($pdo->inTransaction()) $pdo->commit();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        return ['statements' => $executed, 'error' => null];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        return ['statements' => $executed, 'error' => $e->getMessage()];
    }
}

/**
 * Split a .sql dump into individual statements on top-level semicolons,
 * ignoring semicolons inside '...'/"..."/`...` strings so INSERTed text
 * containing ";" doesn't get cut mid-value.
 */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inString = null; // one of ' " ` or null
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $buffer .= $char;

        if ($inString !== null) {
            if ($char === '\\' && $inString !== '`') {
                // Consume the escaped character too so we don't mistake it for the closing quote.
                if ($i + 1 < $length) { $buffer .= $sql[++$i]; }
                continue;
            }
            if ($char === $inString) $inString = null;
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $inString = $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = substr($buffer, 0, -1);
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') $statements[] = $buffer;

    return $statements;
}
