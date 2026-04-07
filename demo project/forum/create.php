<?php
// forum/create.php — Create a new thread
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId    = $_SESSION['user_id'];
$defaultCat = (int)($_GET['cat'] ?? 0);
$errors = [];
$vals   = ['title'=>'','content'=>'','category_id'=> $defaultCat];

$cats = $pdo->query('SELECT * FROM forum_categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['title']       = trim($_POST['title']       ?? '');
    $vals['content']     = trim($_POST['content']     ?? '');
    $vals['category_id'] = (int)($_POST['category_id'] ?? 0);

    if (strlen($vals['title']) < 5)   $errors['title']    = 'Title must be at least 5 characters.';
    if (strlen($vals['content']) < 10) $errors['content']  = 'Content is too short.';
    if (!$vals['category_id'])         $errors['category'] = 'Please select a category.';

    if (!$errors) {
        $stmt = $pdo->prepare('
            INSERT INTO forum_threads (category_id, user_id, title, content)
            VALUES (?,?,?,?)
        ');
        $stmt->execute([$vals['category_id'], $userId, $vals['title'], $vals['content']]);
        $tid = $pdo->lastInsertId();
        awardPoints($pdo, $userId, 15);
        header('Location: ' . BASE_URL . '/forum/thread.php?id=' . $tid); exit;
    }
}

$pageTitle  = 'New Thread';
$activePage = 'forum';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>💬 New Discussion Thread</h1>
    <p>Share your thoughts with the SpaceCollab community</p>
  </div>
  <a href="<?= BASE_URL ?>/forum/" class="btn btn-outline">← Forum</a>
</div>

<div style="max-width:700px">
<div class="card">
<form method="POST">
  <div class="form-group">
    <label class="form-label">Category *</label>
    <select name="category_id" class="form-control" required>
      <option value="">Select a topic category…</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $vals['category_id'] === $c['id'] ? 'selected' : '' ?>>
          <?= $c['icon'] ?> <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['category'])): ?><div class="form-error"><?= e($errors['category']) ?></div><?php endif; ?>
  </div>

  <div class="form-group">
    <label class="form-label">Thread Title *</label>
    <input type="text" name="title" class="form-control" required
           placeholder="e.g. What would happen if you fell into a black hole?"
           value="<?= e($vals['title']) ?>">
    <?php if (isset($errors['title'])): ?><div class="form-error"><?= e($errors['title']) ?></div><?php endif; ?>
  </div>

  <div class="form-group">
    <label class="form-label">Your Post *</label>
    <textarea name="content" class="form-control" rows="8" required
              placeholder="Share your thoughts, research, hypotheses, or questions. The more detail, the better the discussion!"><?= e($vals['content']) ?></textarea>
    <?php if (isset($errors['content'])): ?><div class="form-error"><?= e($errors['content']) ?></div><?php endif; ?>
  </div>

  <div class="flex gap-12 mt-8">
    <button type="submit" class="btn btn-primary btn-lg">🚀 Post Thread (+15 pts)</button>
    <a href="<?= BASE_URL ?>/forum/" class="btn btn-outline btn-lg">Cancel</a>
  </div>
</form>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
