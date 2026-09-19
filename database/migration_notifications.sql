-- Recipient-scoped notifications for the CMAS bell.
-- Run this after database/schema.sql on existing installations.

USE committee_management_db;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id    INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id  INT NOT NULL,
    activity_log_id    INT DEFAULT NULL,
    message            VARCHAR(500) NOT NULL,
    url                VARCHAR(500) DEFAULT NULL,
    read_at            DATETIME DEFAULT NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_notification_activity_recipient (activity_log_id, recipient_user_id),
    KEY idx_notifications_recipient (recipient_user_id, read_at, created_at),
    CONSTRAINT fk_notification_recipient FOREIGN KEY (recipient_user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notification_activity FOREIGN KEY (activity_log_id)
        REFERENCES activity_logs(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;