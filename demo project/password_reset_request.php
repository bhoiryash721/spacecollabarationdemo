<?php
// password_reset_request.php — Request a password reset
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_reset.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$errors = [];
$email = '';
$success = false;
$debugUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            cleanupExpiredPasswordResetTokens($pdo);
            $token = createPasswordResetToken($pdo, (int)$user['id']);
            $sent = sendPasswordResetEmail($user['email'], $user['name'], $token);

            if (!$sent && isLocalhost()) {
                $_SESSION['password_reset_debug'] = BASE_URL . '/password_reset.php?token=' . urlencode($token);
            }
        }

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔒</text></svg>">
</head>
<body>
<div class="stars-layer"></div>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div style="font-size:3rem">🔒</div>
      <h1>Password Reset</h1>
      <p>Enter your email and we’ll send instructions.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        If that email exists in our system, you will receive reset instructions shortly.
      </div>
      <?php if (!empty($_SESSION['password_reset_debug'])): ?>
        <div class="alert alert-info">
          <strong>Local debug link:</strong>
          <div style="word-break:break-all;margin-top:8px;"><?= e($_SESSION['password_reset_debug']) ?></div>
        </div>
        <?php unset($_SESSION['password_reset_debug']); ?>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline w-full" style="justify-content:center">← Return to login</a>
    <?php else: ?>
      <form method="POST" novalidate>
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 class="form-control <?= isset($errors['email']) ? 'border-danger' : '' ?>"
                 placeholder="you@example.com" value="<?= e($email) ?>" required autocomplete="email">
          <?php if (isset($errors['email'])): ?>
            <div class="form-error">⚠ <?= e($errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg">Send reset link</button>
      </form>

      <div class="auth-divider">or</div>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline w-full" style="justify-content:center">← Back to login</a>
    <?php endif; ?>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
