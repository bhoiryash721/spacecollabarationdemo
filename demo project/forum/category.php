<?php
// forum/category.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$searchQuery = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'recent';
$validSorts = ['recent', 'replies', 'views'];
if (!in_array($sort, $validSorts, true)) { $sort = 'recent'; }
$pageSize = 10;
$offset = ($page - 1) * $pageSize;

$catId = (int)($_GET['id'] ?? 0);
if (!$catId) { header('Location: ' . BASE_URL . '/forum/'); exit; }

$stmt = $pdo->prepare('SELECT * FROM forum_categories WHERE id=?');
$stmt->execute([$catId]);
$cat = $stmt->fetch();
if (!$cat) { header('Location: ' . BASE_URL . '/forum/'); exit; }

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

if ($searchQuery) {
    $like = "%{$searchQuery}%";
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM forum_threads WHERE category_id = ? AND (title LIKE ? OR content LIKE ?)');
    $countStmt->execute([$catId, $like, $like]);
    $totalThreads = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT ft.*, u.name AS author,
               (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
        FROM forum_threads ft JOIN users u ON u.id = ft.user_id
        WHERE ft.category_id = ? AND (ft.title LIKE ? OR ft.content LIKE ?)
        ORDER BY {$orderSql} LIMIT ? OFFSET ?"
    );
    $stmt->execute([$catId, $like, $like, $pageSize, $offset]);
} else {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM forum_threads WHERE category_id = ?');
    $countStmt->execute([$catId]);
    $totalThreads = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT ft.*, u.name AS author,
               (SELECT COUNT(*) FROM forum_replies WHERE thread_id = ft.id) AS reply_count
        FROM forum_threads ft JOIN users u ON u.id = ft.user_id
        WHERE ft.category_id = ?
        ORDER BY {$orderSql} LIMIT ? OFFSET ?"
    );
    $stmt->execute([$catId, $pageSize, $offset]);
}
$threads = $stmt->fetchAll();
$totalPages = max(1, (int)ceil($totalThreads / $pageSize));

$pageTitle  = e($cat['name']) . ' Forum';
$activePage = 'forum';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= $cat['icon'] ?> <?= e($cat['name']) ?></h1>
    <p><?= e($cat['description']) ?></p>
  </div>
  <div class="flex gap-8">
    <a href="<?= BASE_URL ?>/forum/create.php?cat=<?= $catId ?>" class="btn btn-primary">+ New Thread</a>
    <a href="<?= BASE_URL ?>/forum/" class="btn btn-outline">← Forum</a>
  </div>
</div>

<form method="GET" class="flex mb-20" style="gap:12px;flex-wrap:wrap;">
  <input type="hidden" name="id" value="<?= $catId ?>">
  <input type="search" name="q" class="form-control" placeholder="Search in <?= e($cat['name']) ?>…" value="<?= e($searchQuery) ?>" style="flex:1;min-width:220px;" autocomplete="off">
  <select name="sort" class="form-control" style="width:220px;min-width:220px;">
    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Sort: Latest</option>
    <option value="replies" <?= $sort === 'replies' ? 'selected' : '' ?>>Sort: Most Replies</option>
    <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Sort: Most Views</option>
  </select>
  <button type="submit" class="btn btn-primary">Search</button>
  <?php if ($searchQuery || $sort !== 'recent'): ?>
    <a href="<?= BASE_URL ?>/forum/category.php?id=<?= $catId ?>" class="btn btn-outline">Reset</a>
  <?php endif; ?>
</form>

<div class="card">
  <?php if ($searchQuery): ?>
    <div class="alert alert-info">Showing <?= count($threads) ?> results for "<?= e($searchQuery) ?>" in <?= e($cat['name']) ?>.</div>
  <?php endif; ?>
  <div style="display:flex;padding:10px 0;border-bottom:1px solid var(--border)">
    <div style="flex:1;font-family:var(--font-display);font-size:.7rem;letter-spacing:.1em;color:var(--text-muted)">THREAD</div>
    <div style="width:80px;text-align:center;font-family:var(--font-display);font-size:.7rem;letter-spacing:.1em;color:var(--text-muted)">REPLIES</div>
    <div style="width:80px;text-align:center;font-family:var(--font-display);font-size:.7rem;letter-spacing:.1em;color:var(--text-muted)">VIEWS</div>
    <div style="width:100px;font-family:var(--font-display);font-size:.7rem;letter-spacing:.1em;color:var(--text-muted)">DATE</div>
  </div>

  <?php foreach ($threads as $t): ?>
  <div style="display:flex;align-items:center;gap:16px;padding:14px 0;border-bottom:1px solid var(--border)">
    <div class="avatar"><?= strtoupper(substr($t['author'],0,1)) ?></div>
    <div style="flex:1">
      <?php if ($t['is_pinned']): ?><span class="badge badge-gold" style="margin-right:6px">📌</span><?php endif; ?>
      <a href="<?= BASE_URL ?>/forum/thread.php?id=<?= $t['id'] ?>"
         style="font-weight:600;color:var(--text-primary)">
        <?= e($t['title']) ?>
      </a>
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">
        by <?= e($t['author']) ?>
      </div>
    </div>
    <div style="width:80px;text-align:center;font-size:.9rem;color:var(--text-secondary)"><?= $t['reply_count'] ?></div>
    <div style="width:80px;text-align:center;font-size:.9rem;color:var(--text-secondary)"><?= $t['views'] ?></div>
    <div style="width:100px;font-size:.8rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($t['created_at'])) ?></div>
  </div>
  <?php endforeach; ?>

  <?php if (!$threads): ?>
    <div class="empty-state" style="padding:40px">
      <div class="empty-icon"><?= $cat['icon'] ?></div>
      <h3>No threads yet</h3>
      <p>Start the first discussion in this category!</p>
      <a href="<?= BASE_URL ?>/forum/create.php?cat=<?= $catId ?>" class="btn btn-primary mt-16">Create Thread</a>
    </div>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="justify-content:center;margin-top:24px;">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="page-link <?= $p === $page ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/forum/category.php?<?= http_build_query(array_filter(['id'=>$catId,'q'=>$searchQuery,'sort'=>$sort,'page'=>$p])) ?>">
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
