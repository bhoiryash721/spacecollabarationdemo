<?php
// api/notifications.php — Fetch / mark notifications
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required']); exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$userId]);
    echo json_encode(['success' => true]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'count') {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id=? AND is_read = 0');
    $stmt->execute([$userId]);
    echo json_encode(['count' => (int) $stmt->fetchColumn()]);
    exit;
}

// GET — return recent notifications
$stmt = $pdo->prepare('
    SELECT message, link, is_read,
           DATE_FORMAT(created_at, "%M %e, %Y") AS created_at
    FROM notifications WHERE user_id=?
    ORDER BY created_at DESC LIMIT 15
');
$stmt->execute([$userId]);
echo json_encode($stmt->fetchAll());
