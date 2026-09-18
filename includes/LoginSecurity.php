<?php
/**
 * includes/LoginSecurity.php
 * ------------------------------------------------------------------
 * Server-side account lockout after repeated failed login attempts.
 * Tracked per email in `login_security`, independent of JavaScript
 * or client storage of any kind.
 * ------------------------------------------------------------------
 */

class LoginSecurity
{
    private PDO $pdo;
    public const MAX_ATTEMPTS = 3;
    public const LOCK_SECONDS = 5 * 60;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getRow(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM login_security WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Seconds remaining on an active lock, or 0 if not locked.
     */
    public function lockSecondsRemaining(string $email): int
    {
        $row = $this->getRow($email);
        if (!$row || !$row['locked_until']) return 0;
        $remaining = strtotime($row['locked_until']) - time();
        return max(0, $remaining);
    }

    public function isLocked(string $email): bool
    {
        return $this->lockSecondsRemaining($email) > 0;
    }

    /**
     * Records a failed attempt. Locks the account once the 3rd
     * consecutive failure is reached, then resets the counter so the
     * next window starts clean once the lock expires.
     */
    public function recordFailure(string $email): void
    {
        $row = $this->getRow($email);
        $attempts = $row ? (int)$row['failed_attempts'] + 1 : 1;
        $now = date('Y-m-d H:i:s');

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCK_SECONDS);
            $stmt = $this->pdo->prepare(
                'INSERT INTO login_security (email, failed_attempts, locked_until, last_attempt_at)
                 VALUES (:email, 0, :locked, :now)
                 ON DUPLICATE KEY UPDATE failed_attempts = 0, locked_until = :locked2, last_attempt_at = :now2'
            );
            $stmt->execute([':email' => $email, ':locked' => $lockedUntil, ':locked2' => $lockedUntil, ':now' => $now, ':now2' => $now]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO login_security (email, failed_attempts, locked_until, last_attempt_at)
                 VALUES (:email, :attempts, NULL, :now)
                 ON DUPLICATE KEY UPDATE failed_attempts = :attempts2, last_attempt_at = :now2'
            );
            $stmt->execute([':email' => $email, ':attempts' => $attempts, ':attempts2' => $attempts, ':now' => $now, ':now2' => $now]);
        }
    }

    /**
     * Resets the counter on successful authentication.
     */
    public function recordSuccess(string $email): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_security (email, failed_attempts, locked_until, last_attempt_at)
             VALUES (:email, 0, NULL, :now)
             ON DUPLICATE KEY UPDATE failed_attempts = 0, locked_until = NULL, last_attempt_at = :now2'
        );
        $stmt->execute([':email' => $email, ':now' => $now, ':now2' => $now]);
    }

    public function attemptsRemaining(string $email): int
    {
        $row = $this->getRow($email);
        $used = $row ? (int)$row['failed_attempts'] : 0;
        return max(0, self::MAX_ATTEMPTS - $used);
    }
}
