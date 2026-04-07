<?php
// projects/join.php — POST handler to join a project
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId    = $_SESSION['user_id'];
$projectId = (int) ($_POST['project_id'] ?? 0);
if (!$projectId) { header('Location: ' . BASE_URL . '/projects/'); exit; }

// Check project exists and is approved
$stmt = $pdo->prepare('SELECT id, creator_id, title FROM projects WHERE id=? AND status="approved"');
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) { header('Location: ' . BASE_URL . '/projects/'); exit; }

// Insert membership (ignore duplicate)
$stmt = $pdo->prepare('INSERT IGNORE INTO project_members (project_id, user_id) VALUES (?,?)');
$stmt->execute([$projectId, $userId]);

if ($stmt->rowCount()) {
    // Award points
    awardPoints($pdo, $userId, 10);
    // Notify project creator
    if ($project['creator_id'] !== $userId) {
        notify($pdo, $project['creator_id'],
               $_SESSION['user_name'] . ' joined your project "' . $project['title'] . '"',
               BASE_URL . '/projects/view.php?id=' . $projectId);
    }
}

header('Location: ' . BASE_URL . '/projects/view.php?id=' . $projectId);
exit;
