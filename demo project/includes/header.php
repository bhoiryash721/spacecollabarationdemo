<?php
// ============================================================
// includes/header.php — Reusable page shell top
// Usage: include it after setting $pageTitle and $activePage
// ============================================================
if (!isset($pageTitle))  $pageTitle  = 'SpaceCollab';
if (!isset($activePage)) $activePage = '';
$notifCount = isLoggedIn() ? unreadNotifCount($pdo) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="base-url" content="<?= BASE_URL ?>">
  <title><?= e($pageTitle) ?> · SpaceCollab</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body>
<div class="stars-layer"></div>

<?php if (isLoggedIn()): ?>
<div class="app-shell">

  <!-- ── Topbar ──────────────────────────────────────────── -->
  <header class="topbar">
    <div class="flex flex-center gap-12">
      <button id="menu-toggle" class="btn btn-icon btn-outline hidden-mobile" style="display:none;font-size:1.2rem" aria-label="Menu">☰</button>
      <div class="topbar-brand">🚀 Space<span>Collab</span></div>
    </div>
    <div style="flex:1;max-width:320px;margin:0 24px">
      <input id="global-search" class="form-control" placeholder="Search projects, experiments, users…" style="padding:7px 14px;font-size:.85rem">
    </div>
    <div class="topbar-right">
      <button class="notif-btn" id="notif-btn" title="Notifications">
        🔔
        <?php if ($notifCount > 0): ?>
          <span class="notif-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
        <?php endif; ?>
      </button>
      <div class="user-chip">
        <span><?= e(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></span>
        <strong><?= e($_SESSION['user_name'] ?? '') ?></strong>
      </div>
    </div>
  </header>

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <nav class="sidebar">
    <div class="sidebar-logo">
      🚀 SpaceCollab
      <small>COLLABORATIVE LEARNING</small>
    </div>

    <div class="sidebar-section">Navigation</div>
    <a href="<?= BASE_URL ?>/dashboard.php"   class="nav-item <?= $activePage==='dashboard'   ? 'active' : '' ?>"><span class="icon">🏠</span> Dashboard</a>
    <a href="<?= BASE_URL ?>/projects/"        class="nav-item <?= $activePage==='projects'    ? 'active' : '' ?>"><span class="icon">🛸</span> Projects</a>
    <a href="<?= BASE_URL ?>/experiments/"     class="nav-item <?= $activePage==='experiments' ? 'active' : '' ?>"><span class="icon">🔬</span> Experiments</a>
    <a href="<?= BASE_URL ?>/forum/"           class="nav-item <?= $activePage==='forum'       ? 'active' : '' ?>"><span class="icon">💬</span> Forum</a>
    <a href="<?= BASE_URL ?>/leaderboard/"     class="nav-item <?= $activePage==='leaderboard' ? 'active' : '' ?>"><span class="icon">🏆</span> Leaderboard</a>

    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <div class="sidebar-section" style="margin-top:16px">Admin</div>
    <a href="<?= BASE_URL ?>/admin/"           class="nav-item <?= $activePage==='admin'       ? 'active' : '' ?>"><span class="icon">⚙️</span> Admin Panel</a>
    <?php endif; ?>

    <div class="sidebar-footer">
      <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="color:var(--danger)">
        <span class="icon">🚪</span> Logout
      </a>
    </div>
  </nav>

  <!-- ── Main ────────────────────────────────────────────── -->
  <main class="main-content">

<?php else: ?>
<!-- Guest layout (auth pages use their own wrapper) -->
<?php endif; ?>
