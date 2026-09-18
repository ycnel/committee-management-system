<?php
/**
 * modules/committees/ajax_search.php
 * ------------------------------------------------------------------
 * Returns the filtered/sorted/paginated committees table body HTML.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

ob_start();
include __DIR__ . '/table.php';
$html = ob_get_clean();

jsonResponse(true, '', ['html' => $html]);
