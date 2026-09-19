<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
session_write_close(); // read-only endpoint: release the session lock for concurrent requests

ob_start();
include __DIR__ . '/table.php';
$html = ob_get_clean();

jsonResponse(true, '', ['html' => $html]);
