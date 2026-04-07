<?php
// ============================================================
// register.php — Student registration
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$errors = [];
$vals   = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals['name']  = trim($_POST['name']  ?? '');
    $vals['email'] = trim($_POST['email'] ?? '');
    $pass          = $_POST['password']         ?? '';
    $pass2         = $_POST['password_confirm'] ?? '';

    // Validate
    if (!$vals['name'] || strlen($vals['name']) < 2)
        $errors['name'] = 'Name must be at least 2 characters.';
    if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Please enter a valid email address.';
    if (strlen($pass) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)
        $errors['password_confirm'] = 'Passwords do not match.';

    if (!$errors) {
        // Check email uniqueness
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$vals['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
            );
            $stmt->execute([$vals['name'], $vals['email'], $hash]);
            $newId = $pdo->lastInsertId();

            // Auto-login
            session_regenerate_id(true);
            $_SESSION['user_id']   = $newId;
            $_SESSION['user_name'] = $vals['name'];
            $_SESSION['user_role'] = 'student';

            header('Location: ' . BASE_URL . '/dashboard.php?welcome=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body>
<div class="stars-layer"></div>
<div style="position:fixed;top:-100px;left:50%;transform:translateX(-50%);width:500px;height:300px;
  background:radial-gradient(circle,rgba(124,58,237,0.12),transparent 70%);pointer-events:none"></div>

<div class="auth-page">
  <div class="auth-card" style="max-width:480px">
    <div class="auth-logo">
      <div style="font-size:2.5rem">🛸</div>
      <h1>Join the Mission</h1>
      <p>Create your SpaceCollab account</p>
    </div>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'border-danger' : '' ?>"
               placeholder="Yuri Gagarin" value="<?= e($vals['name']) ?>" required>
        <?php if (isset($errors['name'])): ?>
          <div class="form-error">⚠ <?= e($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'border-danger' : '' ?>"
               placeholder="you@example.com" value="<?= e($vals['email']) ?>" required>
        <?php if (isset($errors['email'])): ?>
          <div class="form-error">⚠ <?= e($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password"
               class="form-control <?= isset($errors['password']) ? 'border-danger' : '' ?>"
               placeholder="Min. 8 characters" required>
        <div class="progress-bar mt-8">
          <div class="progress-fill" id="pass-strength" style="width:0%"></div>
        </div>
        <div class="form-hint">
          Strength: <span id="pass-strength-label" style="color:var(--accent)">—</span>
        </div>
        <?php if (isset($errors['password'])): ?>
          <div class="form-error">⚠ <?= e($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirm">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm"
               class="form-control <?= isset($errors['password_confirm']) ? 'border-danger' : '' ?>"
               placeholder="Repeat password" required>
        <?php if (isset($errors['password_confirm'])): ?>
          <div class="form-error">⚠ <?= e($errors['password_confirm']) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-hint mb-16">
        By registering you agree to our community guidelines for respectful space science collaboration.
      </div>

      <button type="submit" class="btn btn-primary w-full btn-lg">
        🚀 Create Account &amp; Launch
      </button>
    </form>

    <div class="auth-divider">already aboard?</div>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline w-full" style="justify-content:center">
      🔑 Sign In
    </a>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
