<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$conn = getDbConnection();
$pageTitle = 'Alerts';

if (isset($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare('UPDATE alerts SET is_read=1 WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: alerts.php');
    exit;
}

$alerts = $conn->query("SELECT * FROM alerts ORDER BY is_read ASC, FIELD(type,'CRITICAL','WARNING','INFO'), created_at DESC")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i>Alerts</h3>
<p class="text-dim small mb-3"><?= count($alerts) ?> total alerts</p>

<div class="hz-panel p-3">
<?php if (empty($alerts)): ?>
  <div class="empty-state"><i class="fa-solid fa-check-circle"></i><div>No alerts recorded.</div></div>
<?php else: foreach ($alerts as $a): ?>
  <div class="alert-item <?= $a['type'] ?> d-flex justify-content-between align-items-start <?= $a['is_read'] ? 'opacity-50' : '' ?>">
    <div>
      <span class="alert-tag <?= $a['type'] ?>"><?= $a['type'] ?></span>
      <div class="text-white fw-semibold mt-1"><?= sanitize($a['title']) ?></div>
      <div class="text-dim small"><?= sanitize($a['message']) ?></div>
      <div class="text-faint small mt-1"><?= date('d M Y, H:i', strtotime($a['created_at'])) ?></div>
    </div>
    <?php if (!$a['is_read']): ?><a href="alerts.php?mark_read=<?= $a['id'] ?>" class="btn btn-hz-ghost btn-sm" title="Mark as read"><i class="fa-solid fa-check"></i></a><?php endif; ?>
  </div>
<?php endforeach; endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
