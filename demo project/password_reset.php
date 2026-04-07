<?php
// password_reset.php — Set a new password from a reset token
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_reset.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$password = '';
$passwordConfirm = '';
$tokenValid = false;
$tokenData = null;

if ($token) {
    cleanupExpiredPasswordResetTokens($pdo);
    $tokenData = fetchPasswordResetToken($pdo, $token);
    $tokenValid = !empty($tokenData);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (!$tokenValid) {
        $errors['token'] = 'The reset link is invalid or has expired.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!$errors && $tokenData) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $tokenData['user_id']]);

        clearPasswordResetTokens($pdo, (int)$tokenData['user_id']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $tokenData['user_id'];
        $_SESSION['user_name'] = fetchUsername($pdo, (int)$tokenData['user_id']);
        $_SESSION['user_role'] = fetchUserRole($pdo, (int)$tokenData['user_id']);

        header('Location: ' . BASE_URL . '/dashboard.php?reset=1');
        exit;
    }
}

function fetchUsername(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 'SpaceCollab Member';
}

function fetchUserRole(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 'student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Choose New Password · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔑</text></svg>">
</head>
<body>
<div class="stars-layer"></div>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div style="font-size:3rem">🔑</div>
      <h1>Choose a new password</h1>
      <p>Securely reset your account password.</p>
    </div>

    <?php if (!$tokenValid): ?>
      <div class="alert alert-danger">The reset link is invalid or has expired.</div>
      <a href="<?= BASE_URL ?>/password_reset_request.php" class="btn btn-primary w-full btn-lg">Request a new link</a>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline w-full" style="justify-content:center;margin-top:12px;">← Back to login</a>
    <?php else: ?>
      <?php if (isset($errors['token'])): ?>
        <div class="alert alert-danger"><?= e($errors['token']) ?></div>
      <?php endif; ?>
      <form method="POST" novalidate>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label class="form-label" for="password">New Password</label>
          <input type="password" id="password" name="password"
                 class="form-control <?= isset($errors['password']) ? 'border-danger' : '' ?>"
                 placeholder="Min. 8 characters" required autocomplete="new-password">
          <?php if (isset($errors['password'])): ?>
            <div class="form-error">⚠ <?= e($errors['password']) ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirm">Confirm New Password</label>
          <input type="password" id="password_confirm" name="password_confirm"
                 class="form-control <?= isset($errors['password_confirm']) ? 'border-danger' : '' ?>"
                 placeholder="Repeat password" required autocomplete="new-password">
          <?php if (isset($errors['password_confirm'])): ?>
            <div class="form-error">⚠ <?= e($errors['password_confirm']) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg">Reset password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
