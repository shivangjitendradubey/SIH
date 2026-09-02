<?php
/** Expects $pageTitle to be set by the including page.
 *  Expects optional $base to be set (e.g. '../') when included from a subdirectory like /admin. */
$user = currentUser();
$base = $base ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?>RESQZONE</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛰️</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<?php if (DEMO_MODE): ?>
<div class="demo-banner"><i class="fa-solid fa-flask"></i> DEMO / PROTOTYPE DATA — All habitations, sites and figures on this platform are fictional and used for demonstration only.</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg app-navbar">
  <div class="container-fluid px-3">
    <button class="btn sidebar-toggle d-lg-none me-2" type="button" onclick="document.body.classList.toggle('sidebar-open')">
      <i class="fa-solid fa-bars"></i>
    </button>
    <a class="navbar-brand app-brand" href="<?= $base ?>dashboard.php">
      <i class="fa-solid fa-tower-broadcast"></i> RESQZONE
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <a href="<?= $base ?>alerts.php" class="alert-bell" title="Alerts">
        <i class="fa-solid fa-bell"></i>
        <span class="alert-dot" id="navAlertDot"></span>
      </a>
      <div class="dropdown">
        <button class="btn user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="fa-solid fa-circle-user"></i>
          <span class="d-none d-md-inline"><?= sanitize($user['name'] ?? 'User') ?></span>
          <span class="role-pill"><?= sanitize($user['role'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= $base ?>profile.php"><i class="fa-solid fa-id-badge me-2"></i>Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= $base ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="app-shell">
