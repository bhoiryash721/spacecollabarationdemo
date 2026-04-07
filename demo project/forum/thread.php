<?php
// forum/thread.php — Thread detail with AJAX replies
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId   = $_SESSION['user_id'];
$threadId = (int)($_GET['id'] ?? 0);
if (!$threadId) { header('Location: ' . BASE_URL . '/forum/'); exit; }

// Increment view count
$pdo->prepare('UPDATE forum_threads SET views = views + 1 WHERE id=?')->execute([$threadId]);

$stmt = $pdo->prepare('
    SELECT ft.*, fc.name AS cat_name, fc.id AS cat_id, u.name AS author
    FROM forum_threads ft
    JOIN forum_categories fc ON fc.id = ft.category_id
    JOIN users u ON u.id = ft.user_id
    WHERE ft.id = ?
');
$stmt->execute([$threadId]);
$thread = $stmt->fetch();
if (!$thread) { echo 'Thread not found.'; exit; }

$stmt = $pdo->prepare('
    SELECT fr.*, u.name AS author, u.points
    FROM forum_replies fr JOIN users u ON u.id = fr.user_id
    WHERE fr.thread_id = ? ORDER BY fr.created_at ASC
');
$stmt->execute([$threadId]);
$replies = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT id, title, views,
           (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
    FROM forum_threads ft
    WHERE ft.category_id = ? AND ft.id != ?
    ORDER BY ft.created_at DESC LIMIT 4
');
$stmt->execute([$thread['category_id'], $threadId]);
$relatedThreads = $stmt->fetchAll();

$pageTitle  = e($thread['title']);
$activePage = 'forum';
include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div style="font-size:.82rem;color:var(--text-muted);margin-bottom:16px">
  <a href="<?= BASE_URL ?>/forum/">Forum</a> ›
  <a href="<?= BASE_URL ?>/forum/category.php?id=<?= $thread['cat_id'] ?>"><?= e($thread['cat_name']) ?></a> ›
  <span style="color:var(--text-secondary)"><?= e(substr($thread['title'],0,40)) ?>…</span>
</div>

<div class="page-header">
  <div>
    <h1 style="font-size:1.4rem"><?= e($thread['title']) ?></h1>
    <div class="card-meta">
      by <strong><?= e($thread['author']) ?></strong> ·
      <?= date('M j, Y \a\t g:i a', strtotime($thread['created_at'])) ?> ·
      <?= $thread['views'] ?> views
    </div>
  </div>
  <a href="<?= BASE_URL ?>/forum/category.php?id=<?= $thread['cat_id'] ?>" class="btn btn-outline">← Back</a>
</div>

<!-- Original Post -->
<div class="card mb-16">
  <div class="flex gap-16" style="align-items:flex-start">
    <div style="text-align:center;min-width:64px">
      <div class="avatar avatar-lg" style="margin:0 auto 8px"><?= strtoupper(substr($thread['author'],0,1)) ?></div>
      <div style="font-size:.72rem;color:var(--text-muted)"><?= e($thread['author']) ?></div>
    </div>
    <div style="flex:1">
      <div style="padding:16px;background:rgba(79,195,247,0.04);border:1px solid var(--border);border-radius:var(--radius);border-left:3px solid var(--accent)">
        <p style="white-space:pre-line;line-height:1.8"><?= nl2br(e($thread['content'])) ?></p>
      </div>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px">
        Posted <?= date('M j, Y', strtotime($thread['created_at'])) ?>
      </div>
    </div>
  </div>
</div>

<!-- Replies -->
<h3 class="mb-16" style="color:var(--text-secondary)">
  <?= count($replies) ?> <?= count($replies) === 1 ? 'Reply' : 'Replies' ?>
</h3>

<div id="replies-list">
  <?php foreach ($replies as $r): ?>
  <div class="comment-item card mb-12" style="padding:16px">
    <div class="avatar"><?= strtoupper(substr($r['author'],0,1)) ?></div>
    <div class="comment-body" style="flex:1">
      <span class="comment-author"><?= e($r['author']) ?></span>
      <span class="badge badge-gold" style="margin-left:8px"><?= number_format($r['points']) ?> pts</span>
      <span class="comment-time"><?= date('M j, Y \a\t g:i a', strtotime($r['created_at'])) ?></span>
      <p class="comment-text" style="margin-top:8px;white-space:pre-line"><?= nl2br(e($r['content'])) ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Reply form (AJAX) -->
<div class="card mt-24">
  <h3 class="mb-16">Post a Reply</h3>
  <form class="reply-form" data-thread="<?= $threadId ?>">
    <div class="form-group">
      <textarea class="form-control" rows="5"
                placeholder="Share your thoughts, research, or insights on this topic…" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">📡 Post Reply (+10 pts)</button>
  </form>
</div>

<?php if ($relatedThreads): ?>
<div class="card mt-24">
  <h3 class="mb-16">More in <?= e($thread['cat_name']) ?></h3>
  <?php foreach ($relatedThreads as $rt): ?>
    <div style="padding:12px 0;border-bottom:1px solid var(--border)">
      <a href="<?= BASE_URL ?>/forum/thread.php?id=<?= $rt['id'] ?>" style="font-weight:600;color:var(--text-primary)"><?= e($rt['title']) ?></a>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
        <?= $rt['reply_count'] ?> replies · <?= $rt['views'] ?> views
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
