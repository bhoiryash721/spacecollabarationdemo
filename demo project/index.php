<?php
// index.php — public landing page or redirect for signed-in users
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SpaceCollab · Collaborative learning for students</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body>
<div class="stars-layer"></div>

<div class="guest-shell">
  <header class="guest-header">
    <div class="guest-brand">🚀 Space<span>Collab</span></div>
    <div class="guest-cta">
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Login</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get Started</a>
    </div>
  </header>

  <main class="hero-section">
    <div class="hero-copy">
      <span class="eyebrow">Collaborative learning for future explorers</span>
      <h1>Launch student projects, share experiments, and build teams in one cosmic workspace.</h1>
      <p>SpaceCollab makes it easy for learners to create projects, publish experiments, follow peers, and join science discussion through a polished, mission-ready interface.</p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-lg">Join SpaceCollab</a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-lg">Sign in</a>
      </div>
    </div>

    <div class="hero-features">
      <div class="feature-card">
        <div class="feature-icon">🛸</div>
        <h3>Project collaboration</h3>
        <p>Start and join student-led projects with shared goals, tasks, and team members.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔬</div>
        <h3>Experiment showcase</h3>
        <p>Publish your experiments, collect feedback, and invite classmates to explore your findings.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💬</div>
        <h3>Community forum</h3>
        <p>Discuss ideas, ask questions, and collaborate with other learners across topics.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🏆</div>
        <h3>Reward system</h3>
        <p>Earn points and climb the leaderboard while contributing to projects and discussions.</p>
      </div>
    </div>
  </main>

  <section class="feature-grid">
    <div class="feature-panel">
      <h2>Built for student teams</h2>
      <p>Easy onboarding, clean collaboration spaces, and a learning dashboard that helps every user stay focused and motivated.</p>
    </div>
    <div class="feature-panel">
      <h2>Secure and reliable</h2>
      <p>SpaceCollab uses prepared statements, session controls, and later OTP login support to keep accounts safe.</p>
    </div>
    <div class="feature-panel">
      <h2>Modern interface</h2>
      <p>Dark theme, glassy cards, and responsive layouts make the platform feel polished on desktop and mobile.</p>
    </div>
  </section>

  <footer class="guest-footer">
    <p>Ready to explore with your team? Sign up now and launch your next science collaboration.</p>
    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Create account</a>
  </footer>
</div>
</body>
</html>
