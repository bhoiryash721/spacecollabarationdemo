<?php
// ============================================================
// projects/index.php — Browse & search projects
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');
$tag    = trim($_GET['tag'] ?? '');

// Build query dynamically
$where = ["p.status = 'approved'"];
$params = [];
if ($search) { $where[] = '(p.title LIKE ? OR p.description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($tag)    { $where[] = 'p.tags LIKE ?'; $params[] = "%$tag%"; }

$sql = '
    SELECT p.*, u.name AS creator_name,
           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count,
           EXISTS(SELECT 1 FROM project_members WHERE project_id = p.id AND user_id = ?) AS is_member
    FROM projects p
    JOIN users u ON u.id = p.creator_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY p.created_at DESC
';
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$userId], $params));
$projects = $stmt->fetchAll();

$pageTitle  = 'Projects';
$activePage = 'projects';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🛸 Space Projects</h1>
    <p>Collaborate on groundbreaking space research projects</p>
  </div>
  <a href="<?= BASE_URL ?>/projects/create.php" class="btn btn-primary">+ New Project</a>
</div>

<!-- Search -->
<div class="search-bar">
  <input type="text" id="search-input" class="form-control" placeholder="Search projects…"
         value="<?= e($search) ?>" oninput="filterProjects(this.value)">
  <?php if ($tag): ?>
    <a href="?" class="btn btn-outline">✕ Clear Tag</a>
  <?php endif; ?>
</div>

<?php if ($tag): ?>
  <div class="alert alert-info">Filtering by tag: <strong><?= e($tag) ?></strong></div>
<?php endif; ?>

<!-- Projects Grid -->
<?php if ($projects): ?>
<div class="card-grid" id="projects-grid">
  <?php foreach ($projects as $p): ?>
  <div class="card project-card" data-title="<?= strtolower(e($p['title'])) ?>">
    <div class="card-header">
      <div style="flex:1">
        <h3 style="margin-bottom:4px">
          <a href="<?= BASE_URL ?>/projects/view.php?id=<?= $p['id'] ?>" style="color:var(--text-primary)">
            <?= e($p['title']) ?>
          </a>
        </h3>
        <div class="card-meta">
          by <?= e($p['creator_name']) ?> · <?= $p['member_count'] ?> members
          · <?= date('M Y', strtotime($p['created_at'])) ?>
        </div>
      </div>
      <?php if ($p['is_member']): ?>
        <span class="badge badge-green">✓ Joined</span>
      <?php endif; ?>
    </div>

    <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:14px;
              -webkit-line-clamp:3;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
      <?= e($p['description']) ?>
    </p>

    <?php if ($p['tags']): ?>
    <div class="tags-wrap" style="margin-bottom:16px">
      <?php foreach (array_slice(explode(',', $p['tags']), 0, 4) as $t): ?>
        <a href="?tag=<?= urlencode(trim($t)) ?>" class="badge badge-blue"><?= e(trim($t)) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="flex gap-8">
      <a href="<?= BASE_URL ?>/projects/view.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">View</a>
      <?php if (!$p['is_member']): ?>
        <form method="POST" action="<?= BASE_URL ?>/projects/join.php" style="display:inline">
          <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
          <button type="submit" class="btn btn-primary btn-sm">Join</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state card">
  <div class="empty-icon">🛸</div>
  <h3>No projects found</h3>
  <p>Be the first to create a space research project!</p>
  <a href="<?= BASE_URL ?>/projects/create.php" class="btn btn-primary mt-16">Create Project</a>
</div>
<?php endif; ?>

<script>
function filterProjects(q) {
  const cards = document.querySelectorAll('.project-card');
  q = q.toLowerCase();
  cards.forEach(c => {
    c.style.display = c.dataset.title.includes(q) ? '' : 'none';
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
