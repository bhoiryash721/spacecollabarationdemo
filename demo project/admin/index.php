<?php
// admin/index.php — Admin Dashboard
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Stats
$stats = [];
foreach (['users','projects','experiments','forum_threads','comments'] as $tbl) {
    $stats[$tbl] = (int)$pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
}
$pendingProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='pending'")->fetchColumn();

$weeklyStats = [];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmt->execute();
$weeklyStats['new_users'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM experiments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmt->execute();
$weeklyStats['new_experiments'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmt->execute();
$weeklyStats['new_comments'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM forum_threads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmt->execute();
$weeklyStats['new_threads'] = (int)$stmt->fetchColumn();

$topUsers = $pdo->query("SELECT id,name,points,email FROM users WHERE role='student' ORDER BY points DESC LIMIT 8")->fetchAll();
$topProjects = $pdo->query('SELECT p.id,p.title,p.status,COUNT(pm.user_id) AS members FROM projects p LEFT JOIN project_members pm ON pm.project_id = p.id GROUP BY p.id ORDER BY members DESC LIMIT 8')->fetchAll();

// Recent users
$users = $pdo->query('SELECT id,name,email,role,points,is_active,created_at FROM users ORDER BY created_at DESC LIMIT 20')->fetchAll();

// Pending projects
$pending = $pdo->query("SELECT p.*,u.name AS creator FROM projects p JOIN users u ON u.id=p.creator_id WHERE p.status='pending' ORDER BY p.created_at DESC")->fetchAll();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['target_id'] ?? 0);

    if ($action === 'toggle_user' && $targetId) {
        $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role != "admin"')->execute([$targetId]);
    } elseif ($action === 'delete_user' && $targetId) {
        $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"')->execute([$targetId]);
    } elseif ($action === 'approve_project' && $targetId) {
        $pdo->prepare('UPDATE projects SET status="approved" WHERE id=?')->execute([$targetId]);
    } elseif ($action === 'reject_project' && $targetId) {
        $pdo->prepare('UPDATE projects SET status="rejected" WHERE id=?')->execute([$targetId]);
    } elseif ($action === 'delete_project' && $targetId) {
        $pdo->prepare('DELETE FROM projects WHERE id=?')->execute([$targetId]);
    } elseif ($action === 'delete_thread' && $targetId) {
        $pdo->prepare('DELETE FROM forum_threads WHERE id=?')->execute([$targetId]);
    }
    header('Location: ' . BASE_URL . '/admin/'); exit;
}

$pageTitle  = 'Admin Panel';
$activePage = 'admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>⚙️ Mission Control Admin</h1>
    <p>Manage users, projects, and community content</p>
  </div>
</div>

<!-- Stats -->
<div class="stats-row">
  <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= $stats['users'] ?></div><div class="stat-label">TOTAL USERS</div></div>
  <div class="stat-card"><div class="stat-icon">🛸</div><div class="stat-value"><?= $stats['projects'] ?></div><div class="stat-label">PROJECTS</div></div>
  <div class="stat-card"><div class="stat-icon">🔬</div><div class="stat-value"><?= $stats['experiments'] ?></div><div class="stat-label">EXPERIMENTS</div></div>
  <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?= $stats['forum_threads'] ?></div><div class="stat-label">THREADS</div></div>
  <?php if ($pendingProjects > 0): ?>
  <div class="stat-card" style="border-color:rgba(245,158,11,0.4)">
    <div class="stat-icon">⏳</div>
    <div class="stat-value" style="color:var(--accent3)"><?= $pendingProjects ?></div>
    <div class="stat-label">PENDING APPROVAL</div>
  </div>
  <?php endif; ?>
</div>

<!-- Platform analytics -->
<div class="flex-between mb-16">
  <h2>📊 Platform analytics</h2>
</div>
<div class="stats-row mb-24">
  <div class="stat-card">
    <div class="stat-icon">🆕</div>
    <div class="stat-value"><?= $weeklyStats['new_users'] ?></div>
    <div class="stat-label">New users (7d)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🔬</div>
    <div class="stat-value"><?= $weeklyStats['new_experiments'] ?></div>
    <div class="stat-label">New experiments (7d)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💬</div>
    <div class="stat-value"><?= $weeklyStats['new_comments'] ?></div>
    <div class="stat-label">New comments (7d)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🧵</div>
    <div class="stat-value"><?= $weeklyStats['new_threads'] ?></div>
    <div class="stat-label">New threads (7d)</div>
  </div>
</div>

<div class="card mb-24">
  <h2 class="mb-16">🏅 Top contributors</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Points</th></tr></thead>
      <tbody>
        <?php foreach ($topUsers as $user): ?>
        <tr>
          <td><?= e($user['name']) ?></td>
          <td><?= e($user['email']) ?></td>
          <td><?= number_format($user['points']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mb-24">
  <h2 class="mb-16">🚀 Top projects by members</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Project</th><th>Status</th><th>Members</th></tr></thead>
      <tbody>
        <?php foreach ($topProjects as $project): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/projects/view.php?id=<?= $project['id'] ?>" style="color:var(--text-primary)"><?= e($project['title']) ?></a></td>
          <td><span class="badge <?= $project['status']==='approved' ? 'badge-green' : ($project['status']==='pending' ? 'badge-gold' : 'badge-red') ?>"><?= e($project['status']) ?></span></td>
          <td><?= (int)$project['members'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pending Projects -->
<?php if ($pending): ?>
<h2 class="mb-16">⏳ Pending Projects</h2>
<div class="card mb-24">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Creator</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($pending as $p): ?>
        <tr>
          <td><strong><?= e($p['title']) ?></strong></td>
          <td><?= e($p['creator']) ?></td>
          <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
          <td>
            <div class="flex gap-8">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="approve_project">
                <input type="hidden" name="target_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-success">✓ Approve</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="reject_project">
                <input type="hidden" name="target_id" value="<?= $p['id'] ?>">
                <button class="btn btn-sm btn-danger">✕ Reject</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- User Management -->
<div class="flex-between mb-16">
  <h2>👥 User Management</h2>
</div>
<div class="card mb-24">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Points</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="flex flex-center gap-8">
              <div class="avatar" style="width:28px;height:28px;font-size:.65rem"><?= strtoupper(substr($u['name'],0,1)) ?></div>
              <?= e($u['name']) ?>
            </div>
          </td>
          <td><?= e($u['email']) ?></td>
          <td>
            <span class="badge <?= $u['role']==='admin' ? 'badge-gold' : 'badge-blue' ?>">
              <?= $u['role'] ?>
            </span>
          </td>
          <td><?= number_format($u['points']) ?></td>
          <td>
            <span class="badge <?= $u['is_active'] ? 'badge-green' : 'badge-red' ?>">
              <?= $u['is_active'] ? 'Active' : 'Banned' ?>
            </span>
          </td>
          <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['role'] !== 'admin'): ?>
            <div class="flex gap-8">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_user">
                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                  <?= $u['is_active'] ? 'Ban' : 'Unban' ?>
                </button>
              </form>
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Delete user <?= e($u['name']) ?>? This is irreversible.')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- All projects -->
<h2 class="mb-16">🛸 All Projects</h2>
<div class="card mb-24">
  <div class="table-wrap">
    <?php
    $allProjects = $pdo->query("SELECT p.*,u.name AS creator FROM projects p JOIN users u ON u.id=p.creator_id ORDER BY p.created_at DESC LIMIT 30")->fetchAll();
    ?>
    <table>
      <thead><tr><th>Title</th><th>Creator</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($allProjects as $p): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/projects/view.php?id=<?= $p['id'] ?>" style="color:var(--text-primary)"><?= e($p['title']) ?></a></td>
          <td><?= e($p['creator']) ?></td>
          <td>
            <span class="badge <?= $p['status']==='approved' ? 'badge-green' : ($p['status']==='pending' ? 'badge-gold' : 'badge-red') ?>">
              <?= $p['status'] ?>
            </span>
          </td>
          <td>
            <form method="POST" style="display:inline"
                  onsubmit="return confirm('Delete this project?')">
              <input type="hidden" name="action" value="delete_project">
              <input type="hidden" name="target_id" value="<?= $p['id'] ?>">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Forum threads -->
<h2 class="mb-16">💬 Forum Moderation</h2>
<div class="card">
  <?php
  $threads = $pdo->query("SELECT ft.*,u.name AS author,fc.name AS cat FROM forum_threads ft JOIN users u ON u.id=ft.user_id JOIN forum_categories fc ON fc.id=ft.category_id ORDER BY ft.created_at DESC LIMIT 20")->fetchAll();
  ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Thread</th><th>Author</th><th>Category</th><th>Replies</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($threads as $t): ?>
        <?php
          $replyStmt = $pdo->prepare('SELECT COUNT(*) FROM forum_replies WHERE thread_id = ?');
          $replyStmt->execute([$t['id']]);
          $rCount = (int)$replyStmt->fetchColumn();
        ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/forum/thread.php?id=<?= $t['id'] ?>" style="color:var(--text-primary)"><?= e(substr($t['title'],0,50)) ?></a></td>
          <td><?= e($t['author']) ?></td>
          <td><?= e($t['cat']) ?></td>
          <td><?= $rCount ?></td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this thread?')">
              <input type="hidden" name="action" value="delete_thread">
              <input type="hidden" name="target_id" value="<?= $t['id'] ?>">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
