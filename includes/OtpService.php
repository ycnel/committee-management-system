<?php
/**
 * includes/OtpService.php
 * ------------------------------------------------------------------
 * OTP generation and verification for the login flow.
 *
 * DELIVERY: this class only generates, hashes, stores, and verifies
 * OTPs — it does not send anything. Actual delivery is controlled by
 * OTP_DELIVERY_MODE in config/config.php:
 *   - 'demo'  (default): the OTP is shown directly on the verification
 *              page in a clearly labeled "Demo Mode" banner, so the
 *              login flow is fully testable without a real SMS/email
 *              provider connected. This is intentional, not a bug —
 *              wiring up a real provider without one being configured
 *              would mean silently failing to deliver OTPs at all.
 *   - 'email' / 'sms': NOT IMPLEMENTED. These are reserved values for
 *              future integration (e.g. PHPMailer/SMTP, a Semaphore/
 *              Twilio SMS gateway) and are intentionally left as a
 *              documented TODO rather than a fake/simulated send.
 * ------------------------------------------------------------------
 */

class OtpService
{
    private PDO $pdo;
    public const VALIDITY_SECONDS = 60 * 5;
    public const MAX_ATTEMPTS = 5;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Send an OTP without logging or exposing the plaintext code. */
    public function sendEmail(string $recipientEmail, string $recipientName, string $otp): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = OTP_SMTP_HOST;
        $mailer->Port = OTP_SMTP_PORT;
        $mailer->SMTPAuth = true;
        $mailer->Username = OTP_SMTP_USERNAME;
        $mailer->Password = OTP_SMTP_PASSWORD;
        $mailer->SMTPSecure = OTP_SMTP_ENCRYPTION === 'tls'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom(OTP_FROM_EMAIL, OTP_FROM_NAME);
        $mailer->addAddress($recipientEmail, $recipientName);
        $mailer->isHTML(true);
        $mailer->Subject = APP_NAME . ' verification code';
        $mailer->Body = '<p>Hello ' . e($recipientName) . ',</p>'
            . '<p>Your verification code is:</p>'
            . '<p style="font-size:24px;font-weight:bold;letter-spacing:6px;">' . e($otp) . '</p>'
            . '<p>This code expires in ' . self::formatDuration(self::VALIDITY_SECONDS) . '. If you did not request it, you can ignore this email.</p>';
        $mailer->AltBody = 'Your ' . APP_NAME . ' verification code is ' . $otp
            . '. It expires in ' . self::formatDuration(self::VALIDITY_SECONDS) . '.';
        $mailer->send();
    }

    private static function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        if ($remainingSeconds === 0) {
            return $minutes . ($minutes === 1 ? ' minute' : ' minutes');
        }
        return $minutes . ':' . str_pad((string)$remainingSeconds, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Invalidates any prior unused OTP for this user and issues a new
     * one. Returns the plaintext code (needed by the caller to hand to
     * whatever delivery mechanism is configured) and its expiry.
     */
    public function generate(int $userId): array
    {
        $this->pdo->prepare('UPDATE otp_verifications SET is_used = 1 WHERE user_id = :uid AND is_used = 0')
            ->execute([':uid' => $userId]);

        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::VALIDITY_SECONDS);

        $stmt = $this->pdo->prepare(
            'INSERT INTO otp_verifications (user_id, otp_hash, expires_at, is_used, attempts, created_at)
             VALUES (:uid, :hash, :exp, 0, 0, NOW())'
        );
        $stmt->execute([':uid' => $userId, ':hash' => $hash, ':exp' => $expiresAt]);

        return ['otp' => $otp, 'expires_at' => $expiresAt, 'expires_in' => self::VALIDITY_SECONDS];
    }

    private function latestActive(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM otp_verifications WHERE user_id = :uid AND is_used = 0 ORDER BY otp_id DESC LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * True once the most recently issued OTP has expired (or none
     * exists yet) — i.e. it is safe to issue a new one. Enforced here,
     * server-side, not just by a disabled attribute in the browser.
     */
    public function canResend(int $userId): bool
    {
        $active = $this->latestActive($userId);
        if (!$active) return true;
        return strtotime($active['expires_at']) <= time();
    }

    public function secondsRemaining(int $userId): int
    {
        $active = $this->latestActive($userId);
        if (!$active) return 0;
        $remaining = strtotime($active['expires_at']) - time();
        return max(0, $remaining);
    }

    /**
     * Verifies a submitted code against the latest active OTP for the
     * user. Returns ['success' => bool, 'message' => string].
     */
    public function verify(int $userId, string $inputOtp): array
    {
        $active = $this->latestActive($userId);

        if (!$active) {
            return ['success' => false, 'message' => 'No active verification code. Please request a new one.'];
        }
        if (strtotime($active['expires_at']) < time()) {
            return ['success' => false, 'message' => 'This code has expired. Please request a new one.'];
        }
        if ((int)$active['attempts'] >= self::MAX_ATTEMPTS) {
            $this->pdo->prepare('UPDATE otp_verifications SET is_used = 1 WHERE otp_id = :id')
                ->execute([':id' => $active['otp_id']]);
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        $this->pdo->prepare('UPDATE otp_verifications SET attempts = attempts + 1 WHERE otp_id = :id')
            ->execute([':id' => $active['otp_id']]);

        if (!password_verify($inputOtp, $active['otp_hash'])) {
            return ['success' => false, 'message' => 'Incorrect code. Please try again.'];
        }

        $this->pdo->prepare('UPDATE otp_verifications SET is_used = 1 WHERE otp_id = :id')
            ->execute([':id' => $active['otp_id']]);

        return ['success' => true, 'message' => 'Verified.'];
    }
}
