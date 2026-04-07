<?php
// ============================================================
// includes/password_reset.php — Password reset helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/otp.php';

function createPasswordResetToken(PDO $pdo, int $userId): string {
    clearPasswordResetTokens($pdo, $userId);

    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))'
    );
    $stmt->execute([$userId, $hash]);

    return $token;
}

function clearPasswordResetTokens(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);
}

function cleanupExpiredPasswordResetTokens(PDO $pdo): void {
    $pdo->exec('DELETE FROM password_reset_tokens WHERE expires_at < NOW()');
}

function fetchPasswordResetToken(PDO $pdo, string $token): ?array {
    $stmt = $pdo->query('SELECT id, user_id, token_hash, expires_at, created_at FROM password_reset_tokens WHERE expires_at >= NOW() ORDER BY created_at DESC');
    while ($row = $stmt->fetch()) {
        if (password_verify($token, $row['token_hash'])) {
            return $row;
        }
    }
    return null;
}

function sendPasswordResetEmail(string $to, string $name, string $token): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $resetUrl = $scheme . '://' . $host . BASE_URL . '/password_reset.php?token=' . urlencode($token);
    $from = 'SpaceCollab <no-reply@' . $host . '>';
    $subject = 'Reset your SpaceCollab password';

    $body = <<<TEXT
Hello {$name},

We received a request to reset your SpaceCollab password.

Open the link below to choose a new password:

{$resetUrl}

This link expires in 60 minutes.
If you did not request a reset, you can safely ignore this message.

Thanks,
The SpaceCollab team
TEXT;

    $headers = [
        'From: ' . $from,
        'Reply-To: no-reply@' . $host,
        'X-Mailer: PHP/' . phpversion(),
    ];

    return mail($to, $subject, $body, implode("\r\n", $headers));
}
