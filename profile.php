<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$conn = getDbConnection();
$pageTitle = 'Profile';
$user = currentUser();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $newPass = $_POST['new_password'] ?? '';
    if (strlen($newPass) >= 8) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password=? WHERE id=?');
        $stmt->bind_param('si', $hash, $user['id']);
        $stmt->execute();
        $message = 'Password updated successfully.';
        logAction('password_change', 'User updated their password');
    } else {
        $message = 'Password must be at least 8 characters.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-3"><i class="fa-solid fa-id-badge me-2"></i>Profile</h3>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="hz-panel p-4">
      <div class="text-center mb-3"><i class="fa-solid fa-circle-user" style="font-size:3.5rem;color:var(--hz-accent)"></i></div>
      <div class="text-center"><div class="text-white fs-5 fw-semibold"><?= sanitize($user['name']) ?></div>
      <div class="text-dim small"><?= sanitize($user['email']) ?></div>
      <span class="role-pill d-inline-block mt-2"><?= sanitize($user['role']) ?></span></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="hz-panel p-4">
      <h6 class="text-white mb-3">Change Password</h6>
      <?php if ($message): ?><div class="alert alert-info py-2 small"><?= sanitize($message) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <label class="hz-label">New Password</label>
        <input type="password" name="new_password" minlength="8" required class="form-control hz-form-control mb-3">
        <button class="btn btn-hz-primary">Update Password</button>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
