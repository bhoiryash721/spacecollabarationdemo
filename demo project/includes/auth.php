<?php
// ============================================================
// includes/auth.php — Session helpers & access control
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Require the user to be logged in ──────────────────────────
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// ── Require admin role ────────────────────────────────────────
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// ── Check if the current visitor is logged in ─────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// ── Tiny XSS escape helper ────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Award points to a user ────────────────────────────────────
function awardPoints(PDO $pdo, int $userId, int $points): void {
    $stmt = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
    $stmt->execute([$points, $userId]);
}

// ── Create a notification ─────────────────────────────────────
function notify(PDO $pdo, int $userId, string $message, string $link = ''): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $message, $link]);
}

// ── Count unread notifications for current user ───────────────
function unreadNotifCount(PDO $pdo): int {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}
