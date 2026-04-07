<?php
// experiments/view.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$expId  = (int)($_GET['id'] ?? 0);
if (!$expId) { header('Location: ' . BASE_URL . '/experiments/'); exit; }

$stmt = $pdo->prepare('
    SELECT e.*, u.name AS author, u.id AS author_id
    FROM experiments e JOIN users u ON u.id = e.user_id
    WHERE e.id = ?
');
$stmt->execute([$expId]);
$exp = $stmt->fetch();
if (!$exp) { echo 'Experiment not found.'; exit; }

// Like status
$stmt = $pdo->prepare("SELECT id FROM likes WHERE entity_type='experiment' AND entity_id=? AND user_id=?");
$stmt->execute([$expId, $userId]);
$userLiked = (bool)$stmt->fetch();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE entity_type='experiment' AND entity_id=?");
$stmt->execute([$expId]); $likeCount = (int)$stmt->fetchColumn();

// Comments
$stmt = $pdo->prepare("
    SELECT c.content, c.created_at, u.name
    FROM comments c JOIN users u ON u.id = c.user_id
    WHERE c.entity_type='experiment' AND c.entity_id=?
    ORDER BY c.created_at DESC
");
$stmt->execute([$expId]);
$comments = $stmt->fetchAll();

// Update view / award points once
$stmt = $pdo->prepare("SELECT id FROM likes WHERE entity_type='experiment' AND entity_id=? AND user_id=?");
$stmt->execute([$expId, $userId]);

$pageTitle  = e($exp['title']);
$activePage = 'experiments';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($exp['title']) ?></h1>
    <div class="card-meta">
      by <strong style="color:var(--accent)"><?= e($exp['author']) ?></strong> ·
      <?= date('M j, Y', strtotime($exp['created_at'])) ?>
    </div>
  </div>
  <a href="<?= BASE_URL ?>/experiments/" class="btn btn-outline">← Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px" class="exp-layout">
<div>
  <!-- Media -->
  <?php if ($exp['media']): ?>
    <?php $isVideo = str_ends_with($exp['media'], '.mp4') || str_ends_with($exp['media'], '.webm'); ?>
    <?php if ($isVideo): ?>
      <video controls style="width:100%;border-radius:var(--radius-lg);margin-bottom:20px">
        <source src="<?= BASE_URL ?>/uploads/experiments/<?= e($exp['media']) ?>">
      </video>
    <?php else: ?>
      <img src="<?= BASE_URL ?>/uploads/experiments/<?= e($exp['media']) ?>"
           alt="Experiment media" style="width:100%;border-radius:var(--radius-lg);margin-bottom:20px;max-height:400px;object-fit:cover">
    <?php endif; ?>
  <?php endif; ?>

  <!-- Description -->
  <div class="card mb-16">
    <h3 class="mb-16">About this Experiment</h3>
    <p><?= nl2br(e($exp['description'])) ?></p>

    <?php if ($exp['steps']): ?>
    <div class="glow-divider"></div>
    <h3 class="mb-16">Steps & Procedure</h3>
    <p style="white-space:pre-line;font-size:.9rem"><?= e($exp['steps']) ?></p>
    <?php endif; ?>

    <?php if ($exp['tags']): ?>
    <div class="glow-divider"></div>
    <div class="tags-wrap">
      <?php foreach (explode(',', $exp['tags']) as $t): ?>
        <span class="badge badge-purple"><?= e(trim($t)) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Comments -->
  <div class="card">
    <h3 class="mb-16">Comments (<?= count($comments) ?>)</h3>
    <div id="comment-list-<?= $expId ?>">
      <?php foreach ($comments as $c): ?>
      <div class="comment-item">
        <div class="avatar"><?= strtoupper(substr($c['name'],0,1)) ?></div>
        <div class="comment-body">
          <span class="comment-author"><?= e($c['name']) ?></span>
          <span class="comment-time"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
          <p class="comment-text"><?= nl2br(e($c['content'])) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$comments): ?><p class="text-muted">Be the first to comment!</p><?php endif; ?>
    </div>
    <form class="comment-form mt-16" data-type="experiment" data-id="<?= $expId ?>">
      <textarea class="form-control" rows="3" placeholder="Share your thoughts or questions…" required></textarea>
      <button type="submit" class="btn btn-primary btn-sm mt-8">Post Comment (+5 pts)</button>
    </form>
  </div>
</div>

<!-- Right panel -->
<div>
  <div class="card mb-16" style="text-align:center;padding:28px">
    <div style="font-size:2rem;margin-bottom:8px">❤️</div>
    <div style="font-family:var(--font-display);font-size:2rem;font-weight:800"><?= $likeCount ?></div>
    <div class="text-muted mb-16">likes</div>
    <button class="like-btn <?= $userLiked ? 'liked' : '' ?> w-full" style="justify-content:center"
            data-type="experiment" data-id="<?= $expId ?>">
      <?= $userLiked ? '♥ Unlike' : '♡ Like this Experiment' ?>
    </button>
  </div>

  <div class="card">
    <h3 class="mb-16">About the Author</h3>
    <div class="flex gap-12 flex-center">
      <div class="avatar avatar-lg"><?= strtoupper(substr($exp['author'],0,1)) ?></div>
      <div>
        <div style="font-weight:600"><?= e($exp['author']) ?></div>
        <div class="text-muted">Space Researcher</div>
      </div>
    </div>
    <div class="glow-divider"></div>
    <a href="<?= BASE_URL ?>/experiments/?author=<?= $exp['author_id'] ?>" class="btn btn-outline w-full" style="justify-content:center">
      View All Experiments
    </a>
  </div>
</div>
</div>

<style>@media(max-width:900px){.exp-layout{grid-template-columns:1fr!important}}</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
