-- Committee group chat ("Messages" button beside the notification bell).
-- Run this after database/schema.sql on existing installations:
--   mysql -u root -p committee_management_db < database/migration_committee_messages.sql
--
-- Nothing existing is altered. Chat access is derived from the existing
-- committee_members table (status = 'Active'), so no user or committee
-- rows are duplicated.

USE committee_management_db;

-- One row per chat message. A message belongs to exactly one committee's
-- group conversation.
--
-- sender_user_id is nullable with ON DELETE SET NULL on purpose: the app
-- hard-deletes users (pages/ajax_user_delete.php). RESTRICT would make a
-- single chat message block that deletion forever, and CASCADE would erase
-- the committee's conversation history. SET NULL keeps the history and the
-- UI shows the author as "Former member".
CREATE TABLE IF NOT EXISTS committee_messages (
    message_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    committee_id    INT NOT NULL,
    sender_user_id  INT DEFAULT NULL,
    message_body    VARCHAR(2000) NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Serves "latest N", "older than X" and "newer than X" for one committee.
    KEY idx_committee_messages_feed (committee_id, message_id),
    -- Serves the per-user send-rate check.
    KEY idx_committee_messages_sender (sender_user_id, created_at),
    CONSTRAINT fk_committee_messages_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_committee_messages_sender FOREIGN KEY (sender_user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-user, per-committee "read up to" cursor. Drives the unread badge on
-- the Message button without storing one row per message per recipient.
CREATE TABLE IF NOT EXISTS committee_message_reads (
    committee_id          INT NOT NULL,
    user_id               INT NOT NULL,
    last_read_message_id  BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (committee_id, user_id),
    CONSTRAINT fk_message_reads_committee FOREIGN KEY (committee_id)
        REFERENCES committees(committee_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_message_reads_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
