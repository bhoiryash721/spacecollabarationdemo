<?php
// ============================================================
// projects/create.php — Create a new project
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];
$vals   = ['title'=>'','description'=>'','objectives'=>'','tags'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['title']       = trim($_POST['title']       ?? '');
    $vals['description'] = trim($_POST['description'] ?? '');
    $vals['objectives']  = trim($_POST['objectives']  ?? '');
    $vals['tags']        = trim($_POST['tags']        ?? '');

    if (strlen($vals['title']) < 3)        $errors['title'] = 'Title must be at least 3 characters.';
    if (strlen($vals['description']) < 20) $errors['description'] = 'Description must be at least 20 characters.';

    // Handle cover image upload
    $coverImage = null;
    if (!empty($_FILES['cover']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime    = mime_content_type($_FILES['cover']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $errors['cover'] = 'Only JPG, PNG, GIF, WEBP images allowed.';
        } elseif ($_FILES['cover']['size'] > 5 * 1024 * 1024) {
            $errors['cover'] = 'Image must be under 5MB.';
        } else {
            $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
            $coverImage = uniqid('cover_', true) . '.' . $ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], __DIR__ . '/../uploads/projects/' . $coverImage);
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('
            INSERT INTO projects (title, description, objectives, tags, creator_id, cover_image)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$vals['title'], $vals['description'], $vals['objectives'], $vals['tags'], $userId, $coverImage]);
        $pid = $pdo->lastInsertId();

        // Auto-join as member
        $pdo->prepare('INSERT INTO project_members (project_id, user_id) VALUES (?, ?)')->execute([$pid, $userId]);

        // Award points
        awardPoints($pdo, $userId, 50);

        header('Location: ' . BASE_URL . '/projects/view.php?id=' . $pid . '&created=1');
        exit;
    }
}

$pageTitle  = 'Create Project';
$activePage = 'projects';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🛸 Create Project</h1>
    <p>Launch a new collaborative space research project</p>
  </div>
  <a href="<?= BASE_URL ?>/projects/" class="btn btn-outline">← Back</a>
</div>

<div style="max-width:700px">
  <div class="card">
    <form method="POST" enctype="multipart/form-data">

      <div class="form-group">
        <label class="form-label">Project Title *</label>
        <input type="text" name="title" class="form-control" required
               placeholder="e.g. Mars Rover Navigation AI"
               value="<?= e($vals['title']) ?>">
        <?php if (isset($errors['title'])): ?><div class="form-error"><?= e($errors['title']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Description *</label>
        <textarea name="description" class="form-control" rows="5" required
                  placeholder="Describe your project goals, methodology, and expected outcomes…"><?= e($vals['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="form-error"><?= e($errors['description']) ?></div><?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Objectives <span class="text-muted">(one per line)</span></label>
        <textarea name="objectives" class="form-control" rows="4"
                  placeholder="· Simulate Martian terrain&#10;· Implement pathfinding algorithm&#10;· Publish results"><?= e($vals['objectives']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Tags <span class="text-muted">(comma-separated)</span></label>
        <input type="text" name="tags" class="form-control"
               placeholder="Mars, AI, Robotics, Navigation"
               value="<?= e($vals['tags']) ?>">
        <div class="form-hint">Tags help others discover your project</div>
      </div>

      <div class="form-group">
        <label class="form-label">Cover Image <span class="text-muted">(optional, max 5MB)</span></label>
        <input type="file" name="cover" id="media-upload" class="form-control" accept="image/*">
        <div id="media-preview"></div>
        <?php if (isset($errors['cover'])): ?><div class="form-error"><?= e($errors['cover']) ?></div><?php endif; ?>
      </div>

      <div style="display:flex;gap:12px;margin-top:24px">
        <button type="submit" class="btn btn-primary btn-lg">🚀 Launch Project (+50 pts)</button>
        <a href="<?= BASE_URL ?>/projects/" class="btn btn-outline btn-lg">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
