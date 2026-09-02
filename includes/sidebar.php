<?php
$current = basename($_SERVER['SCRIPT_NAME']);
function navActive(string $file, string $current): string { return $file === $current ? 'active' : ''; }
$role = $_SESSION['user_role'] ?? '';
$base = $base ?? '';
?>
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-section-label">Operations</div>
  <a class="side-link <?= navActive('dashboard.php', $current) ?>" href="<?= $base ?>dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
  <a class="side-link <?= navActive('risk-map.php', $current) ?>" href="<?= $base ?>risk-map.php"><i class="fa-solid fa-map-location-dot"></i> Risk Map</a>
  <a class="side-link <?= navActive('habitations.php', $current) ?>" href="<?= $base ?>habitations.php"><i class="fa-solid fa-house-chimney-crack"></i> Habitations</a>
  <a class="side-link <?= navActive('ai-analysis.php', $current) ?>" href="<?= $base ?>ai-analysis.php"><i class="fa-solid fa-brain"></i> Risk Analysis</a>
  <a class="side-link <?= navActive('relocation.php', $current) ?>" href="<?= $base ?>relocation.php"><i class="fa-solid fa-people-roof"></i> Relocation Planner</a>
  <a class="side-link <?= navActive('site-comparison.php', $current) ?>" href="<?= $base ?>site-comparison.php"><i class="fa-solid fa-scale-balanced"></i> Site Comparison</a>
  <a class="side-link <?= navActive('reports.php', $current) ?>" href="<?= $base ?>reports.php"><i class="fa-solid fa-file-lines"></i> Reports</a>
  <a class="side-link <?= navActive('alerts.php', $current) ?>" href="<?= $base ?>alerts.php"><i class="fa-solid fa-triangle-exclamation"></i> Alerts</a>

  <?php if ($role === 'admin'): ?>
  <div class="sidebar-section-label">Administration</div>
  <a class="side-link <?= navActive('users.php', $current) ?>" href="<?= $base ?>admin/users.php"><i class="fa-solid fa-users-gear"></i> Users</a>
  <a class="side-link <?= navActive('data.php', $current) ?>" href="<?= $base ?>admin/data.php"><i class="fa-solid fa-database"></i> Data Management</a>
  <a class="side-link <?= navActive('settings.php', $current) ?>" href="<?= $base ?>admin/settings.php"><i class="fa-solid fa-sliders"></i> Settings</a>
  <?php endif; ?>

  <div class="sidebar-section-label mt-auto"></div>
  <a class="side-link text-danger" href="<?= $base ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</aside>
<div class="sidebar-backdrop d-lg-none" onclick="document.body.classList.remove('sidebar-open')"></div>
<main class="main-content">
