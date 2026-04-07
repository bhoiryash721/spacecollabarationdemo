<?php
// experiments/create.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];
$errors = [];
$vals   = ['title'=>'','description'=>'','steps'=>'','tags'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['title']       = trim($_POST['title']       ?? '');
    $vals['description'] = trim($_POST['description'] ?? '');
    $vals['steps']       = trim($_POST['steps']       ?? '');
    $vals['tags']        = trim($_POST['tags']        ?? '');

    if (strlen($vals['title']) < 3)        $errors['title']       = 'Title must be at least 3 characters.';
    if (strlen($vals['description']) < 10) $errors['description'] = 'Description too short.';

    $mediaFile = null;
    if (!empty($_FILES['media']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/webm'];
        $mime    = mime_content_type($_FILES['media']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $errors['media'] = 'Only images (JPG/PNG/GIF/WEBP) or videos (MP4/WEBM) allowed.';
        } elseif ($_FILES['media']['size'] > 20 * 1024 * 1024) {
            $errors['media'] = 'File must be under 20MB.';
        } else {
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $mediaFile = uniqid('exp_', true) . '.' . $ext;
            move_uploaded_file($_FILES['media']['tmp_name'], __DIR__ . '/../uploads/experiments/' . $mediaFile);
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('
            INSERT INTO experiments (user_id, title, description, steps, media, tags)
            VALUES (?,?,?,?,?,?)
        ');
        $stmt->execute([$userId, $vals['title'], $vals['description'], $vals['steps'], $mediaFile, $vals['tags']]);
        awardPoints($pdo, $userId, 30);
        header('Location: ' . BASE_URL . '/experiments/?posted=1'); exit;
    }
}

$pageTitle  = 'Share Experiment';
$activePage = 'experiments';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🔬 Share Experiment</h1>
    <p>Document and share your space science experiment</p>
  </div>
  <a href="<?= BASE_URL ?>/experiments/" class="btn btn-outline">← Back</a>
</div>

<div style="max-width:700px">
<div class="card">
<form method="POST" enctype="multipart/form-data">
  <div class="form-group">
    <label class="form-label">Experiment Title *</label>
    <input type="text" name="title" class="form-control" required
           placeholder="e.g. DIY Cloud Chamber for Cosmic Ray Detection"
           value="<?= e($vals['title']) ?>">
    <?php if (isset($errors['title'])): ?><div class="form-error"><?= e($errors['title']) ?></div><?php endif; ?>
  </div>

  <div class="form-group">
    <label class="form-label">Description *</label>
    <textarea name="description" class="form-control" rows="4" required
              placeholder="What is this experiment about? What will students learn?"><?= e($vals['description']) ?></textarea>
    <?php if (isset($errors['description'])): ?><div class="form-error"><?= e($errors['description']) ?></div><?php endif; ?>
  </div>

  <div class="form-group">
    <label class="form-label">Steps / Procedure</label>
    <textarea name="steps" class="form-control" rows="6"
              placeholder="1. Gather materials&#10;2. Set up apparatus&#10;3. Record observations&#10;4. Analyse results"><?= e($vals['steps']) ?></textarea>
  </div>

  <div class="form-group">
    <label class="form-label">Tags <span class="text-muted">(comma-separated)</span></label>
    <input type="text" name="tags" class="form-control"
           placeholder="Cosmic Rays, DIY, Particle Physics"
           value="<?= e($vals['tags']) ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Media <span class="text-muted">(image or video, max 20MB)</span></label>
    <input type="file" name="media" id="media-upload" class="form-control" accept="image/*,video/mp4,video/webm">
    <div id="media-preview"></div>
    <?php if (isset($errors['media'])): ?><div class="form-error"><?= e($errors['media']) ?></div><?php endif; ?>
  </div>

  <div class="flex gap-12 mt-24">
    <button type="submit" class="btn btn-primary btn-lg">🔬 Publish Experiment (+30 pts)</button>
    <a href="<?= BASE_URL ?>/experiments/" class="btn btn-outline btn-lg">Cancel</a>
  </div>
</form>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
