<?php
// ============================================================
// projects/view.php — Single project page
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId    = $_SESSION['user_id'];
$projectId = (int) ($_GET['id'] ?? 0);
if (!$projectId) { header('Location: ' . BASE_URL . '/projects/'); exit; }

$stmt = $pdo->prepare('
    SELECT p.*, u.name AS creator_name
    FROM projects p JOIN users u ON u.id = p.creator_id
    WHERE p.id = ? AND p.status = "approved"
');
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) { echo 'Project not found.'; exit; }

// Is current user a member?
$stmt = $pdo->prepare('SELECT id FROM project_members WHERE project_id=? AND user_id=?');
$stmt->execute([$projectId, $userId]);
$isMember = (bool) $stmt->fetch();

// Members list
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.points FROM users u
    JOIN project_members pm ON pm.user_id = u.id
    WHERE pm.project_id = ?
');
$stmt->execute([$projectId]);
$members = $stmt->fetchAll();

// Comments
$stmt = $pdo->prepare('
    SELECT c.content, c.created_at, u.name
    FROM comments c JOIN users u ON u.id = c.user_id
    WHERE c.entity_type = "project" AND c.entity_id = ?
    ORDER BY c.created_at DESC
');
$stmt->execute([$projectId]);
$comments = $stmt->fetchAll();

// Files
$stmt = $pdo->prepare('
    SELECT pf.*, u.name AS uploader
    FROM project_files pf JOIN users u ON u.id = pf.uploader_id
    WHERE pf.project_id = ? ORDER BY pf.uploaded_at DESC
');
$stmt->execute([$projectId]);
$files = $stmt->fetchAll();

// Handle file upload
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isMember && isset($_FILES['pfile'])) {
    $allowed = ['application/pdf','image/jpeg','image/png','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $mime = mime_content_type($_FILES['pfile']['tmp_name']);
    if (!in_array($mime, $allowed)) {
        $uploadError = 'Only PDF, JPG, PNG, DOC, DOCX allowed.';
    } elseif ($_FILES['pfile']['size'] > 10 * 1024 * 1024) {
        $uploadError = 'File must be under 10MB.';
    } else {
        $ext  = pathinfo($_FILES['pfile']['name'], PATHINFO_EXTENSION);
        $safe = uniqid('file_', true) . '.' . $ext;
        move_uploaded_file($_FILES['pfile']['tmp_name'], __DIR__ . '/../uploads/projects/' . $safe);
        $pdo->prepare('INSERT INTO project_files (project_id, uploader_id, filename, original, file_type, file_size) VALUES (?,?,?,?,?,?)')
            ->execute([$projectId, $userId, $safe, $_FILES['pfile']['name'], $mime, $_FILES['pfile']['size']]);
        awardPoints($pdo, $userId, 20);
        header('Location: ?id=' . $projectId . '&uploaded=1'); exit;
    }
}

$pageTitle  = e($project['title']);
$activePage = 'projects';
include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_GET['created'])): ?>
  <div class="alert alert-success">🎉 Project launched! You earned 50 points.</div>
<?php endif; ?>
<?php if (isset($_GET['uploaded'])): ?>
  <div class="alert alert-success">📎 File uploaded! You earned 20 points.</div>
<?php endif; ?>
<?php if ($uploadError): ?>
  <div class="alert alert-danger">⚠️ <?= e($uploadError) ?></div>
<?php endif; ?>

<div class="page-header">
  <div>
    <h1><?= e($project['title']) ?></h1>
    <div class="card-meta">
      Created by <?= e($project['creator_name']) ?> ·
      <?= date('M j, Y', strtotime($project['created_at'])) ?> ·
      <?= count($members) ?> members
    </div>
  </div>
  <div class="flex gap-8">
    <?php if (!$isMember): ?>
      <form method="POST" action="<?= BASE_URL ?>/projects/join.php">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">
        <button type="submit" class="btn btn-primary">Join Project</button>
      </form>
    <?php else: ?>
      <span class="badge badge-green" style="padding:8px 16px">✓ You're a member</span>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/projects/" class="btn btn-outline">← Back</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px" class="project-layout">

  <!-- Main content -->
  <div>
    <!-- Description -->
    <div class="card mb-16">
      <?php if ($project['tags']): ?>
      <div class="tags-wrap" style="margin-bottom:16px">
        <?php foreach (explode(',', $project['tags']) as $t): ?>
          <a href="<?= BASE_URL ?>/projects/?tag=<?= urlencode(trim($t)) ?>" class="badge badge-blue"><?= e(trim($t)) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <h3 class="mb-16">About this Project</h3>
      <p><?= nl2br(e($project['description'])) ?></p>

      <?php if ($project['objectives']): ?>
      <div class="glow-divider"></div>
      <h3 class="mb-16">Objectives</h3>
      <p style="white-space:pre-line"><?= e($project['objectives']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Files -->
    <div class="card mb-16">
      <div class="flex-between mb-16">
        <h3>Project Files</h3>
        <?php if ($isMember): ?>
          <label class="btn btn-sm btn-outline" style="cursor:pointer">
            + Upload File
            <form method="POST" enctype="multipart/form-data" id="file-form" style="display:none">
              <input type="file" name="pfile" onchange="document.getElementById('file-form').submit()" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            </form>
          </label>
        <?php endif; ?>
      </div>

      <?php if ($files): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Filename</th><th>Uploader</th><th>Size</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $f): ?>
              <tr>
                <td>📎 <?= e($f['original']) ?></td>
                <td><?= e($f['uploader']) ?></td>
                <td><?= round($f['file_size'] / 1024) ?> KB</td>
                <td><?= date('M j', strtotime($f['uploaded_at'])) ?></td>
                <td>
                  <a href="<?= BASE_URL ?>/uploads/projects/<?= e($f['filename']) ?>"
                     class="btn btn-sm btn-outline" target="_blank">↓ Download</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">No files uploaded yet.</p>
      <?php endif; ?>
    </div>

    <!-- Comments -->
    <div class="card">
      <h3 class="mb-16">Discussion (<?= count($comments) ?>)</h3>

      <div id="comment-list-<?= $projectId ?>">
        <?php foreach ($comments as $c): ?>
        <div class="comment-item">
          <div class="avatar"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
          <div class="comment-body">
            <span class="comment-author"><?= e($c['name']) ?></span>
            <span class="comment-time"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
            <p class="comment-text"><?= nl2br(e($c['content'])) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
          <p class="text-muted">No comments yet. Start the discussion!</p>
        <?php endif; ?>
      </div>

      <?php if ($isMember): ?>
      <form class="comment-form mt-16" data-type="project" data-id="<?= $projectId ?>">
        <textarea class="form-control" rows="3" placeholder="Write a comment…" required></textarea>
        <button type="submit" class="btn btn-primary btn-sm mt-8">Post Comment (+5 pts)</button>
      </form>
      <?php else: ?>
        <p class="text-muted mt-16"><a href="?id=<?= $projectId ?>">Join the project</a> to comment.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sidebar: Members -->
  <div>
    <div class="card mb-16">
      <h3 class="mb-16">Members (<?= count($members) ?>)</h3>
      <?php foreach ($members as $m): ?>
      <div class="rank-row" style="padding:10px 0">
        <div class="avatar"><?= strtoupper(substr($m['name'], 0, 1)) ?></div>
        <div>
          <div style="font-size:.88rem;font-weight:600"><?= e($m['name']) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted)"><?= number_format($m['points']) ?> pts</div>
        </div>
        <?php if ($m['id'] === (int)$project['creator_id']): ?>
          <span class="badge badge-gold" style="margin-left:auto">Creator</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Project Stats -->
    <div class="card">
      <h3 class="mb-16">Stats</h3>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div class="flex-between">
          <span class="text-muted">Files</span>
          <strong><?= count($files) ?></strong>
        </div>
        <div class="flex-between">
          <span class="text-muted">Comments</span>
          <strong><?= count($comments) ?></strong>
        </div>
        <div class="flex-between">
          <span class="text-muted">Created</span>
          <strong><?= date('M Y', strtotime($project['created_at'])) ?></strong>
        </div>
      </div>
    </div>
  </div>

</div>

<style>
@media(max-width:900px){ .project-layout{grid-template-columns:1fr!important} }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
