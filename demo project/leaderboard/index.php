<?php
// leaderboard/index.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$userId = $_SESSION['user_id'];

$stmt = $pdo->query('
    SELECT u.id, u.name, u.points,
           (SELECT COUNT(*) FROM project_members WHERE user_id = u.id) AS projects,
           (SELECT COUNT(*) FROM experiments WHERE user_id = u.id) AS experiments,
           (SELECT COUNT(*) FROM comments WHERE user_id = u.id) AS comments,
           (SELECT COUNT(*) FROM forum_threads WHERE user_id = u.id) AS threads
    FROM users u
    WHERE u.role = "student" AND u.is_active = 1
    ORDER BY u.points DESC
    LIMIT 50
');
$leaders = $stmt->fetchAll();

// Find current user rank
$myRank = 1;
foreach ($leaders as $i => $l) {
    if ($l['id'] === $userId) { $myRank = $i + 1; break; }
}

$medals = ['🥇','🥈','🥉'];
$topClasses = ['top1','top2','top3'];

$pageTitle  = 'Leaderboard';
$activePage = 'leaderboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🏆 Leaderboard</h1>
    <p>Top contributors in the SpaceCollab community</p>
  </div>
  <div class="badge badge-gold" style="padding:10px 20px;font-size:.9rem">
    Your rank: #<?= $myRank ?>
  </div>
</div>

<!-- Points guide -->
<div class="card mb-24" style="background:rgba(79,195,247,0.04)">
  <h3 class="mb-16">How to Earn Points</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
    <?php
    $pointGuide = [
      ['🛸','Create a project','50 pts'],
      ['🤝','Join a project','10 pts'],
      ['🔬','Share experiment','30 pts'],
      ['📎','Upload file','20 pts'],
      ['💬','Post comment','5 pts'],
      ['📡','Forum thread','15 pts'],
      ['↩️','Forum reply','10 pts'],
    ];
    foreach ($pointGuide as [$icon, $action, $pts]):
    ?>
    <div style="display:flex;align-items:center;gap:10px;padding:10px;
                background:var(--bg-card);border-radius:var(--radius);border:1px solid var(--border)">
      <span style="font-size:1.3rem"><?= $icon ?></span>
      <div>
        <div style="font-size:.82rem;font-weight:600"><?= $action ?></div>
        <div style="font-size:.75rem;color:var(--accent3)"><?= $pts ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Top 3 podium -->
<?php if (count($leaders) >= 3): ?>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;max-width:600px;margin-left:auto;margin-right:auto">
  <!-- 2nd -->
  <div class="card text-center" style="order:1;padding-top:24px">
    <div style="font-size:2rem">🥈</div>
    <div class="avatar avatar-lg" style="margin:8px auto"><?= strtoupper(substr($leaders[1]['name'],0,1)) ?></div>
    <div style="font-weight:700;font-size:.9rem"><?= e($leaders[1]['name']) ?></div>
    <div style="font-family:var(--font-display);font-size:1.2rem;color:#9ca3af;margin-top:4px"><?= number_format($leaders[1]['points']) ?></div>
    <div style="font-size:.7rem;color:var(--text-muted)">POINTS</div>
  </div>
  <!-- 1st -->
  <div class="card text-center" style="order:0;border-color:rgba(251,191,36,0.4);box-shadow:0 0 30px rgba(251,191,36,0.1)">
    <div style="font-size:2.5rem">🥇</div>
    <div class="avatar avatar-lg" style="margin:8px auto;background:linear-gradient(135deg,#f59e0b,#ef4444)"><?= strtoupper(substr($leaders[0]['name'],0,1)) ?></div>
    <div style="font-weight:700;font-size:1rem"><?= e($leaders[0]['name']) ?></div>
    <div style="font-family:var(--font-display);font-size:1.5rem;color:#fbbf24;margin-top:4px"><?= number_format($leaders[0]['points']) ?></div>
    <div style="font-size:.7rem;color:var(--text-muted)">POINTS</div>
  </div>
  <!-- 3rd -->
  <div class="card text-center" style="order:2;padding-top:24px">
    <div style="font-size:2rem">🥉</div>
    <div class="avatar avatar-lg" style="margin:8px auto"><?= strtoupper(substr($leaders[2]['name'],0,1)) ?></div>
    <div style="font-weight:700;font-size:.9rem"><?= e($leaders[2]['name']) ?></div>
    <div style="font-family:var(--font-display);font-size:1.2rem;color:#b45309;margin-top:4px"><?= number_format($leaders[2]['points']) ?></div>
    <div style="font-size:.7rem;color:var(--text-muted)">POINTS</div>
  </div>
</div>
<?php endif; ?>

<!-- Full table -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Rank</th>
          <th>Student</th>
          <th>Points</th>
          <th>Projects</th>
          <th>Experiments</th>
          <th>Contributions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leaders as $i => $l): ?>
        <tr <?= $l['id'] === $userId ? 'style="background:rgba(79,195,247,0.06)"' : '' ?>>
          <td>
            <span class="rank-num <?= $topClasses[$i] ?? '' ?>">
              <?= $medals[$i] ?? ($i + 1) ?>
            </span>
          </td>
          <td>
            <div class="flex flex-center gap-12">
              <div class="avatar"><?= strtoupper(substr($l['name'],0,1)) ?></div>
              <div>
                <div style="font-weight:600"><?= e($l['name']) ?></div>
                <?php if ($l['id'] === $userId): ?>
                  <span class="badge badge-blue" style="margin-top:2px">You</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <span style="font-family:var(--font-display);font-weight:700;color:var(--accent3)">
              <?= number_format($l['points']) ?>
            </span>
          </td>
          <td><?= $l['projects'] ?></td>
          <td><?= $l['experiments'] ?></td>
          <td><?= $l['comments'] + $l['threads'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
