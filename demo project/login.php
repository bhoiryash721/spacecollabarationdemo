<?php
// ============================================================
// login.php — Student & Admin login
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';

// Already logged in → redirect
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Fetch user by email (prepared statement → no SQL injection)
        $stmt = $pdo->prepare('SELECT id, name, email, password, role, is_active FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been suspended. Contact admin.';
        } else {
            cleanupExpiredLoginOtps($pdo);
            $otpCode = createLoginOtp($pdo, $user['id']);
            $_SESSION['otp_debug_code'] = $otpCode;

            if (sendLoginOtpEmail($user['email'], $user['name'], $otpCode)) {
                $_SESSION['otp_user_id'] = $user['id'];
                $_SESSION['otp_email']   = $user['email'];

                header('Location: ' . BASE_URL . '/otp.php?sent=1');
                exit;
            }

            if (isLocalhost()) {
                $_SESSION['otp_user_id'] = $user['id'];
                $_SESSION['otp_email']   = $user['email'];
                $_SESSION['otp_notice']  = 'Email sending is not configured locally; use the one-time code shown below.';

                header('Location: ' . BASE_URL . '/otp.php');
                exit;
            }

            clearUserLoginOtps($pdo, $user['id']);
            unset($_SESSION['otp_debug_code']);
            $error = 'Unable to send the login code. Please try again later or contact support.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body>
<div class="stars-layer"></div>

<!-- Decorative orb -->
<div style="position:fixed;top:-120px;right:-120px;width:400px;height:400px;border-radius:50%;
  background:radial-gradient(circle,rgba(124,58,237,0.15),transparent 70%);pointer-events:none"></div>
<div style="position:fixed;bottom:-80px;left:-80px;width:300px;height:300px;border-radius:50%;
  background:radial-gradient(circle,rgba(79,195,247,0.1),transparent 70%);pointer-events:none"></div>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div style="font-size:3rem">🚀</div>
      <h1>SpaceCollab</h1>
      <p>Collaborative Space Learning Platform</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email"
               class="form-control" placeholder="you@spacecollab.io"
               value="<?= e($email) ?>" required autocomplete="email">
      </div>

      <div class="form-group">
        <label class="form-label" for="pass">Password</label>
        <input type="password" id="pass" name="password"
               class="form-control" placeholder="••••••••"
               required autocomplete="current-password">
      </div>

      <p style="font-size:.9rem;color:var(--text-muted);margin-top:8px">
        After your password is verified, we will send a secure 6-digit login code to your registered email.
      </p>

      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:8px">
        🚀 Launch Into SpaceCollab
      </button>
      <a href="<?= BASE_URL ?>/password_reset_request.php" class="btn btn-outline w-full btn-lg" style="margin-top:12px; justify-content:center;">
        🔒 Reset your password
      </a>
    </form>

    <div class="auth-divider">or</div>

    <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline w-full" style="justify-content:center">
      ✨ Create New Account
    </a>

    <div style="margin-top:24px;padding:16px;background:rgba(79,195,247,0.05);border-radius:var(--radius);border:1px solid var(--border)">
      <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:8px">
        <strong style="color:var(--accent)">Demo Accounts</strong> (password: <code>password</code>)
      </p>
      <p style="font-size:.75rem;color:var(--text-muted)">Student: yuri@spacecollab.io</p>
      <p style="font-size:.75rem;color:var(--text-muted)">Admin: admin@spacecollab.io</p>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
