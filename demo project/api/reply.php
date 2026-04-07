<?php
// api/reply.php — Post forum reply via AJAX
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Login required']); exit;
}

$userId   = $_SESSION['user_id'];
$threadId = (int)($_POST['thread_id'] ?? 0);
$content  = trim($_POST['content'] ?? '');

if (!$threadId || !$content) {
    echo json_encode(['error' => 'Invalid request']); exit;
}
if (strlen($content) > 5000) {
    echo json_encode(['error' => 'Reply too long']); exit;
}

// Verify thread exists
$stmt = $pdo->prepare('SELECT id, user_id, title FROM forum_threads WHERE id=?');
$stmt->execute([$threadId]);
$thread = $stmt->fetch();
if (!$thread) { echo json_encode(['error' => 'Thread not found']); exit; }

$stmt = $pdo->prepare('INSERT INTO forum_replies (thread_id, user_id, content) VALUES (?,?,?)');
$stmt->execute([$threadId, $userId, $content]);

// Award points
awardPoints($pdo, $userId, 10);

// Notify thread creator
if ($thread['user_id'] !== $userId) {
    notify($pdo, $thread['user_id'],
           $_SESSION['user_name'] . ' replied to your thread "' . $thread['title'] . '"',
           BASE_URL . '/forum/thread.php?id=' . $threadId);
}

echo json_encode([
    'success'  => true,
    'name'     => $_SESSION['user_name'],
    'initials' => strtoupper(substr($_SESSION['user_name'], 0, 1)),
]);
