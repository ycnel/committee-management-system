<?php

require_once __DIR__ . '/../config/database.php';

function lockJurisdictionForRemoval(PDO $pdo, int $jurisdictionId): array
{
    $stmt = $pdo->prepare(
        'SELECT jurisdiction_name FROM jurisdictions WHERE jurisdiction_id = :id FOR UPDATE'
    );
    $stmt->execute([':id' => $jurisdictionId]);
    $jurisdiction = $stmt->fetch();
    if (!$jurisdiction) {
        throw new RuntimeException('Jurisdiction not found.');
    }

    $assigned = $pdo->prepare('SELECT COUNT(*) FROM committees WHERE jurisdiction_id = :id');
    $assigned->execute([':id' => $jurisdictionId]);
    if ((int)$assigned->fetchColumn() > 0) {
        throw new RuntimeException(
            'This jurisdiction is still assigned to one or more committees. Reassign those committees first.'
        );
    }

    return $jurisdiction;
}

function insertJurisdictionRemovalNotification(
    PDO $pdo,
    int $recipientUserId,
    string $message,
    string $url,
    ?int $activityLogId
): void {
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO notifications
            (recipient_user_id, activity_log_id, message, url, created_at)
         VALUES (:recipient_user_id, :activity_log_id, :message, :url, NOW())'
    );
    $stmt->execute([
        ':recipient_user_id' => $recipientUserId,
        ':activity_log_id' => $activityLogId,
        ':message' => $message,
        ':url' => $url,
    ]);
}
