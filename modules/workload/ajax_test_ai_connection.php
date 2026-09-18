<?php
/**
 * modules/workload/ajax_test_ai_connection.php
 * ------------------------------------------------------------------
 * Admin-only. Checks the configured Gemini API (no inference run)
 * and reports whether it's reachable and whether the configured
 * model is available. Powers the "Test AI Connection" button on
 * Smart AI Settings.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/GeminiAI.php';
requireRole([ROLE_ADMIN]);

$pdo = db();

try {
    $gemini = new GeminiAI($pdo);
    $status = $gemini->testConnection();
    jsonResponse(true, '', ['status' => $status]);
} catch (Throwable $e) {
    error_log('AI test connection error: ' . $e->getMessage());
    jsonResponse(false, 'Could not run the connection test.');
}
