<?php
// ============================================================
// experiments/index.php — Browse experiments
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');
$where  = [];
$params = [];
if ($search) {
    $where[] = '(e.title LIKE ? OR e.description LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT e.*, u.name AS author,
           (SELECT COUNT(*) FROM likes WHERE entity_type='experiment' AND entity_id=e.id) AS like_count,
           EXISTS(SELECT 1 FROM likes WHERE entity_type='experiment' AND entity_id=e.id AND user_id=?) AS user_liked,
           (SELECT COUNT(*) FROM comments WHERE entity_type='experiment' AND entity_id=e.id) AS comment_count
    FROM experiments e
    JOIN users u ON u.id = e.user_id
    $whereSQL
    ORDER BY e.created_at DESC
");
$stmt->execute(array_merge([$userId], $params));
$exps = $stmt->fetchAll();

$pageTitle  = 'Experiments';
$activePage = 'experiments';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🔬 Experiments</h1>
    <p>Share your space science experiments and discoveries</p>
  </div>
  <a href="<?= BASE_URL ?>/experiments/create.php" class="btn btn-primary">+ Share Experiment</a>
</div>

<div class="search-bar">
  <input type="text" class="form-control" placeholder="Search experiments…"
         value="<?= e($search) ?>"
         onchange="window.location='?q='+encodeURIComponent(this.value)">
</div>

<?php if ($exps): ?>
<div class="card-grid">
  <?php foreach ($exps as $exp): ?>
  <div class="card">
    <?php if ($exp['media']): ?>
      <img src="<?= BASE_URL ?>/uploads/experiments/<?= e($exp['media']) ?>"
           alt="Experiment" style="width:100%;height:160px;object-fit:cover;border-radius:var(--radius);margin-bottom:14px">
    <?php else: ?>
      <div style="height:80px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;
                  background:rgba(79,195,247,0.06);border-radius:var(--radius);margin-bottom:14px">🔬</div>
    <?php endif; ?>

    <h3 style="margin-bottom:6px">
      <a href="<?= BASE_URL ?>/experiments/view.php?id=<?= $exp['id'] ?>" style="color:var(--text-primary)">
        <?= e($exp['title']) ?>
      </a>
    </h3>
    <div class="card-meta">by <?= e($exp['author']) ?> · <?= date('M j, Y', strtotime($exp['created_at'])) ?></div>

    <p style="font-size:.84rem;color:var(--text-secondary);margin:10px 0;
              -webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
      <?= e($exp['description']) ?>
    </p>

    <?php if ($exp['tags']): ?>
    <div class="tags-wrap" style="margin-bottom:12px">
      <?php foreach (array_slice(explode(',', $exp['tags']), 0, 3) as $t): ?>
        <span class="badge badge-purple"><?= e(trim($t)) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="flex gap-8 mt-8">
      <button class="like-btn <?= $exp['user_liked'] ? 'liked' : '' ?>"
              data-type="experiment" data-id="<?= $exp['id'] ?>">
        ❤ <span class="like-count"><?= $exp['like_count'] ?></span>
      </button>
      <a href="<?= BASE_URL ?>/experiments/view.php?id=<?= $exp['id'] ?>" class="btn btn-sm btn-outline">
        💬 <?= $exp['comment_count'] ?> · View
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state card">
  <div class="empty-icon">🔬</div>
  <h3>No experiments yet</h3>
  <p>Share your first space experiment!</p>
  <a href="<?= BASE_URL ?>/experiments/create.php" class="btn btn-primary mt-16">Share Experiment</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
