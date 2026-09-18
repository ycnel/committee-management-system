<?php
/**
 * index.php (project root)
 * ------------------------------------------------------------------
 * Entry point for the Committee Management and Assignment System.
 * Sends signed-in users to the dashboard and everyone else to login.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';

redirect(APP_URL . (isLoggedIn() ? '/dashboard.php' : '/login.php'));
