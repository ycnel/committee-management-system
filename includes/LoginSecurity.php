<?php
/**
 * includes/LoginSecurity.php
 * ------------------------------------------------------------------
 * Server-side account lockout after repeated failed login attempts.
 * Tracked per email in `login_security`, independent of JavaScript
 * or client storage of any kind.
 *
 * Escalating schedule (consecutive failures -> lock duration):
 *   attempts 1-3 : no lock (warning only)
 *   attempt 4    : 3 minutes
 *   attempt 5    : 5 minutes
 *   attempt 6    : 10 minutes
 *   attempt 7+   : 24 hours
 * The counter is NOT reset when a lock expires, so each subsequent
 * failure escalates. A successful login resets it to zero.
 * ------------------------------------------------------------------
 */

class LoginSecurity
{
    private PDO $pdo;
    public const FREE_ATTEMPTS = 3;                 // failures before the first lock
    private const LOCK_SCHEDULE = [                 // failure count => lock duration
        4 => 3 * 60,
        5 => 5 * 60,
        6 => 10 * 60,
    ];
    private const MAX_LOCK_SECONDS = 86400;         // attempt 7+ => 24 hours

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
     * Records a failed attempt. Applies the escalating lock duration for
     * the current consecutive-failure count (see header schedule). The
     * counter is never reset here — it survives lock expiry so repeat
     * failures keep escalating; only recordSuccess() clears it.
     */
    public function recordFailure(string $email): void
    {
        $row = $this->getRow($email);
        $attempts = $row ? (int)$row['failed_attempts'] + 1 : 1;
        $now = date('Y-m-d H:i:s');

        $lockSeconds = self::LOCK_SCHEDULE[$attempts] ?? ($attempts > self::FREE_ATTEMPTS ? self::MAX_LOCK_SECONDS : 0);
        $lockedUntil = $lockSeconds > 0 ? date('Y-m-d H:i:s', time() + $lockSeconds) : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO login_security (email, failed_attempts, locked_until, last_attempt_at)
             VALUES (:email, :attempts, :locked, :now)
             ON DUPLICATE KEY UPDATE failed_attempts = :attempts2, locked_until = :locked2, last_attempt_at = :now2'
        );
        $stmt->execute([
            ':email' => $email, ':attempts' => $attempts, ':locked' => $lockedUntil, ':now' => $now,
            ':attempts2' => $attempts, ':locked2' => $lockedUntil, ':now2' => $now,
        ]);
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

    /**
     * Failures left before the next lock. Only meaningful while the
     * counter is still in the "free" window (first FREE_ATTEMPTS).
     */
    public function attemptsRemaining(string $email): int
    {
        $row = $this->getRow($email);
        $used = $row ? (int)$row['failed_attempts'] : 0;
        return max(0, self::FREE_ATTEMPTS + 1 - $used);
    }

    /** Human label for a lock duration, e.g. "3 minutes" / "24 hours". */
    public static function durationLabel(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int)round($seconds / 3600);
            return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        $minutes = max(1, (int)ceil($seconds / 60));
        return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
    }
}
