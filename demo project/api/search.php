<?php
// api/search.php — Global search (projects + experiments + threads)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode([]); exit; }

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }
$like = "%$q%";

$results = [];

// Projects
$stmt = $pdo->prepare("SELECT id, title, 'project' AS type FROM projects WHERE title LIKE ? AND status='approved' LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['label' => '🛸 ' . $r['title'], 'url' => BASE_URL . '/projects/view.php?id=' . $r['id']];
}

// Experiments
$stmt = $pdo->prepare("SELECT id, title, 'experiment' AS type FROM experiments WHERE title LIKE ? LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['label' => '🔬 ' . $r['title'], 'url' => BASE_URL . '/experiments/view.php?id=' . $r['id']];
}

// Forum threads
$stmt = $pdo->prepare("SELECT id, title FROM forum_threads WHERE title LIKE ? LIMIT 4");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = ['label' => '💬 ' . $r['title'], 'url' => BASE_URL . '/forum/thread.php?id=' . $r['id']];
}

// Users
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE name LIKE ? LIMIT 5");
$stmt->execute([$like]);
foreach ($stmt->fetchAll() as $r) {
    // If no dedicated user profile exists, we can link them to the leaderboard or a non-clickable action
    $results[] = ['label' => '👥 ' . $r['name'], 'url' => 'javascript:void(0)'];
}

echo json_encode($results);
