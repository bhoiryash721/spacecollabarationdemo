<?php
// ============================================================
// includes/otp.php — One-time password login helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function createLoginOtp(PDO $pdo, int $userId): string {
    clearUserLoginOtps($pdo, $userId);

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO login_otps (user_id, otp_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    );
    $stmt->execute([$userId, $hash]);

    return $code;
}

function clearUserLoginOtps(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('DELETE FROM login_otps WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function cleanupExpiredLoginOtps(PDO $pdo): void {
    $pdo->exec('DELETE FROM login_otps WHERE expires_at < NOW()');
}

function fetchLatestLoginOtp(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare(
        'SELECT id, otp_hash, expires_at FROM login_otps WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $otp = $stmt->fetch();

    return $otp ?: null;
}

function isLocalhost(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'localhost' || $host === '127.0.0.1' || str_contains($host, 'localhost');
}

function sendLoginOtpEmail(string $to, string $name, string $code): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $from = 'SpaceCollab <no-reply@' . $host . '>';
    $subject = 'Your SpaceCollab login code';

    $body = <<<TEXT
Hello {$name},

Use the code below to finish signing in to SpaceCollab:

{$code}

This code expires in 10 minutes.
If you did not request this, you can safely ignore this message.

Happy collaborating,
The SpaceCollab team
TEXT;

    $headers = [
        'From: ' . $from,
        'Reply-To: no-reply@' . $host,
        'X-Mailer: PHP/' . phpversion(),
    ];

    return mail($to, $subject, $body, implode("\r\n", $headers));
}
