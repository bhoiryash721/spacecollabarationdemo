<?php
// ============================================================
// dashboard.php — Personalized student dashboard
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$welcome  = isset($_GET['welcome']);

// ── Stats ─────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT points FROM users WHERE id = ?');
$stmt->execute([$userId]);
$points = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM project_members WHERE user_id = ?');
$stmt->execute([$userId]);
$projectCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM experiments WHERE user_id = ?');
$stmt->execute([$userId]);
$expCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE user_id = ?');
$stmt->execute([$userId]);
$commentCount = (int) $stmt->fetchColumn();

// ── Leaderboard rank ──────────────────────────────────────────
$stmt = $pdo->query('SELECT id FROM users WHERE role="student" ORDER BY points DESC');
$ranks = array_column($stmt->fetchAll(), 'id');
$myRank = array_search($userId, $ranks);
$myRank = $myRank !== false ? $myRank + 1 : '—';

// ── Joined projects ───────────────────────────────────────────
$stmt = $pdo->prepare('
    SELECT p.id, p.title, p.description, p.tags, p.created_at,
           u.name AS creator_name,
           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count
    FROM projects p
    JOIN project_members pm ON pm.project_id = p.id
    JOIN users u ON u.id = p.creator_id
    WHERE pm.user_id = ? AND p.status = "approved"
    ORDER BY pm.joined_at DESC LIMIT 6
');
$stmt->execute([$userId]);
$myProjects = $stmt->fetchAll();

// ── Recent experiments ─────────────────────────────────────────
$stmt = $pdo->query('
    SELECT e.id, e.title, e.description, e.created_at, u.name AS author,
           (SELECT COUNT(*) FROM likes WHERE entity_type="experiment" AND entity_id=e.id) AS like_count
    FROM experiments e
    JOIN users u ON u.id = e.user_id
    ORDER BY e.created_at DESC LIMIT 4
');
$recentExp = $stmt->fetchAll();

// ── Recent notifications ───────────────────────────────────────
$stmt = $pdo->prepare('
    SELECT message, link, is_read, created_at
    FROM notifications WHERE user_id = ?
    ORDER BY created_at DESC LIMIT 5
');
$stmt->execute([$userId]);
$notifs = $stmt->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<?php if ($welcome): ?>
  <div class="alert alert-success">
    🎉 Welcome aboard, <?= e($userName) ?>! Your space journey begins now. 🚀
  </div>
<?php endif; ?>

<div class="page-header">
  <div>
    <h1>Mission Control</h1>
    <p>Welcome back, <span style="color:var(--accent)"><?= e($userName) ?></span> — the cosmos awaits.</p>
  </div>
  <a href="<?= BASE_URL ?>/projects/create.php" class="btn btn-primary">
    + New Project
  </a>
</div>

<!-- Stats Row -->
<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon">⭐</div>
    <div class="stat-value"><?= number_format($points) ?></div>
    <div class="stat-label">TOTAL POINTS</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🛸</div>
    <div class="stat-value"><?= $projectCount ?></div>
    <div class="stat-label">PROJECTS JOINED</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🔬</div>
    <div class="stat-value"><?= $expCount ?></div>
    <div class="stat-label">EXPERIMENTS</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏆</div>
    <div class="stat-value">#<?= $myRank ?></div>
    <div class="stat-label">LEADERBOARD RANK</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💬</div>
    <div class="stat-value"><?= $commentCount ?></div>
    <div class="stat-label">CONTRIBUTIONS</div>
  </div>
</div>

<!-- Two-column layout -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:24px" class="dashboard-grid">

  <!-- Left: Projects -->
  <div>
    <div class="flex-between mb-16">
      <h2>My Projects</h2>
      <a href="<?= BASE_URL ?>/projects/" class="btn btn-sm btn-outline">View All</a>
    </div>

    <?php if ($myProjects): ?>
    <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
      <?php foreach ($myProjects as $p): ?>
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title"><?= e($p['title']) ?></div>
            <div class="card-meta">by <?= e($p['creator_name']) ?> · <?= $p['member_count'] ?> members</div>
          </div>
        </div>
        <p style="font-size:.84rem;margin-bottom:14px;color:var(--text-secondary);-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
          <?= e($p['description']) ?>
        </p>
        <?php if ($p['tags']): ?>
        <div class="tags-wrap">
          <?php foreach (array_slice(explode(',', $p['tags']), 0, 3) as $tag): ?>
            <span class="badge badge-blue"><?= e(trim($tag)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/projects/view.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" style="margin-top:14px">Open Project</a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state card">
      <div class="empty-icon">🛸</div>
      <h3>No projects yet</h3>
      <p>Join or create your first space research project!</p>
      <a href="<?= BASE_URL ?>/projects/" class="btn btn-primary mt-16">Browse Projects</a>
    </div>
    <?php endif; ?>

    <!-- Recent experiments -->
    <div class="flex-between mb-16 mt-24">
      <h2>Recent Experiments</h2>
      <a href="<?= BASE_URL ?>/experiments/" class="btn btn-sm btn-outline">All Experiments</a>
    </div>
    <div class="card">
      <?php foreach ($recentExp as $exp): ?>
      <div style="padding:14px 0;border-bottom:1px solid var(--border)">
        <div class="flex-between">
          <div>
            <a href="<?= BASE_URL ?>/experiments/view.php?id=<?= $exp['id'] ?>" style="font-weight:600;color:var(--text-primary)">
              <?= e($exp['title']) ?>
            </a>
            <div class="card-meta">by <?= e($exp['author']) ?></div>
          </div>
          <span class="badge badge-purple">❤ <?= $exp['like_count'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right: Notifications -->
  <div>
    <div class="flex-between mb-16">
      <h2>Notifications</h2>
    </div>
    <div class="card">
      <?php if ($notifs): ?>
        <?php foreach ($notifs as $n): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--border)">
          <div style="display:flex;gap:10px;align-items:flex-start">
            <span style="font-size:1rem"><?= $n['is_read'] ? '🔘' : '🔵' ?></span>
            <div>
              <p style="font-size:.82rem;color:<?= $n['is_read'] ? 'var(--text-muted)' : 'var(--text-primary)' ?>">
                <?= e($n['message']) ?>
              </p>
              <span style="font-size:.7rem;color:var(--text-muted)">
                <?= date('M j, g:i a', strtotime($n['created_at'])) ?>
              </span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:30px">
          <div class="empty-icon">🔔</div>
          <p>No notifications yet</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick links -->
    <div class="mt-24">
      <h2 class="mb-16">Quick Actions</h2>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="<?= BASE_URL ?>/projects/create.php" class="btn btn-outline">🛸 Create Project</a>
        <a href="<?= BASE_URL ?>/experiments/create.php" class="btn btn-outline">🔬 Share Experiment</a>
        <a href="<?= BASE_URL ?>/forum/" class="btn btn-outline">💬 Open Forum</a>
        <a href="<?= BASE_URL ?>/leaderboard/" class="btn btn-outline">🏆 Leaderboard</a>
      </div>
    </div>
  </div>

</div><!-- dashboard-grid -->

<style>
@media(max-width:900px){
  .dashboard-grid{grid-template-columns:1fr!important}
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
