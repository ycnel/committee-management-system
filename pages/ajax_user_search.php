<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole([ROLE_ADMIN]);
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

ob_start();
include __DIR__ . '/users_table.php';
$html = ob_get_clean();

jsonResponse(true, '', ['html' => $html]);
