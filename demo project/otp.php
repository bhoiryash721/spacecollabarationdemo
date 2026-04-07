<?php
// ============================================================
// otp.php — One-time password verification
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

if (empty($_SESSION['otp_user_id']) || empty($_SESSION['otp_email'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$error = '';
$success = '';
$otpCode = '';
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

$otpUserId = (int) $_SESSION['otp_user_id'];
$otpEmail = $_SESSION['otp_email'];
$otpNotice = $_SESSION['otp_notice'] ?? '';
$debugCode = $_SESSION['otp_debug_code'] ?? null;

$stmt = $pdo->prepare('SELECT name FROM users WHERE id = ? AND email = ?');
$stmt->execute([$otpUserId, $otpEmail]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_notice'], $_SESSION['otp_debug_code']);
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$otpName = $user['name'];

if ($otpNotice) {
    unset($_SESSION['otp_notice']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        cleanupExpiredLoginOtps($pdo);
        $otpCode = createLoginOtp($pdo, $otpUserId);

        if (sendLoginOtpEmail($otpEmail, $otpName, $otpCode)) {
            $success = 'A fresh login code has been sent to your email address.';
            $sent = true;
        } else {
            $error = 'Unable to send the verification code. Please check your mail configuration and try again.';
        }
    } else {
        $otpCode = trim($_POST['otp_code'] ?? '');

        if (!$otpCode) {
            $error = 'Please enter your 6-digit verification code.';
        } else {
            cleanupExpiredLoginOtps($pdo);
            $otp = fetchLatestLoginOtp($pdo, $otpUserId);

            if (empty($otp)) {
                $error = 'Your login code has expired or is invalid. Please resend a new code.';
            } elseif (!password_verify($otpCode, $otp['otp_hash'])) {
                $error = 'The code you entered is incorrect. Please try again or resend a new code.';
            } else {
                clearUserLoginOtps($pdo, $otpUserId);

                $stmt = $pdo->prepare('SELECT id, name, role, is_active FROM users WHERE id = ?');
                $stmt->execute([$otpUserId]);
                $user = $stmt->fetch();

                if (!$user || !$user['is_active']) {
                    $error = 'This account is no longer active. Contact admin if you need help.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    unset($_SESSION['otp_user_id'], $_SESSION['otp_email']);

                    header('Location: ' . BASE_URL . '/dashboard.php');
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify Login · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body>
<div class="stars-layer"></div>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div style="font-size:3rem">🔐</div>
      <h1>Verify Your Login</h1>
      <p>Enter the secure one-time code sent to <strong><?= e($otpEmail) ?></strong>.</p>
    </div>

    <?php if ($sent): ?>
      <div class="alert alert-success">✅ A new login code has been sent to your email.</div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($otpNotice): ?>
      <div class="alert alert-warning">⚠️ <?= e($otpNotice) ?></div>
    <?php endif; ?>

    <?php if ($debugCode && isLocalhost()): ?>
      <div class="alert alert-info">🔧 Local OTP code: <strong><?= e($debugCode) ?></strong></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="otp_code">Verification Code</label>
        <input type="text" id="otp_code" name="otp_code"
               class="form-control" placeholder="123456"
               value="<?= e($otpCode) ?>" maxlength="6" pattern="\d{6}"
               required autocomplete="one-time-code" inputmode="numeric">
      </div>

      <button type="submit" class="btn btn-primary w-full btn-lg" style="margin-top:8px">
        Verify &amp; Sign in
      </button>
    </form>

    <form method="POST" style="margin-top:16px;">
      <button type="submit" name="resend" value="1" class="btn btn-outline w-full">
        Resend code to email
      </button>
    </form>

    <div style="margin-top:18px;font-size:.9rem;color:var(--text-muted);">
      The code expires in 10 minutes. If you did not receive it, click "Resend code".
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
