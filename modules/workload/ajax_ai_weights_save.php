<?php
/**
 * modules/workload/ajax_ai_weights_save.php
 * ------------------------------------------------------------------
 * Saves admin-adjusted weights/enabled-state for the Smart AI
 * Workload Distribution engine. Expects a JSON body: an array of
 * { factor_key, weight, is_enabled } objects.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid request method.');
requireCsrf();

$raw = $_POST['factors'] ?? null;
if (!$raw) jsonResponse(false, 'No factor data received.');

$factors = json_decode($raw, true);
if (!is_array($factors)) jsonResponse(false, 'Invalid factor data.');

$pdo = db();
try {
    $pdo->beginTransaction();

    $validKeys = $pdo->query('SELECT factor_key FROM ai_weight_config')->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare(
        'UPDATE ai_weight_config SET weight = :weight, is_enabled = :enabled, updated_by = :by, updated_at = NOW()
         WHERE factor_key = :key'
    );

    foreach ($factors as $f) {
        $key = clean($f['factor_key'] ?? '');
        $weight = (int)($f['weight'] ?? 0);
        $enabled = !empty($f['is_enabled']) ? 1 : 0;

        if (!in_array($key, $validKeys, true)) continue; // ignore unknown keys defensively
        $weight = max(0, min(100, $weight));

        $stmt->execute([':weight' => $weight, ':enabled' => $enabled, ':by' => currentUserId(), ':key' => $key]);
    }

    $pdo->commit();

    logActivity(currentUserId(), 'Update', 'Updated Smart AI Workload Distribution factor weights.');
    jsonResponse(true, 'AI scoring weights updated successfully.');

} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('AI weight save error: ' . $e->getMessage());
    jsonResponse(false, 'A database error occurred while saving the weights.');
}
