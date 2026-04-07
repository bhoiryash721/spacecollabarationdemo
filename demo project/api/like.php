<?php
// api/like.php — Toggle like via AJAX
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required']); exit;
}

$userId     = $_SESSION['user_id'];
$entityType = $_POST['entity_type'] ?? '';
$entityId   = (int)($_POST['entity_id'] ?? 0);

$allowedTypes = ['experiment', 'forum_post'];
if (!in_array($entityType, $allowedTypes) || !$entityId) {
    echo json_encode(['error' => 'Invalid request']); exit;
}

// Check if already liked
$stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id=? AND entity_type=? AND entity_id=?');
$stmt->execute([$userId, $entityType, $entityId]);
$existing = $stmt->fetch();

if ($existing) {
    // Unlike
    $pdo->prepare('DELETE FROM likes WHERE user_id=? AND entity_type=? AND entity_id=?')
        ->execute([$userId, $entityType, $entityId]);
    $liked = false;
} else {
    // Like
    $pdo->prepare('INSERT INTO likes (user_id, entity_type, entity_id) VALUES (?,?,?)')
        ->execute([$userId, $entityType, $entityId]);
    $liked = true;
    // Award points to content creator
    if ($entityType === 'experiment') {
        $row = $pdo->prepare('SELECT user_id FROM experiments WHERE id=?');
        $row->execute([$entityId]);
        $creator = $row->fetchColumn();
        if ($creator && $creator !== $userId) awardPoints($pdo, $creator, 2);
    }
}

// Get new count
$stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE entity_type=? AND entity_id=?');
$stmt->execute([$entityType, $entityId]);
$count = (int)$stmt->fetchColumn();

echo json_encode(['liked' => $liked, 'count' => $count]);
