<?php
/**
 * auth/session_check.php
 * ------------------------------------------------------------------
 * Lightweight authenticated heartbeat. The shared auth bootstrap checks
 * the per-user session token before this response is generated, so an
 * older browser receives the normal session-replaced JSON response.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../includes/auth.php';

requireLogin();
jsonResponse(true, 'Session is active.');
