<?php
/**
 * layouts/header.php
 * ------------------------------------------------------------------
 * Shared <head> only. The top navigation bar has been removed in
 * favor of a sidebar + content layout; branding now lives in
 * sidebar.php and the account/notifications controls now live in
 * the page's own content-topbar (see dashboard.php).
 *
 * This file still computes $user and the notification list, since
 * that data/logic is reused by whichever page renders the
 * content-topbar. Expects $pageTitle to optionally be set by the
 * including page.
 *
 * Design-system note: the Linear-inspired light-mode theme lives
 * canonically in assets/css/style.css now (not here) — style.css
 * already zeroes --gov-topnav-height and sizes .main-content
 * correctly for the header-less layout, so no override CSS is
 * needed in this file anymore.
 * ------------------------------------------------------------------
 */

$pageTitle = $pageTitle ?? 'Dashboard';
$user = currentUser();

/** Build the bell list from notifications addressed to this user. */
$notifItems = [];
$notifCount = 0;
try {
    $pdo = db();
    $notificationStmt = $pdo->prepare(
        'SELECT notification_id, message, url, read_at
         FROM notifications
         WHERE recipient_user_id = :user_id
         ORDER BY created_at DESC, notification_id DESC
         LIMIT 20'
    );
    $notificationStmt->execute([':user_id' => (int)currentUserId()]);
    $notifItems = $notificationStmt->fetchAll();

    $unreadStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications
         WHERE recipient_user_id = :user_id AND read_at IS NULL'
    );
    $unreadStmt->execute([':user_id' => (int)currentUserId()]);
    $notifCount = (int)$unreadStmt->fetchColumn();
} catch (Throwable $e) {
    error_log('Notification bell error: ' . $e->getMessage());
    $notifItems = [];
    $notifCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="Committee Management System">
<meta property="og:image" content="<?= e(APP_URL) ?>/assets/img/manila-city.jpg">
<meta property="og:url" content="<?= e(APP_URL) ?>">
<meta property="og:type" content="website">
<title><?= e($pageTitle) ?></title>

<!-- e($pageTitle) ?> | = e(APP_SHORT_NAME)-->

<link href="<?= e(vendorAsset('bootstrap/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(vendorAsset('bootstrap-icons/bootstrap-icons.css', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css')) ?>" rel="stylesheet">
<link href="<?= e(vendorAsset('datatables/dataTables.bootstrap5.min.css', 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css')) ?>" rel="stylesheet">
<link rel="icon" type="image/png" href="<?= e(APP_URL) ?>/assets/img/city_manila_seal.png">
<link href="<?= e(APP_URL) ?>/assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
<?php if (!empty($extraCss)) foreach ($extraCss as $css): ?>
<link href="<?= e($css) ?>" rel="stylesheet">
<?php endforeach; ?>
</head>
<body>