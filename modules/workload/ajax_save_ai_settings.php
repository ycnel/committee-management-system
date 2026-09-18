<?php
/**
 * modules/workload/ajax_save_ai_settings.php
 * ------------------------------------------------------------------
 * Admin-only. Saves Google Gemini configuration: enabled flag,
 * model name, request timeout. Separate from the
 * rule-based factor-weight save (that stays wired to whatever
 * endpoint ai-settings.js already posts weights to) so neither can
 * break the other.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/GeminiAI.php';
requireRole([ROLE_ADMIN]);
requireCsrf();

$pdo = db();

$enabled = isset($_POST['gemini_enabled']) && $_POST['gemini_enabled'] === '1' ? '1' : '0';
$model = trim((string)($_POST['gemini_model'] ?? ''));
$timeout = (int)($_POST['gemini_timeout'] ?? 30);

if ($model === '') {
    jsonResponse(false, 'Please enter a Gemini model name, e.g. gemini-2.5-flash');
}
if ($timeout < 5 || $timeout > 120) {
    jsonResponse(false, 'Timeout must be between 5 and 120 seconds.');
}

try {
    $userId = currentUserId();
    GeminiAI::saveSetting($pdo, 'gemini_enabled', $enabled, $userId);
    GeminiAI::saveSetting($pdo, 'gemini_model', $model, $userId);
    GeminiAI::saveSetting($pdo, 'gemini_timeout', (string)$timeout, $userId);

    jsonResponse(true, 'Local AI settings saved.');
} catch (Throwable $e) {
    error_log('Save AI settings error: ' . $e->getMessage());
    jsonResponse(false, 'Could not save AI settings right now.');
}
