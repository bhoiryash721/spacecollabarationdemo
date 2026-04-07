<?php
// api/comment.php — Post comment via AJAX
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required']); exit;
}

$userId     = $_SESSION['user_id'];
$entityType = $_POST['entity_type'] ?? '';
$entityId   = (int)($_POST['entity_id'] ?? 0);
$content    = trim($_POST['content'] ?? '');

if (!in_array($entityType, ['project','experiment','forum']) || !$entityId || !$content) {
    echo json_encode(['error' => 'Invalid request']); exit;
}
if (strlen($content) > 2000) {
    echo json_encode(['error' => 'Comment too long (max 2000 chars)']); exit;
}

$stmt = $pdo->prepare('INSERT INTO comments (user_id, entity_type, entity_id, content) VALUES (?,?,?,?)');
$stmt->execute([$userId, $entityType, $entityId, $content]);

// Award points
awardPoints($pdo, $userId, 5);

// Notify content owner
if ($entityType === 'experiment') {
    $row = $pdo->prepare('SELECT user_id, title FROM experiments WHERE id=?');
    $row->execute([$entityId]);
    $exp = $row->fetch();
    if ($exp && $exp['user_id'] !== $userId) {
        notify($pdo, $exp['user_id'],
               $_SESSION['user_name'] . ' commented on your experiment "' . $exp['title'] . '"',
               BASE_URL . '/experiments/view.php?id=' . $entityId);
    }
}

$initials = strtoupper(substr($_SESSION['user_name'], 0, 1));
echo json_encode([
    'success'  => true,
    'name'     => $_SESSION['user_name'],
    'initials' => $initials,
]);
