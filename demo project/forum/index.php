<?php
// forum/index.php — Forum categories overview
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$searchQuery = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'recent';
$validSorts = ['recent', 'replies', 'views'];
if (!in_array($sort, $validSorts, true)) {
    $sort = 'recent';
}
$pageSize = 8;
$offset = ($page - 1) * $pageSize;

$orderSql = 'ft.is_pinned DESC, ';
switch ($sort) {
    case 'replies':
        $orderSql .= 'reply_count DESC, ft.created_at DESC';
        break;
    case 'views':
        $orderSql .= 'ft.views DESC, ft.created_at DESC';
        break;
    default:
        $orderSql .= 'ft.created_at DESC';
}

$stmt = $pdo->query('
    SELECT fc.*,
           (SELECT COUNT(*) FROM forum_threads WHERE category_id = fc.id) AS thread_count,
           (SELECT COUNT(*) FROM forum_replies fr
            JOIN forum_threads ft ON ft.id = fr.thread_id
            WHERE ft.category_id = fc.id) AS reply_count,
           (SELECT ft2.title FROM forum_threads ft2 WHERE ft2.category_id = fc.id
            ORDER BY ft2.created_at DESC LIMIT 1) AS latest_thread
    FROM forum_categories fc ORDER BY fc.id
');
$categories = $stmt->fetchAll();

if ($searchQuery) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM forum_threads WHERE title LIKE ? OR content LIKE ?');
    $like = "%{$searchQuery}%";
    $countStmt->execute([$like, $like]);
    $totalThreads = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT ft.*, fc.name AS cat_name, u.name AS author,
               (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
        FROM forum_threads ft
        JOIN forum_categories fc ON fc.id = ft.category_id
        JOIN users u ON u.id = ft.user_id
        WHERE ft.title LIKE ? OR ft.content LIKE ?
        ORDER BY {$orderSql} LIMIT ? OFFSET ?"
    );
    $stmt->execute([$like, $like, $pageSize, $offset]);
    $recentThreads = $stmt->fetchAll();
} else {
    $countStmt = $pdo->query('SELECT COUNT(*) FROM forum_threads');
    $totalThreads = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT ft.*, fc.name AS cat_name, u.name AS author,
               (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
        FROM forum_threads ft
        JOIN forum_categories fc ON fc.id = ft.category_id
        JOIN users u ON u.id = ft.user_id
        ORDER BY {$orderSql} LIMIT ? OFFSET ?"
    );
    $stmt->execute([$pageSize, $offset]);
    $recentThreads = $stmt->fetchAll();
}

$totalPages = max(1, (int)ceil($totalThreads / $pageSize));

$popularThreads = $pdo->query('
    SELECT ft.*, u.name AS author,
           (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
    FROM forum_threads ft
    JOIN users u ON u.id = ft.user_id
    WHERE ft.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY reply_count DESC, views DESC LIMIT 4
')->fetchAll();

$pageTitle  = 'Forum';
$activePage = 'forum';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>💬 Discussion Forum</h1>
    <p>Explore space topics with fellow researchers</p>
  </div>
  <a href="<?= BASE_URL ?>/forum/create.php" class="btn btn-primary">+ New Thread</a>
</div>

<form method="GET" class="flex mb-20" style="gap:12px;flex-wrap:wrap;">
  <input type="search" name="q" class="form-control" placeholder="Search forum threads and posts…" value="<?= e($searchQuery) ?>" style="flex:1;min-width:220px;" autocomplete="off">
  <select name="sort" class="form-control" style="width:220px;min-width:220px;">
    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Sort: Latest</option>
    <option value="replies" <?= $sort === 'replies' ? 'selected' : '' ?>>Sort: Most Replies</option>
    <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Sort: Most Views</option>
  </select>
  <button type="submit" class="btn btn-primary">Search</button>
  <?php if ($searchQuery || $sort !== 'recent'): ?>
    <a href="<?= BASE_URL ?>/forum/" class="btn btn-outline">Reset</a>
  <?php endif; ?>
</form>

<!-- Categories -->
<h2 class="mb-16">Topics</h2>
<div class="card mb-24">
  <?php foreach ($categories as $cat): ?>
  <div style="display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid var(--border)">
    <div style="font-size:2rem;width:48px;text-align:center"><?= $cat['icon'] ?></div>
    <div style="flex:1">
      <a href="<?= BASE_URL ?>/forum/category.php?id=<?= $cat['id'] ?>"
         style="font-size:1rem;font-weight:600;color:var(--text-primary)">
        <?= e($cat['name']) ?>
      </a>
      <p style="font-size:.8rem;color:var(--text-muted);margin-top:2px"><?= e($cat['description']) ?></p>
      <?php if ($cat['latest_thread']): ?>
        <p style="font-size:.75rem;color:var(--text-muted);margin-top:4px">
          Latest: <?= e(substr($cat['latest_thread'],0,50)) ?>…
        </p>
      <?php endif; ?>
    </div>
    <div style="text-align:right;min-width:80px">
      <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--accent)"><?= $cat['thread_count'] ?></div>
      <div class="text-muted" style="font-size:.7rem">THREADS</div>
      <div style="font-size:.8rem;color:var(--text-muted)"><?= $cat['reply_count'] ?> replies</div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Popular this week -->
<div class="flex-between mb-16">
  <h2>Popular This Week</h2>
</div>
<div class="card mb-24">
  <?php if ($popularThreads): ?>
    <?php foreach ($popularThreads as $t): ?>
      <div style="display:flex;gap:16px;padding:14px 0;border-bottom:1px solid var(--border);align-items:flex-start">
        <div class="avatar"><?= strtoupper(substr($t['author'],0,1)) ?></div>
        <div style="flex:1">
          <a href="<?= BASE_URL ?>/forum/thread.php?id=<?= $t['id'] ?>" style="font-weight:600;color:var(--text-primary)"><?= e($t['title']) ?></a>
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
            by <?= e($t['author']) ?> · <?= $t['reply_count'] ?> replies · <?= $t['views'] ?> views
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="empty-state" style="padding:20px">No popular threads this week.</div>
  <?php endif; ?>
</div>

<!-- Recent threads -->
<div class="flex-between mb-16">
  <div>
    <h2><?= $searchQuery ? 'Search Results' : 'Recent Discussions' ?></h2>
    <?php if ($searchQuery): ?><p class="text-muted">Found <?= number_format($totalThreads) ?> results for "<?= e($searchQuery) ?>".</p><?php endif; ?>
  </div>
  <div class="flex gap-8" style="align-items:center;">
    <span class="text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
  </div>
</div>
<div class="card">
  <?php foreach ($recentThreads as $t): ?>
  <div style="display:flex;gap:16px;padding:14px 0;border-bottom:1px solid var(--border);align-items:flex-start">
    <div class="avatar"><?= strtoupper(substr($t['author'],0,1)) ?></div>
    <div style="flex:1">
      <?php if ($t['is_pinned']): ?><span class="badge badge-gold" style="margin-right:6px">📌 Pinned</span><?php endif; ?>
      <a href="<?= BASE_URL ?>/forum/thread.php?id=<?= $t['id'] ?>"
         style="font-weight:600;color:var(--text-primary)">
        <?= e($t['title']) ?>
      </a>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:3px">
        by <?= e($t['author']) ?> in
        <a href="<?= BASE_URL ?>/forum/category.php?id=<?= $t['category_id'] ?>" style="color:var(--accent)">
          <?= e($t['cat_name']) ?>
        </a>
        · <?= date('M j', strtotime($t['created_at'])) ?>
      </div>
    </div>
    <div style="text-align:right;min-width:60px">
      <div style="font-size:.85rem;color:var(--text-secondary)"><?= $t['reply_count'] ?> replies</div>
      <div style="font-size:.75rem;color:var(--text-muted)"><?= $t['views'] ?> views</div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$recentThreads): ?>
    <div class="empty-state" style="padding:30px">
      <p>No threads yet. Start a discussion!</p>
    </div>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="justify-content:center;margin-top:24px;">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="page-link <?= $p === $page ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/forum/?<?= http_build_query(array_filter(['q'=>$searchQuery,'sort'=>$sort,'page'=>$p])) ?>">
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
